<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../conexao.php');

// Autenticação do Admin
if (!isset($_SESSION['id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    // Idealmente, redirecionar para uma página de erro ou login
    die("Acesso negado.");
}

$tipo_relatorio = $_GET['tipo_relatorio'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';
$periodo_filtro = $_GET['periodo'] ?? ''; // Ex: '7dias', '30dias', 'custom'
$status_filtro = $_GET['status'] ?? '';
$data_inicial_filtro = $_GET['data_inicial'] ?? '';
$data_final_filtro = $_GET['data_final'] ?? '';

$sql = "";
$params = [];
$param_types = "";
$filename = "relatorio_vibra.csv";

switch ($tipo_relatorio) {
    case 'cidades':
        $filename = "relatorio_cidades.csv";
        $sql = "SELECT c.id, c.nome, c.estado, c.pais, c.descricao, 
                       (SELECT COUNT(*) FROM ponto_turistico pt WHERE pt.id_cidade = c.id) as total_pontos,
                       (SELECT COUNT(*) FROM evento_cultural ec WHERE ec.id_cidade = c.id) as total_eventos
                FROM cidade c WHERE 1=1";
        if (!empty($estado_filtro)) {
            $sql .= " AND c.estado = ?";
            $params[] = $estado_filtro;
            $param_types .= "s";
        }
        $sql .= " ORDER BY c.nome ASC";
        break;

    case 'pontos':
        $filename = "relatorio_pontos_turisticos.csv";
        $sql = "SELECT pt.id, pt.nome AS nome_ponto, c.nome AS nome_cidade, pt.tipo, pt.status, DATE_FORMAT(pt.data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro
                FROM ponto_turistico pt
                JOIN cidade c ON pt.id_cidade = c.id
                WHERE 1=1";
        if (!empty($estado_filtro)) {
            $sql .= " AND c.estado = ?";
            $params[] = $estado_filtro;
            $param_types .= "s";
        }
        if (!empty($status_filtro)) {
            $sql .= " AND pt.status = ?";
            $params[] = $status_filtro;
            $param_types .= "s";
        }
        if ($periodo_filtro === 'custom' && !empty($data_inicial_filtro) && !empty($data_final_filtro)) {
            $sql .= " AND DATE(pt.data_cadastro) BETWEEN ? AND ?";
            $params[] = $data_inicial_filtro;
            $params[] = $data_final_filtro;
            $param_types .= "ss";
        } elseif ($periodo_filtro === '7dias') {
            $sql .= " AND pt.data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($periodo_filtro === '30dias') {
            $sql .= " AND pt.data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        $sql .= " ORDER BY pt.data_cadastro DESC";
        break;

    case 'eventos':
        $filename = "relatorio_eventos.csv";
        $sql = "SELECT ec.id, ec.nome AS nome_evento, c.nome AS nome_cidade, ec.tipo, ec.status, DATE_FORMAT(ec.horario_abertura, '%d/%m/%Y %H:%i') as data_evento, DATE_FORMAT(ec.data_cadastro, '%d/%m/%Y %H:%i') as data_cadastro
                FROM evento_cultural ec
                JOIN cidade c ON ec.id_cidade = c.id
                WHERE 1=1";
        if (!empty($estado_filtro)) {
            $sql .= " AND c.estado = ?";
            $params[] = $estado_filtro;
            $param_types .= "s";
        }
        if (!empty($status_filtro)) {
            $sql .= " AND ec.status = ?";
            $params[] = $status_filtro;
            $param_types .= "s";
        }
         if ($periodo_filtro === 'custom' && !empty($data_inicial_filtro) && !empty($data_final_filtro)) {
            $sql .= " AND DATE(ec.data_cadastro) BETWEEN ? AND ?";
            $params[] = $data_inicial_filtro;
            $params[] = $data_final_filtro;
            $param_types .= "ss";
        } elseif ($periodo_filtro === '7dias') {
            $sql .= " AND ec.data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($periodo_filtro === '30dias') {
            $sql .= " AND ec.data_cadastro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        }
        $sql .= " ORDER BY ec.data_cadastro DESC";
        break;

    default:
        echo "Tipo de relatório inválido.";
        exit;
}

$stmt = $mysqli->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');

        // Cabeçalho do CSV
        $header_printed = false;
        $data_row = []; 

        while ($row = $result->fetch_assoc()) {
            if (!$header_printed) {
                fputcsv($output, array_keys($row), ';'); 
                $header_printed = true;
            }
           
            $data_row = [];
            foreach($row as $value) {
                $data_row[] = $value === null ? '' : (string)$value;
            }
            fputcsv($output, $data_row, ';');
        }
        fclose($output);
        $stmt->close();
    } else {
        error_log("Erro ao executar query do relatório: " . $stmt->error);
        echo "Erro ao gerar relatório.";
    }
} else {
    error_log("Erro ao preparar query do relatório: " . $mysqli->error);
    echo "Erro ao preparar relatório.";
}
$mysqli->close();
exit;
?>