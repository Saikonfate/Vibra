<?php
if (session_status() == PHP_SESSION_NONE) { // Melhor verificar antes de iniciar
    session_start();
}
include('../conexao.php'); 

header('Content-Type: application/json');

// Usar as chaves de sessão corretas: 'id', 'nome', 'email'
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado. Por favor, faça login novamente.']);
    exit();
}

$usuario_id_sessao = $_SESSION['id']; 
$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $data) {
    if (isset($data['nome'])) {
        $novo_nome = trim($data['nome']);
        if (empty($novo_nome)) {
            echo json_encode(['success' => false, 'message' => 'O nome não pode estar vazio.']);
            exit();
        }
        if (strlen($novo_nome) > 100) {
            echo json_encode(['success' => false, 'message' => 'O nome não pode exceder 100 caracteres.']);
            exit();
        }

        $stmt = $mysqli->prepare("UPDATE usuario SET nome = ? WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query (nome): ' . $mysqli->error]);
            exit();
        }
        $stmt->bind_param("si", $novo_nome, $usuario_id_sessao);
        if ($stmt->execute()) {
            $_SESSION['nome'] = $novo_nome; 
            echo json_encode(['success' => true, 'message' => 'Nome atualizado com sucesso!', 'field' => 'nome', 'newValue' => $novo_nome]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar nome: ' . $stmt->error]);
        }
        $stmt->close();

    } elseif (isset($data['email'])) {
        $novo_email = trim($data['email']);
        if (empty($novo_email) || !filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Por favor, insira um e-mail válido.']);
            exit();
        }
        if (strlen($novo_email) > 50) {
            echo json_encode(['success' => false, 'message' => 'O e-mail não pode exceder 50 caracteres.']);
            exit();
        }

        $stmt_check = $mysqli->prepare("SELECT id FROM usuario WHERE email = ? AND id != ?");
        if (!$stmt_check) {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query (verificação email): ' . $mysqli->error]);
            exit();
        }
        $stmt_check->bind_param("si", $novo_email, $usuario_id_sessao);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Este e-mail já está em uso por outra conta.']);
            $stmt_check->close();
            exit();
        }
        $stmt_check->close();

        $stmt = $mysqli->prepare("UPDATE usuario SET email = ? WHERE id = ?");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Erro ao preparar a query (email): ' . $mysqli->error]);
            exit();
        }
        $stmt->bind_param("si", $novo_email, $usuario_id_sessao);
        if ($stmt->execute()) {
            $_SESSION['email'] = $novo_email; // Atualiza a sessão com a chave correta 'email'
            echo json_encode(['success' => true, 'message' => 'E-mail atualizado com sucesso!', 'field' => 'email', 'newValue' => $novo_email]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar e-mail: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhum dado válido para atualizar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de requisição inválido ou dados não recebidos.']);
}

$mysqli->close();
?>