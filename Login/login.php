<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php'); 

$login_error = '';
$email_input = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = trim($_POST['email'] ?? '');
    $senha_fornecida = $_POST['senha'] ?? '';

    if (empty($email_input)) {
        $login_error = "Preencha seu e-mail.";
    } else if (empty($senha_fornecida)) {
        $login_error = "Preencha sua senha.";
    } else {
        $sql_code = "SELECT id, nome, email, senha, tipo, status FROM usuario WHERE email = ?";
        $stmt = $mysqli->prepare($sql_code);

        if ($stmt) {
            $stmt->bind_param("s", $email_input);
            if (!$stmt->execute()) {
                $login_error = "Erro ao processar o login. Tente novamente.";
                error_log("Login Erro Execute Select: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                if ($result->num_rows == 1) {
                    $usuario = $result->fetch_assoc();

                    if (password_verify($senha_fornecida, $usuario['senha'])) {
                        
                        if ($usuario['status'] === 'desativado') {
                            $login_error = "Esta conta foi desativada e não pode ser acessada.";
                        } elseif ($usuario['status'] === 'pendente_delecao') {

                            $stmt_cancel = $mysqli->prepare("UPDATE usuario SET status = 'ativo', delecao_solicitada_em = NULL WHERE id = ?");
                            if ($stmt_cancel) {
                                $stmt_cancel->bind_param("i", $usuario['id']);
                                $stmt_cancel->execute();
                                $stmt_cancel->close();
                            }

                            $_SESSION['aviso_boas_vindas'] = 'Bem-vindo(a) de volta! A exclusão da sua conta foi cancelada.';
                            
                            session_regenerate_id(true);
                            $_SESSION['id'] = $usuario["id"];
                            $_SESSION['nome'] = $usuario['nome'];
                            $_SESSION['email'] = $usuario['email'];
                            $_SESSION['tipo'] = $usuario['tipo'];
                            header("Location: ../Menu-inicial-cliente/Menu.php");
                            exit();

                        } else { // status === 'ativo'
                            // Login normal
                            session_regenerate_id(true);
                            $_SESSION['id'] = $usuario["id"];
                            $_SESSION['nome'] = $usuario['nome'];
                            $_SESSION['email'] = $usuario['email'];
                            $_SESSION['tipo'] = $usuario['tipo'];
                            header("Location: ../Menu-inicial-cliente/Menu.php");
                            exit();
                        }

                    } else {
                        $login_error = "Falha ao logar! E-mail ou senha incorretos.";
                    }
                } else {
                    $login_error = "Falha ao logar! E-mail ou senha incorretos.";
                }
            }
            $stmt->close();
        } else {
            $login_error = "Erro no sistema de login. Tente novamente mais tarde.";
            error_log("Login Erro Prepare Select: " . $mysqli->error);
        }
    }
}
if (isset($mysqli) && $mysqli->ping()) {
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Vibra</title>
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="reset.css">
  <link rel="stylesheet" href="login.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
  <header>
    <h1>Vibra</h1>
  </header>
  <main>
    <section class="sec-cadastro">
      <h2>Bem-vindo de volta!</h2>
      <p>Se ainda não tem uma conta faça o cadastro clicando aqui</p>
      <button class="mostrar-registrar" onclick="location.href='../Cadastro/PROJETO.php';">
        Cadastrar
      </button>
    </section>
    <section class="sec-login">
      <h2>Entrar</h2>
      <?php if (!empty($login_error)): ?>
        <p style="color:red; text-align:center; margin-bottom:10px;"><?php echo htmlspecialchars($login_error); ?></p>
      <?php endif; ?>
      <?php if (isset($_GET['sucesso']) && $_GET['sucesso'] === 'cadastro'): ?>
        <p style="color:green; text-align:center; margin-bottom:10px;">Cadastro realizado com sucesso! Faça o login.</p>
      <?php endif; ?>
      <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
        <p style="color:green; text-align:center; margin-bottom:10px;">Sua senha foi redefinida com sucesso! Faça o login com sua nova senha.</p>
      <?php endif; ?>
      <?php if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_admin_negado'): ?>
        <p style="color:red; text-align:center; margin-bottom:10px;">Acesso negado. Área restrita a administradores.</p>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <label for="email-login-id">E-mail</label>
        <input type="email" id="email-login-id" class="email-login" name="email" required placeholder="E-mail" value="<?php echo htmlspecialchars($email_input); ?>">
        <label for="senha-login-id">Senha</label>
        <input type="password" id="senha-login-id" class="senha-login" name="senha" required placeholder="Senha">
        <p><a href="../RecuperarSenha/solicitar_recuperacao.php">Esqueci minha senha</a></p>
        <button type="submit">Entrar</button>
      </form>
    </section>
  </main>
</body>
</html>