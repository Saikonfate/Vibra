<?php
require('conexao.php');

$feedback_msg = '';
$feedback_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome  = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha_digitada = $_POST['senha'] ?? ''; 

    // --- Validação de Entrada ---
    if (empty($nome) || empty($email) || empty($senha_digitada)) {
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=camposvazios');
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=emailinvalido');
        exit;
    }
    if (strlen($senha_digitada) < 8) { 
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=senhacurta');
        exit;
    }

    // --- Verificar se o e-mail já está cadastrado usando Prepared Statements ---
    $sql_check = "SELECT id FROM usuario WHERE email = ?";
    $stmt_check = $mysqli->prepare($sql_check);
    if (!$stmt_check) {
        error_log("Erro na preparação da query de verificação de email (cadastro): " . $mysqli->error);
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=errointerno');
        exit;
    }
    $stmt_check->bind_param("s", $email);
    if (!$stmt_check->execute()) {
        error_log("Erro ao executar a verificação de email (cadastro): " . $stmt_check->error);
        $stmt_check->close();
        $mysqli->close();
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=errointerno');
        exit;
    }
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        $mysqli->close();
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=email'); // Email já cadastrado
        exit;
    }
    $stmt_check->close();

    // --- Hashing da Senha (CRUCIAL) ---
    $senha_hash = password_hash($senha_digitada, PASSWORD_DEFAULT);
    if ($senha_hash === false) {
        error_log("Erro ao gerar hash da senha.");
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=errointerno');
        exit;
    }

    
    $tipo_usuario_padrao = 'cliente'; 
    $sql_insert = "INSERT INTO usuario (nome, email, senha, tipo) VALUES (?, ?, ?, ?)";
    $stmt_insert = $mysqli->prepare($sql_insert);

    if (!$stmt_insert) {
        error_log("Erro na preparação da query de inserção de usuário: " . $mysqli->error);
        $mysqli->close();
        header('Location: /PI.3/Cadastro/PROJETO.php?erro=errointerno');
        exit;
    }

    $stmt_insert->bind_param("ssss", $nome, $email, $senha_hash, $tipo_usuario_padrao);

    if ($stmt_insert->execute()) {
        $stmt_insert->close();
        $mysqli->close();
       
        header('Location: /PI.3/Login/login.php?sucesso=cadastro');
        exit;
    } else {
        error_log("Erro ao inserir novo usuário: " . $stmt_insert->error);
        $stmt_insert->close();
        $mysqli->close();
        echo "Erro ao cadastrar. Por favor, tente novamente. Detalhe: " . $mysqli->error; 
        
    }
} else {
    header('Location: /PI.3/Cadastro/PROJETO.php');
    exit;
}
?>