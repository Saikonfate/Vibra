<?php
// só precisamos da conexão caso queira mostrar mensagens de erro vindas do DB.
// include('conexao.php');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro</title>
</head>
<body>
  <h2>Cadastre-se</h2>

  <!-- exibe erro de senhas -->
  <?php if (!empty($_GET['erro']) && $_GET['erro'] === 'senhas'): ?>
    <p style="color:red;">As senhas não conferem.</p>
  <?php endif; ?>

  <!-- exibe erro de e-mail duplicado -->
  <?php if (!empty($_GET['erro']) && $_GET['erro'] === 'email'): ?>
    <p style="color:red;">Este e-mail já está cadastrado.</p>
  <?php endif; ?>

  <form action="cadastrar.php" method="post">
    <label>Nome:<br>
      <input type="text" name="nome" required>
    </label><br><br>

    <label>E-mail:<br>
      <input type="email" name="email" required>
    </label><br><br>

    <label>Senha:<br>
      <input type="password" name="senha" required>
    </label><br><br>

    <label>Repita a senha:<br>
      <input type="password" name="confirma" required>
    </label><br><br>

    <button type="submit">Cadastrar</button>
  </form>

  <p>Já tem conta? <a href="login.php">Fazer login</a></p>
</body>
</html>
