<?php
include('conexao.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit;
}

$nome     = trim($_POST['nome']);
$email    = trim($_POST['email']);
$senha    = $_POST['senha'];
$confirma = $_POST['confirma'];

if ($senha !== $confirma) {
    header('Location: cadastro.php?erro=senhas');
    exit;
}

// Escapa para evitar SQL injection
$nome  = $mysqli->real_escape_string($nome);
$email = $mysqli->real_escape_string($email);

// Verifica se e-mail já existe
$sql_check = "SELECT id FROM usuario WHERE email = '$email'";
$res_check = $mysqli->query($sql_check) or die("Erro SQL: " . $mysqli->error);

if ($res_check->num_rows > 0) {
    header('Location: cadastro.php?erro=email');
    exit;
}


$senha_salva = $mysqli->real_escape_string($senha);

$sql_insert = "
  INSERT INTO usuario (nome, email, senha)
  VALUES ('$nome', '$email', '$senha_salva')
";

if ($mysqli->query($sql_insert)) {
    // sucesso, volta ao login
    header('Location: login.php?sucesso=cadastro');
    exit;
} else {
    echo "Erro ao cadastrar: " . $mysqli->error;
}
?>
