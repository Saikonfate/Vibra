<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php'); 

$mensagem_feedback = '';
$tipo_feedback = '';
$token_valido_para_formulario = false; 
$token_from_url = $_GET['token'] ?? '';

$id_usuario_para_reset = null;
$selector_para_deletar = null; // Para usar ao deletar o token

if (empty($token_from_url)) {
    $mensagem_feedback = "Token de recuperação inválido ou ausente.";
    $tipo_feedback = 'error';
} else {
    // O token da URL é selector (16 caracteres hex) + validator_hex (64 caracteres hex)
    $selector_from_url = substr($token_from_url, 0, 16);
    $validator_from_url_hex = substr($token_from_url, 16); // Esta é a string hexadecimal

    if (strlen($selector_from_url) !== 16 || strlen($validator_from_url_hex) !== 64 || !ctype_xdigit($selector_from_url) || !ctype_xdigit($validator_from_url_hex)) {
        $mensagem_feedback = "Formato de token inválido.";
        $tipo_feedback = 'error';
    } else {
        $stmt_find = $mysqli->prepare("SELECT id_usuario, token_hash, expires_at FROM password_resets WHERE selector = ?");
        if ($stmt_find) {
            $stmt_find->bind_param("s", $selector_from_url);
            $stmt_find->execute();
            $result_find = $stmt_find->get_result();

            if ($result_find->num_rows === 1) {
                $reset_data = $result_find->fetch_assoc();
                $agora = new DateTime();
                $data_expiracao = new DateTime($reset_data['expires_at']);

                if ($agora > $data_expiracao) {
                    $mensagem_feedback = "Este link de recuperação de senha expirou.";
                    $tipo_feedback = 'error';
                    $stmt_del = $mysqli->prepare("DELETE FROM password_resets WHERE selector = ?");
                    if($stmt_del) { $stmt_del->bind_param("s", $selector_from_url); $stmt_del->execute(); $stmt_del->close(); }
                } else {
                    // Verificar o validador do token (a versão HEXADECIMAL) com o hash armazenado
                    if (password_verify($validator_from_url_hex, $reset_data['token_hash'])) {
                        $token_valido_para_formulario = true;
                        $id_usuario_para_reset = $reset_data['id_usuario'];
                        $selector_para_deletar = $selector_from_url; // Guardar para deletar depois
                        // Não precisa mais armazenar na sessão se o token estiver na URL da action do form
                    } else {
                        $mensagem_feedback = "Token de recuperação inválido (falha na verificação).";
                        $tipo_feedback = 'error';
                    }
                }
            } else {
                $mensagem_feedback = "Token de recuperação não encontrado ou já utilizado.";
                $tipo_feedback = 'error';
            }
            $stmt_find->close();
        } else {
            $mensagem_feedback = "Erro no sistema ao verificar o token.";
            $tipo_feedback = 'error';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nova_senha'], $_POST['confirmar_senha'], $_POST['hidden_token'])) {
    // Re-validar o token que veio do campo oculto do formulário antes de processar
    $token_from_form = $_POST['hidden_token'];
    // Re-extrair selector e validator_hex do token do formulário
    $current_selector = substr($token_from_form, 0, 16);
    $current_validator_hex = substr($token_from_form, 16);

    // Re-verificar token
    $stmt_recheck = $mysqli->prepare("SELECT id_usuario, token_hash, expires_at FROM password_resets WHERE selector = ?");
    if ($stmt_recheck) {
        $stmt_recheck->bind_param("s", $current_selector);
        $stmt_recheck->execute();
        $result_recheck = $stmt_recheck->get_result();
        if ($result_recheck->num_rows === 1) {
            $recheck_data = $result_recheck->fetch_assoc();
            $agora_recheck = new DateTime();
            $expiracao_recheck = new DateTime($recheck_data['expires_at']);

            if ($agora_recheck <= $expiracao_recheck && password_verify($current_validator_hex, $recheck_data['token_hash'])) {
                $id_usuario_reset_post = $recheck_data['id_usuario']; // ID do usuário confirmado pelo token
                $nova_senha = $_POST['nova_senha'];
                $confirmar_senha = $_POST['confirmar_senha'];

                if (empty($nova_senha) || empty($confirmar_senha)) {
                    $mensagem_feedback = "Por favor, preencha ambos os campos de senha.";
                    $tipo_feedback = 'error';
                    $token_valido_para_formulario = true; // Manter formulário visível
                } elseif (strlen($nova_senha) < 8) {
                    $mensagem_feedback = "A nova senha deve ter pelo menos 8 caracteres.";
                    $tipo_feedback = 'error';
                    $token_valido_para_formulario = true;
                } elseif ($nova_senha !== $confirmar_senha) {
                    $mensagem_feedback = "As senhas não coincidem.";
                    $tipo_feedback = 'error';
                    $token_valido_para_formulario = true;
                } else {
                    $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    if ($nova_senha_hash === false) {
                        $mensagem_feedback = "Erro crítico ao gerar hash da nova senha.";
                        $tipo_feedback = 'error';
                        error_log("Falha ao gerar hash para nova_senha em resetar_senha.php");
                        $token_valido_para_formulario = true;
                    } else {
                        $stmt_update = $mysqli->prepare("UPDATE usuario SET senha = ? WHERE id = ?");
                        if ($stmt_update) {
                            $stmt_update->bind_param("si", $nova_senha_hash, $id_usuario_reset_post);
                            if ($stmt_update->execute()) {
                                $stmt_delete = $mysqli->prepare("DELETE FROM password_resets WHERE selector = ?");
                                if($stmt_delete){
                                    $stmt_delete->bind_param("s", $current_selector);
                                    $stmt_delete->execute();
                                    $stmt_delete->close();
                                }
                                header('Location: ../Login/login.php?reset=success');
                                exit();
                            } else {
                                $mensagem_feedback = "Erro ao atualizar a senha. Tente novamente.";
                                $tipo_feedback = 'error';
                            }
                            $stmt_update->close();
                        } else {
                            $mensagem_feedback = "Erro no sistema ao atualizar a senha.";
                            $tipo_feedback = 'error';
                        }
                        $token_valido_para_formulario = true;
                    }
                }
            } else { // Falha na re-verificação do token
                $mensagem_feedback = "Token inválido ou expirado ao tentar submeter nova senha. Solicite um novo link.";
                $tipo_feedback = 'error';
                $token_valido_para_formulario = false;
            }
        } else { // Falha ao preparar re-verificação
            $mensagem_feedback = "Erro no sistema ao revalidar o token.";
            $tipo_feedback = 'error';
            $token_valido_para_formulario = false;
        }
        if($stmt_recheck) $stmt_recheck->close();
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Vibra</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../Login/reset.css">
    <link rel="stylesheet" href="recuperar_senha.css">
</head>
<body>
    <header><h1>Vibra</h1></header>
    <main>
        <section class="reset-container">
            <h2>Redefinir Senha</h2>

            <?php if (!empty($mensagem_feedback)): ?>
                <div class="feedback-message <?php echo $tipo_feedback; ?>">
                    <?php echo htmlspecialchars($mensagem_feedback); ?>
                </div>
            <?php endif; ?>

            <?php if ($token_valido_para_formulario && $tipo_feedback !== 'success'): ?>
            <form action="resetar_senha.php?token=<?php echo htmlspecialchars($token_from_url); ?>" method="POST">
                <input type="hidden" name="hidden_token" value="<?php echo htmlspecialchars($token_from_url); ?>">
                <div>
                    <label for="nova_senha">Nova Senha (mínimo 8 caracteres)</label>
                    <input type="password" id="nova_senha" name="nova_senha" class="senha-login" required placeholder="Digite sua nova senha">
                </div>
                <div>
                    <label for="confirmar_senha">Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="senha-login" required placeholder="Confirme sua nova senha">
                </div>
                <button type="submit" style="margin-top:10px;">Redefinir Senha</button>
            </form>
            <?php elseif ($tipo_feedback !== 'success'): ?>
                <p style="text-align: center; margin-top: 20px;">
                    <a href="solicitar_recuperacao.php">Solicitar novo link de recuperação</a>
                </p>
            <?php endif; ?>
             <p style="text-align: center; margin-top: 20px;">
                <a href="../Login/login.php" class="back-to-login-link">Voltar para o Login</a>
            </p>
        </section>
    </main>
</body>
</html>