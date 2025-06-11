<?php
// Cliente/solicitar_delecao.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php');
require('../protect.php');

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ocorreu um erro.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_usuario_logado = $_SESSION['id'];

    $sql = "UPDATE usuario SET status = 'pendente_delecao', delecao_solicitada_em = NOW() WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $id_usuario_logado);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Sua solicitação de exclusão de conta foi recebida. Sua conta será desativada em 7 dias se você não fizer login novamente.';
            
            session_destroy();

        } else {
            $response['message'] = 'Não foi possível processar sua solicitação. Tente novamente.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Erro ao preparar a solicitação.';
    }
}

$mysqli->close();
echo json_encode($response);
?>