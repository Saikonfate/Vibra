  <html lang="pt-BR">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Vibra</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="reset.css">
    <link rel="stylesheet" href="VISUAL.CSS">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
  </head>
  <body>

    <header>
      <h1>Vibra</h1>
    </header>

    <main>
      <section class="sec-cadastro"> <h2>Cadastrar</h2>
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
          <label for="nome-id">Nome de usuário</label>
          <input type="text" id="nome-id" name="nome" placeholder="Nome de usuário" required>

          <label for="email-id">E-mail</label>
          <input type="email" id="email-id" name="email" placeholder="E-mail" required>

          <label for="senha-id">Senha</label>
          <input type="password" id="senha-id" name="senha" placeholder="Senha" required>

          <button type="submit">Cadastrar</button>
        </form>
      </section>

      <section class="sec-boasvindas"> <h2>Seja bem-vindo!</h2>
        <p>Já tem uma conta? Faça login clicando aqui</p>
        <button class="mostrar-login" onclick="location.href='../Login/login.php'">Entrar</button>
      </section>
    </main>

  </body>
  </html>
