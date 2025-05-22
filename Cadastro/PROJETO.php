<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro</title>
  <link rel="stylesheet" href="VISUAL.CSS">
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="reset.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>

  <header>
    <h1>Vibra</h1>
  </header>

  <main>
    <section id="sec-cadastro">
      <h2>Cadastrar</h2>
      <?php
        $erro_msg = '';
        if (!empty($_GET['erro'])) {
          switch ($_GET['erro']) {
            case 'email':
              $erro_msg = 'Este e-mail já está cadastrado.';
              break;
            case 'camposvazios':
              $erro_msg = 'Todos os campos são obrigatórios.';
              break;
            case 'emailinvalido':
              $erro_msg = 'Por favor, insira um e-mail válido.';
              break;
            case 'senhacurta':
              $erro_msg = 'A senha deve ter pelo menos 8 caracteres.';
              break;
            case 'errointerno':
            default:
              $erro_msg = 'Ocorreu um erro inesperado. Por favor, tente novamente.';
              break;
          }
        }
        if (!empty($erro_msg)):
      ?>
        <p style="color:red; text-align:center; margin-bottom:15px;"><?php echo htmlspecialchars($erro_msg); ?></p>
      <?php endif; ?>

      <form action="../cadastrar.php" method="POST">

        <div class="input-group">
          <i class="fas fa-user"></i>
          <input type="text" name="nome" placeholder="Nome de usuário" required>
        </div>
      
        <div class="input-group">
          <i class="fas fa-envelope"></i>
          <input type="email" name="email" placeholder="E-mail" required>
        </div>
      
        <div class="input-group">
          <i class="fas fa-lock"></i>
          <input type="password" name="senha" placeholder="Senha" required>
        </div>
      
        <button type="submit">Cadastrar</button>
      </form>
    </section>

    <section id="sec-boasvindas">
      <h2>Seja bem-vindo!</h2>
      <p>Já tem uma conta? Faça login clicando aqui</p>
      <button id="mostrar-login" onclick="location.href='../Login/login.php'">Entrar</button>
    </section>
  </main>

</body>
</html>
