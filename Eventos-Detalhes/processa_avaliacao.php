<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php'); //
header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'avaliacoes' => []];

if (!isset($_SESSION['id'])) {
    $response['message'] = 'Usuário não autenticado. Faça login para interagir com as avaliações.';
    echo json_encode($response);
    exit;
}
$id_usuario_logado = $_SESSION['id'];

$action = $_REQUEST['action'] ?? '';
// Para carregar avaliações, item_id e item_tipo podem vir via GET
// Para salvar/deletar, eles virão via POST (ou do FormData)
$item_id = isset($_REQUEST['item_id']) ? filter_var($_REQUEST['item_id'], FILTER_VALIDATE_INT) : null;
$item_tipo = isset($_REQUEST['item_tipo']) ? htmlspecialchars($_REQUEST['item_tipo']) : null;


if ($action === 'salvar_avaliacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // item_id e item_tipo são pegos do corpo do POST para salvar
    $item_id_post = isset($_POST['item_id']) ? filter_var($_POST['item_id'], FILTER_VALIDATE_INT) : null;
    $item_tipo_post = isset($_POST['item_tipo']) ? htmlspecialchars($_POST['item_tipo']) : null;
    $nota = isset($_POST['nota']) ? filter_var($_POST['nota'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 5]]) : null;
    $comentario = isset($_POST['comentario']) ? trim(htmlspecialchars($_POST['comentario'])) : '';
    $avaliacao_id_para_editar = isset($_POST['avaliacao_id']) ? filter_var($_POST['avaliacao_id'], FILTER_VALIDATE_INT) : null;


    if (!$item_id_post || !$item_tipo_post || !in_array($item_tipo_post, ['ponto', 'evento'])) {
        $response['message'] = 'ID ou tipo do item inválido para salvar avaliação.';
        echo json_encode($response);
        exit;
    }
    if ($nota === null || $nota === false) {
        $response['message'] = 'Nota inválida. Selecione de 1 a 5 estrelas.';
        echo json_encode($response);
        exit;
    }
     if (mb_strlen($comentario) > 1000) {
        $response['message'] = 'O comentário não pode exceder 1000 caracteres.';
        echo json_encode($response);
        exit;
    }

    $id_ponto_db = null;
    $id_evento_db = null;
    if ($item_tipo_post === 'ponto') {
        $id_ponto_db = $item_id_post;
    } else {
        $id_evento_db = $item_id_post;
    }

    // Se avaliacao_id_para_editar é fornecido, estamos editando uma avaliação existente
    if ($avaliacao_id_para_editar) {
        $stmt_update = $mysqli->prepare("UPDATE avaliacao SET nota = ?, comentario = ?, data = CURDATE() WHERE id = ? AND id_usuario = ?");
        if ($stmt_update) {
            $stmt_update->bind_param("isii", $nota, $comentario, $avaliacao_id_para_editar, $id_usuario_logado);
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    $response['success'] = true;
                    $response['message'] = 'Sua avaliação foi atualizada com sucesso!';
                } else {
                    // Pode não ter encontrado a avaliação ou os dados são os mesmos
                    $response['message'] = 'Nenhuma alteração detectada ou você não pode editar esta avaliação.';
                }
            } else {
                $response['message'] = 'Erro ao atualizar sua avaliação: ' . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
             $response['message'] = 'Erro ao preparar atualização da avaliação: ' . $mysqli->error;
        }
    } else {
        // Caso contrário, verifica se já existe uma avaliação para INSERIR ou ATUALIZAR (se o usuário já tinha uma sem ID específico)
        $sql_check = "SELECT id FROM avaliacao WHERE id_usuario = ? AND ";
        $sql_check .= ($item_tipo_post === 'ponto') ? "id_ponto_turistico = ?" : "id_evento_cultural = ?";
        
        $stmt_check = $mysqli->prepare($sql_check);
        if ($stmt_check) {
            $stmt_check->bind_param("ii", $id_usuario_logado, $item_id_post);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) { // Usuário já avaliou, então ATUALIZA a avaliação existente
                $existing_avaliacao = $result_check->fetch_assoc();
                $stmt_update = $mysqli->prepare("UPDATE avaliacao SET nota = ?, comentario = ?, data = CURDATE() WHERE id = ? AND id_usuario = ?");
                if ($stmt_update) {
                    $stmt_update->bind_param("isii", $nota, $comentario, $existing_avaliacao['id'], $id_usuario_logado);
                    if ($stmt_update->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Sua avaliação foi atualizada com sucesso!';
                    } else {
                        $response['message'] = 'Erro ao atualizar sua avaliação: ' . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                     $response['message'] = 'Erro ao preparar atualização da avaliação: ' . $mysqli->error;
                }
            } else { // Nova avaliação, INSERE
                $stmt_insert = $mysqli->prepare("INSERT INTO avaliacao (id_usuario, id_ponto_turistico, id_evento_cultural, nota, comentario, data) VALUES (?, ?, ?, ?, ?, CURDATE())");
                if ($stmt_insert) {
                    $stmt_insert->bind_param("iiiis", $id_usuario_logado, $id_ponto_db, $id_evento_db, $nota, $comentario);
                    if ($stmt_insert->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Avaliação enviada com sucesso!';
                        $response['avaliacao_id'] = $mysqli->insert_id; // Retorna o ID da nova avaliação
                    } else {
                        $response['message'] = 'Erro ao salvar sua avaliação: ' . $stmt_insert->error;
                    }
                    $stmt_insert->close();
                } else {
                    $response['message'] = 'Erro ao preparar para salvar avaliação: ' . $mysqli->error;
                }
            }
            $stmt_check->close();
        } else {
             $response['message'] = 'Erro ao verificar avaliação existente: ' . $mysqli->error;
        }
    }

} elseif ($action === 'carregar_avaliacoes') {
    if (!$item_id || !$item_tipo) { // Adicionado para garantir que item_id e item_tipo estejam definidos para carregar
        $response['message'] = 'ID ou tipo do item não fornecido para carregar avaliações.';
        echo json_encode($response);
        exit;
    }
    // Adicionamos o ID da avaliação e o id_usuario para permitir edição/deleção no frontend
    $sql_load = "SELECT a.id as avaliacao_id, a.id_usuario, a.nota, a.comentario, DATE_FORMAT(a.data, '%d/%m/%Y') as data_formatada, u.nome as nome_usuario
                 FROM avaliacao a
                 JOIN usuario u ON a.id_usuario = u.id
                 WHERE ";
    if ($item_tipo === 'ponto') {
        $sql_load .= "a.id_ponto_turistico = ?";
    } else { // evento
        $sql_load .= "a.id_evento_cultural = ?";
    }
    $sql_load .= " ORDER BY a.data DESC, a.id DESC";

    $stmt_load = $mysqli->prepare($sql_load);
    if ($stmt_load) {
        $stmt_load->bind_param("i", $item_id);
        $stmt_load->execute();
        $result_load = $stmt_load->get_result();
        while ($row = $result_load->fetch_assoc()) {
            $response['avaliacoes'][] = $row;
        }
        $response['success'] = true;
        $stmt_load->close();
    } else {
        $response['message'] = 'Erro ao carregar avaliações: ' . $mysqli->error;
    }

} elseif ($action === 'deletar_avaliacao' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $avaliacao_id = isset($_POST['avaliacao_id']) ? filter_var($_POST['avaliacao_id'], FILTER_VALIDATE_INT) : null;

    if (!$avaliacao_id) {
        $response['message'] = 'ID da avaliação não fornecido.';
        echo json_encode($response);
        exit;
    }

    // Verifica se a avaliação pertence ao usuário logado antes de deletar
    $stmt_delete = $mysqli->prepare("DELETE FROM avaliacao WHERE id = ? AND id_usuario = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("ii", $avaliacao_id, $id_usuario_logado);
        if ($stmt_delete->execute()) {
            if ($stmt_delete->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Avaliação deletada com sucesso!';
            } else {
                $response['message'] = 'Avaliação não encontrada ou você não tem permissão para deletá-la.';
            }
        } else {
            $response['message'] = 'Erro ao deletar avaliação: ' . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $response['message'] = 'Erro ao preparar para deletar avaliação: ' . $mysqli->error;
    }
} else {
    $response['message'] = 'Ação inválida.';
}

// Se a ação foi salvar ou deletar, recalcular e retornar a nova média e total de avaliações
if ($response['success'] && ($action === 'salvar_avaliacao' || $action === 'deletar_avaliacao')) {
    $target_item_id = ($action === 'salvar_avaliacao') ? $item_id_post : $item_id; // item_id já está definido para deletar
    $target_item_tipo = ($action === 'salvar_avaliacao') ? $item_tipo_post : $item_tipo;

    if ($target_item_id && $target_item_tipo) {
        $col_item = ($target_item_tipo === 'ponto') ? 'id_ponto_turistico' : 'id_evento_cultural';
        $sql_avg = "SELECT AVG(nota) as media_avaliacoes, COUNT(id) as total_avaliacoes FROM avaliacao WHERE $col_item = ?";
        $stmt_avg = $mysqli->prepare($sql_avg);
        if ($stmt_avg) {
            $stmt_avg->bind_param("i", $target_item_id);
            $stmt_avg->execute();
            $result_avg = $stmt_avg->get_result()->fetch_assoc();
            $response['new_average_rating'] = $result_avg['media_avaliacoes'] ? round($result_avg['media_avaliacoes'], 1) : 0;
            $response['new_total_ratings'] = $result_avg['total_avaliacoes'] ?? 0;
            $stmt_avg->close();
        }
    }
}


if (isset($mysqli)) $mysqli->close();
echo json_encode($response);
?>