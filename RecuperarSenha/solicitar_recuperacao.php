<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php');

$mensagem_feedback = '';
$tipo_feedback = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !empty(trim($_POST['email']))) {
        $email = $mysqli->real_escape_string(trim($_POST['email']));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagem_feedback = "Por favor, insira um formato de e-mail válido.";
            $tipo_feedback = 'error';
        } else {
            $stmt_check_email = $mysqli->prepare("SELECT id, nome FROM usuario WHERE email = ?");
            if ($stmt_check_email) {
                $stmt_check_email->bind_param("s", $email);
                $stmt_check_email->execute();
                $result_check_email = $stmt_check_email->get_result();

                if ($result_check_email->num_rows === 1) {
                    $usuario_data = $result_check_email->fetch_assoc();
                    $id_usuario = $usuario_data['id'];

                    $selector = bin2hex(random_bytes(8));              // 16 caracteres hex
                    $token_validator_raw = random_bytes(32);          // Bytes crus para o validador
                    $token_validator_hex = bin2hex($token_validator_raw); // Converter para hexadecimal ANTES de hashear

                    $token_validator_hash = password_hash($token_validator_hex, PASSWORD_DEFAULT);
                    
                    if ($token_validator_hash === false) { 
                        $mensagem_feedback = "Erro crítico ao gerar token de segurança. Tente novamente.";
                        $tipo_feedback = 'error';
                        error_log("Falha ao gerar hash para token_validator_hex.");
                    } else {
                        $expires_at = new DateTime('+1 hour');
                        $expires_at_str = $expires_at->format('Y-m-d H:i:s');

                        $stmt_delete_old = $mysqli->prepare("DELETE FROM password_resets WHERE id_usuario = ?");
                        if ($stmt_delete_old) {
                            $stmt_delete_old->bind_param("i", $id_usuario);
                            $stmt_delete_old->execute();
                            $stmt_delete_old->close();
                        } 

                        $stmt_insert_token = $mysqli->prepare("INSERT INTO password_resets (id_usuario, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
                        if ($stmt_insert_token) {
                            $stmt_insert_token->bind_param("isss", $id_usuario, $selector, $token_validator_hash, $expires_at_str);
                            if ($stmt_insert_token->execute()) {
                                $token_para_link = $selector . $token_validator_hex;

                                $link_recuperacao = "http://localhost/PI.3/RecuperarSenha/resetar_senha.php?token=" . urlencode($token_para_link);
                                $assunto = "Recuperação de Senha - Vibra";
                                $corpo_email = "Olá " . htmlspecialchars($usuario_data['nome']) . ",\n\n";
                                $corpo_email .= "Você solicitou a recuperação de senha para sua conta no Vibra.\n";
                                $corpo_email .= "Clique no link a seguir para redefinir sua senha (válido por 1 hora):\n";
                                $corpo_email .= $link_recuperacao . "\n\n";
                                $corpo_email .= "Se você não solicitou isso, por favor, ignore este e-mail.\n\n";
                                $corpo_email .= "Atenciosamente,\nEquipe Vibra";
                                $headers = "From: Vibra <nao-responda@vibra.com>\r\n";
                                $headers .= "Reply-To: suporte@vibra.com\r\n";
                                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

                                $mensagem_feedback = "SIMULAÇÃO: Se um usuário com este e-mail existir, um link de recuperação seria enviado. Verifique sua caixa de entrada e spam.<br>Link (para desenvolvimento): <a href='" . htmlspecialchars($link_recuperacao) . "'>Redefinir Senha</a>";
                                $tipo_feedback = 'success';

                            } else {
                                $mensagem_feedback = "Erro ao registrar a solicitação. Tente novamente.";
                                $tipo_feedback = 'error';
                            }
                            $stmt_insert_token->close();
                        } else {
                            $mensagem_feedback = "Erro no sistema (preparar inserção token).";
                            $tipo_feedback = 'error';
                        }
                    }
                } else {
                    $mensagem_feedback = "Se um usuário com este e-mail existir em nosso sistema, um link de recuperação foi enviado. Por favor, verifique sua caixa de entrada e spam.";
                    $tipo_feedback = 'success';
                }
                $stmt_check_email->close();
            } else {
                $mensagem_feedback = "Erro no sistema (preparar verificação email).";
                $tipo_feedback = 'error';
            }
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $mensagem_feedback = "Por favor, insira seu endereço de e-mail.";
        $tipo_feedback = 'error';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Vibra</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
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
                    <?php echo $mensagem_feedback; ?>
                </div>
            <?php endif; ?>

            <?php
            $mostrar_formulario = true;
            if ($tipo_feedback === 'success' && strpos($mensagem_feedback, "SIMULAÇÃO") !== false && strpos($mensagem_feedback, "Link (para desenvolvimento)") !== false) {
                $mostrar_formulario = false; // Se a simulação foi bem-sucedida (link mostrado), não mostrar mais o form.
            } elseif ($tipo_feedback === 'success' && strpos($mensagem_feedback, "SIMULAÇÃO") === false) {
                 $mostrar_formulario = false; // Se foi um sucesso real de envio de email, não mostrar form.
            }


            if ($mostrar_formulario):
            ?>
            <form action="solicitar_recuperacao.php" method="POST">
                <div>
                    <label for="email-recuperacao">E-mail</label>
                    <input type="email" id="email-recuperacao" class="email-login" name="email" required placeholder="Seu e-mail cadastrado"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" style="margin-top: 15px;">Enviar Link de Recuperação</button>
            </form>
            <?php endif; ?>

            <p style="text-align: center; margin-top: 20px;">
                <a href="../Login/login.php">Voltar para o Login</a>
            </p>
        </section>
    </main>
</body>
</html>