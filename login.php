<?php
include('conexao.php');

if(isset($_POST['email']) || isset( $_POST['senha'])) {

    if(strlen($_POST['email']) == 0) {
        echo "Preencha seu e-mail.";
    } else if(strlen($_POST['senha']) == 0) {
        echo "Preencha sua senha.";
    } else {

        $email = $mysqli ->real_escape_string($_POST["email"]);
        $senha = $mysqli ->real_escape_string($_POST["senha"]);

        $sql_code = "SELECT * FROM usuario WHERE email = '$email' and senha = '$senha'";
        $sql_query = $mysqli->query($sql_code) or die("Falha na execucao do codigo SQL: " . $mysqli->error);

        $quantidade = $sql_query->num_rows;


        if($quantidade == 1) {

            $usuario = $sql_query->fetch_assoc();

            if(!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['id'] = $usuario["id"];
            $_SESSION['nome'] = $usuario['nome'];

            header("Location: painel.php");

        } else {
            echo "Falha ao logar! E-mail ou senha incorretos.";
        }

    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="reset.css">
  <link rel="stylesheet" href="login.css">
</head>
<body>

  <header>
    <h1>Vibra</h1>
  </header>

  <main>
    <section class="sec-login">
      <h2>Bem-vindo de volta!</h2>
      <p>Se ainda não tem uma conta faça o cadastro clicando aqui</p>
      <button class="mostrar-registrar" onclick="location.href='cadastro.php';">
  Cadastrar
</button>

    </section>

    <section class="sec-cadastro">
      <h2>Entrar</h2>

      <div class="social-icons">
        <img src="img/x.svg" alt="x">
        <img src="img/goggle.svg" alt="goggle">
        <img src="img/face.svg" alt="face">
      </div>

      <div class="social-login">
        <p>Ou entre usando seu e-mail</p>
      </div>

      <form action="" method="POST">
        <label>E-mail</label>
        <input type="email" class="email-login" name="email" required placeholder="E-mail">

        <label>Senha</label>
        <input type="password" class="senha-login" name="senha" required placeholder="Senha">

        <p><a href="/recuperar-senha">Esqueci minha senha</a></p>

        <button type="submit">Entrar</button>
      </form>

    </section>
  </main>

</body>
</html>