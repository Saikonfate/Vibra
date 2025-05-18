<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php'); 


if (!isset($_SESSION['id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
 
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Somente administradores.']);
    exit;
}

header('Content-Type: application/json');
$action = $_REQUEST['action'] ?? '';

if ($action === 'listar_pendentes') {
    $eventos_pendentes = [];
    $pontos_pendentes = [];

    $sql_eventos = "SELECT ec.id, ec.nome, ec.horario_abertura, c.nome as nome_cidade, 
                           DATE_FORMAT(ec.data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro_fmt
                    FROM evento_cultural ec
                    JOIN cidade c ON ec.id_cidade = c.id
                    WHERE ec.status = 'pendente' ORDER BY ec.data_cadastro DESC";
    $result_eventos = $mysqli->query($sql_eventos);
    if ($result_eventos) {
        while ($row = $result_eventos->fetch_assoc()) {
            $eventos_pendentes[] = $row;
        }
    } else {
        error_log("Erro SQL ao listar eventos pendentes: " . $mysqli->error);
 
    }

    
    $sql_pontos = "SELECT pt.id, pt.nome, pt.tipo, c.nome as nome_cidade, 
                          DATE_FORMAT(pt.data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro_fmt
                   FROM ponto_turistico pt
                   JOIN cidade c ON pt.id_cidade = c.id
                   WHERE pt.status = 'pendente' ORDER BY pt.data_cadastro DESC";
    $result_pontos = $mysqli->query($sql_pontos);
    if ($result_pontos) {
        while ($row = $result_pontos->fetch_assoc()) {
            $pontos_pendentes[] = $row;
        }
    } else {
        error_log("Erro SQL ao listar pontos pendentes: " . $mysqli->error);
    }

    echo json_encode([
        'success' => true,
        'eventos' => $eventos_pendentes,
        'pontos' => $pontos_pendentes
    ]);
    exit;

} elseif ($action === 'atualizar_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = filter_input(INPUT_POST, 'item_id', FILTER_VALIDATE_INT);
    $item_tipo = $_POST['item_tipo'] ?? '';
    $novo_status = $_POST['novo_status'] ?? '';

    if (!$item_id || empty($item_tipo) || empty($novo_status)) {
        echo json_encode(['success' => false, 'message' => 'Dados incompletos para atualizar status.']);
        exit;
    }
    if (!in_array($novo_status, ['aprovado', 'reprovado'])) {
        echo json_encode(['success' => false, 'message' => 'Status fornecido inválido.']);
        exit;
    }

    $tabela = '';
    if ($item_tipo === 'evento') {
        $tabela = 'evento_cultural';
    } elseif ($item_tipo === 'ponto') {
        $tabela = 'ponto_turistico';
    } else {
        echo json_encode(['success' => false, 'message' => 'Tipo de item desconhecido.']);
        exit;
    }

    $stmt_update = $mysqli->prepare("UPDATE $tabela SET status = ? WHERE id = ? AND status = 'pendente'");
    if ($stmt_update) {
        $stmt_update->bind_param("si", $novo_status, $item_id);
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Status do item "' . htmlspecialchars($item_tipo) . '" atualizado para ' . htmlspecialchars($novo_status) . '!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Nenhum item pendente com este ID foi atualizado (pode já ter sido processado).']);
            }
        } else {
            error_log("Erro ao executar atualização de status para $tabela ID $item_id: " . $stmt_update->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar status do item.']);
        }
        $stmt_update->close();
    } else {
        error_log("Erro ao preparar atualização de status para $tabela: " . $mysqli->error);
        echo json_encode(['success' => false, 'message' => 'Erro no sistema ao preparar atualização.']);
    }
    if (isset($mysqli)) $mysqli->close();
    exit;
}

// Se nenhuma ação válida for encontrada
echo json_encode(['success' => false, 'message' => 'Ação inválida ou método não permitido.']);
if (isset($mysqli)) $mysqli->close();
?>