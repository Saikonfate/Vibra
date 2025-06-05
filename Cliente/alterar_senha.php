<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require('../conexao.php');
require('../protect.php'); 

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ocorreu um erro desconhecido.'];

if (!isset($mysqli)) {
    $response['message'] = 'Erro: Conexão com banco de dados não estabelecida.';
    error_log("alterar_senha.php: Conexão com DB não estabelecida.");
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['id'])) { 
    $response['message'] = 'Usuário não autenticado.';
    echo json_encode($response);
    exit;
}
$id_usuario_logado = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $response['message'] = 'Todos os campos são obrigatórios.';
        echo json_encode($response);
        exit;
    }

    if (strlen($new_password) < 8) {
        $response['message'] = 'A nova senha deve ter pelo menos 8 caracteres.';
        echo json_encode($response);
        exit;
    }

    if ($new_password !== $confirm_password) {
        $response['message'] = 'As novas senhas não coincidem.';
        echo json_encode($response);
        exit;
    }

    // Buscar senha atual do usuário
    $stmt_get_pass = $mysqli->prepare("SELECT senha FROM usuario WHERE id = ?");
    if (!$stmt_get_pass) {
        $response['message'] = 'Erro interno do servidor (prep get).';
        error_log('MySQL Prepare Error (get_pass) em alterar_senha.php: ' . $mysqli->error);
        echo json_encode($response);
        exit;
    }
    $stmt_get_pass->bind_param("i", $id_usuario_logado);
    if (!$stmt_get_pass->execute()) {
        $response['message'] = 'Erro interno do servidor (exec get).';
        error_log('MySQL Execute Error (get_pass) em alterar_senha.php: ' . $stmt_get_pass->error);
        $stmt_get_pass->close();
        echo json_encode($response);
        exit;
    }
    $result_pass = $stmt_get_pass->get_result();

    if ($result_pass->num_rows === 1) {
        $usuario = $result_pass->fetch_assoc();
        $hashed_current_password_db = $usuario['senha'];

        if (password_verify($current_password, $hashed_current_password_db)) {
            $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            if ($new_password_hashed === false) {
                 $response['message'] = 'Erro crítico ao processar nova senha.';
                 error_log('Password Hash Error for user ID ' . $id_usuario_logado . ' em alterar_senha.php');
                 echo json_encode($response);
                 exit;
            }

            $stmt_update_pass = $mysqli->prepare("UPDATE usuario SET senha = ? WHERE id = ?");
            if (!$stmt_update_pass) {
                $response['message'] = 'Erro interno do servidor (prep upd).';
                error_log('MySQL Prepare Error (update_pass) em alterar_senha.php: ' . $mysqli->error);
                echo json_encode($response);
                exit;
            }
            $stmt_update_pass->bind_param("si", $new_password_hashed, $id_usuario_logado);

            if ($stmt_update_pass->execute()) {
                if ($stmt_update_pass->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Senha alterada com sucesso!';
                } else {
                    $response['message'] = 'Nenhuma alteração na senha foi realizada. Verifique se a nova senha é diferente da atual.';
                }
            } else {
                $response['message'] = 'Erro ao atualizar a senha no banco de dados.';
                error_log('MySQL Execute Error (update_pass) em alterar_senha.php: ' . $stmt_update_pass->error);
            }
            $stmt_update_pass->close();
        } else {
            $response['message'] = 'A senha atual está incorreta.';
        }
    } else {
        $response['message'] = 'Usuário não encontrado. Por favor, faça login novamente.';
        error_log('Usuário não encontrado em alterar_senha.php para ID: ' . $id_usuario_logado);
    }
    $stmt_get_pass->close();

} else {
    $response['message'] = 'Método de requisição inválido.';
}

if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}
echo json_encode($response);
?>