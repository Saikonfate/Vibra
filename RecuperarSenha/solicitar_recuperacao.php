<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php');
require __DIR__ . '/../vendor/autoload.php'; // Se PI.3 é a raiz e este arquivo está em PI.3/RecuperarSenha/

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mensagem_feedback = '';
$tipo_feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !empty(trim($_POST['email']))) {
        $email_usuario_solicitante = $mysqli->real_escape_string(trim($_POST['email']));

        if (!filter_var($email_usuario_solicitante, FILTER_VALIDATE_EMAIL)) {
            $mensagem_feedback = "Por favor, insira um formato de e-mail válido.";
            $tipo_feedback = 'error';
        } else {
            $stmt_check_email = $mysqli->prepare("SELECT id, nome FROM usuario WHERE email = ?");
            if ($stmt_check_email) {
                $stmt_check_email->bind_param("s", $email_usuario_solicitante);
                $stmt_check_email->execute();
                $result_check_email = $stmt_check_email->get_result();

                if ($result_check_email->num_rows === 1) {
                    $usuario_data = $result_check_email->fetch_assoc();
                    $id_usuario = $usuario_data['id'];
                    $nome_usuario = $usuario_data['nome'];

                    $selector = bin2hex(random_bytes(8));
                    $token_validator_raw = random_bytes(32);
                    $token_validator_hex = bin2hex($token_validator_raw);
                    $token_validator_hash = password_hash($token_validator_hex, PASSWORD_DEFAULT);
                    
                    if ($token_validator_hash === false) { 
                        $mensagem_feedback = "Erro crítico ao gerar token de segurança. Tente novamente.";
                        $tipo_feedback = 'error';
                        error_log("Falha ao gerar hash para token_validator_hex em solicitar_recuperacao.php");
                    } else {
                        $expires_at = new DateTime('+1 hour');
                        $expires_at_str = $expires_at->format('Y-m-d H:i:s');

                        $stmt_delete_old = $mysqli->prepare("DELETE FROM password_resets WHERE id_usuario = ?");
                        if ($stmt_delete_old) {
                            $stmt_delete_old->bind_param("i", $id_usuario);
                            $stmt_delete_old->execute();
                            $stmt_delete_old->close();
                        } else {
                            error_log("Erro ao preparar delete de tokens antigos (solicitar_recuperacao.php): " . $mysqli->error);
                        }

                        $stmt_insert_token = $mysqli->prepare("INSERT INTO password_resets (id_usuario, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
                        if ($stmt_insert_token) {
                            $stmt_insert_token->bind_param("isss", $id_usuario, $selector, $token_validator_hash, $expires_at_str);
                            if ($stmt_insert_token->execute()) {
                                $token_para_link = $selector . $token_validator_hex;
                                
                                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
                                $domainName = $_SERVER['HTTP_HOST'];
                                $caminho_base_projeto = "/PI.3"; 
                                
                                $link_recuperacao = $protocol . $domainName . $caminho_base_projeto . "/RecuperarSenha/resetar_senha.php?token=" . urlencode($token_para_link);
                                
                                $assunto = "Recuperação de Senha - Vibra";
                                $corpo_email_html = "<p>Olá " . htmlspecialchars($nome_usuario) . ",</p>";
                                $corpo_email_html .= "<p>Você solicitou a recuperação de senha para sua conta no Vibra.</p>";
                                $corpo_email_html .= "<p>Clique no link a seguir para redefinir sua senha (válido por 1 hora):<br>";
                                $corpo_email_html .= "<a href='" . htmlspecialchars($link_recuperacao) . "'>" . htmlspecialchars($link_recuperacao) . "</a></p>";
                                $corpo_email_html .= "<p>Se você não solicitou isso, por favor, ignore este e-mail.</p>";
                                $corpo_email_html .= "<p>Atenciosamente,<br>Equipe Vibra</p>";
                                $corpo_email_texto = strip_tags(str_replace(["<br>", "<p>", "</p>"], ["\n", "\n", ""], $corpo_email_html));

                                $mail = new PHPMailer(true);
                                try {
                                    //Configurações do Servidor SMTP Gmail
                                    // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Descomente para depuração detalhada
                                    $mail->isSMTP();
                                    $mail->Host       = 'smtp.gmail.com';
                                    $mail->SMTPAuth   = true;
                                    $mail->Username   = 'noreply.vibra@gmail.com';
                                    $mail->Password   = '';
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                                    $mail->Port       = 465;
                                    $mail->CharSet    = 'UTF-8';

                                    //Remetente e Destinatário
                                    $mail->setFrom('noreply.vibra@gmail.com', 'Vibra - Suporte'); // Email e nome que aparecerão como remetente
                                    $mail->addAddress($email_usuario_solicitante, htmlspecialchars($nome_usuario)); // Email do destinatário

                                    //Conteúdo
                                    $mail->isHTML(true);
                                    $mail->Subject = $assunto;
                                    $mail->Body    = $corpo_email_html;
                                    $mail->AltBody = $corpo_email_texto;

                                    $mail->send();
                                    $mensagem_feedback = 'Um e-mail com instruções para redefinir sua senha foi enviado para ' . htmlspecialchars($email_usuario_solicitante) . '. Por favor, verifique sua caixa de entrada e spam.';
                                    $tipo_feedback = 'success';

                                } catch (Exception $e) {
                                    $mensagem_feedback = "O e-mail não pôde ser enviado. Erro do Mailer: {$mail->ErrorInfo}. Por favor, tente novamente mais tarde ou contate o suporte.";
                                    $tipo_feedback = 'error';
                                    error_log("PHPMailer Erro (solicitar_recuperacao.php): " . $mail->ErrorInfo);
                                }

                            } else {
                                $mensagem_feedback = "Erro ao registrar a solicitação no banco. Tente novamente.";
                                $tipo_feedback = 'error';
                                error_log("Erro ao inserir token (solicitar_recuperacao.php): " . $stmt_insert_token->error);
                            }
                            $stmt_insert_token->close();
                        } else {
                            $mensagem_feedback = "Erro no sistema ao preparar para registrar solicitação.";
                            $tipo_feedback = 'error';
                             error_log("Erro ao preparar statement de inserção de token (solicitar_recuperacao.php): " . $mysqli->error);
                        }
                    }
                } else { 
                    $mensagem_feedback = "Se um usuário com este e-mail existir em nosso sistema, um link de recuperação foi enviado. Por favor, verifique sua caixa de entrada e spam.";
                    $tipo_feedback = 'success';
                }
                $stmt_check_email->close();
            } else {
                $mensagem_feedback = "Erro no sistema ao verificar e-mail.";
                $tipo_feedback = 'error';
                error_log("Erro ao preparar statement de verificação de email (solicitar_recuperacao.php): " . $mysqli->error);
            }
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mensagem_feedback = "Por favor, insira seu endereço de e-mail.";
        $tipo_feedback = 'error';
    }
}
if(isset($mysqli)) $mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Vibra</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Login/reset.css"> 
    <link rel="stylesheet" href="recuperar_senha.css">
</head>
<body>
    <header><h1>Vibra</h1></header>
    <main>
        <section class="recovery-container">
            <h2>Recuperar Senha</h2>
            <p style="text-align: center; margin-bottom: 20px; color: #666;">
                Digite seu e-mail e enviaremos um link para você redefinir sua senha.
            </p>

            <?php if (!empty($mensagem_feedback)): ?>
                <div class="feedback-message <?php echo $tipo_feedback; ?>">
                    <?php echo htmlspecialchars(strip_tags($mensagem_feedback)); // Mostra apenas a mensagem, sem links HTML diretos ?>
                </div>
            <?php endif; ?>

            <?php
            $mostrar_formulario_solicitacao = true;
            if ($tipo_feedback === 'success') {
                 // Se a mensagem de sucesso é a genérica ou a de envio real, não mostra mais o form.
                 if (strpos($mensagem_feedback, "Um e-mail com instruções") !== false || 
                     strpos($mensagem_feedback, "Se um usuário com este e-mail existir") !== false) {
                    $mostrar_formulario_solicitacao = false;
                 }
            }
            
            if ($mostrar_formulario_solicitacao):
            ?>
            <form action="solicitar_recuperacao.php" method="POST">
                <div>
                    <label for="email-recuperacao">E-mail</label>
                    <input type="email" id="email-recuperacao" name="email" class="email-login" required placeholder="Seu e-mail cadastrado"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" style="margin-top: 15px;">Enviar Link de Recuperação</button>
            </form>
            <?php endif; ?>

            <p style="text-align: center; margin-top: 20px;">
                <a href="../Login/login.php" class="back-to-login-link">Voltar para o Login</a>
            </p>
        </section>
    </main>
</body>
</html>
