<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php'); 

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty(trim($_POST['email']))) {
        $login_error = "Preencha seu e-mail.";
    } else if (empty($_POST['senha'])) {
        $login_error = "Preencha sua senha.";
    } else {
        $email = $mysqli->real_escape_string(trim($_POST["email"])); // Ainda pode usar para buscar
        $senha_fornecida = $_POST["senha"];

        // Selecionar o usuário pelo email
        $sql_code = "SELECT id, nome, email, senha, tipo FROM usuario WHERE email = ?";
        $stmt = $mysqli->prepare($sql_code);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $usuario = $result->fetch_assoc();

                // Verificar a senha hasheada
                if (password_verify($senha_fornecida, $usuario['senha'])) {
                    // Senha correta
                    session_regenerate_id(true); 

                    $_SESSION['id'] = $usuario["id"];
                    $_SESSION['nome'] = $usuario['nome'];
                    $_SESSION['email'] = $usuario['email']; 
                    $_SESSION['tipo'] = $usuario['tipo'];   

                    header("Location: /PI.3/Menu-inicial-cliente/Menu.php"); 
                    exit();
                } else {
                    $login_error = "Falha ao logar! E-mail ou senha incorretos.";
                }
            } else {
                $login_error = "Falha ao logar! E-mail ou senha incorretos.";
            }
            $stmt->close();
        } else {
            $login_error = "Erro no sistema. Tente novamente mais tarde.";
        }
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="reset.css"> <link rel="stylesheet" href="login.css"> </head>
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

      <form action="login.php" method="POST"> 
        <label>E-mail</label>
        <input type="email" class="email-login" name="email" required placeholder="E-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        <label>Senha</label>
        <input type="password" class="senha-login" name="senha" required placeholder="Senha">
        <p><a href="../RecuperarSenha/solicitar_recuperacao.php">Esqueci minha senha</a></p> 
        <button type="submit">Entrar</button>
      </form>
    </section>
  </main>
</body>
</html>