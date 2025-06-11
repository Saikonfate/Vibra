<?php
ob_start(); 
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require('conexao.php');

$response = [
    'success' => false,
    'data' => [],
    'message' => 'Não inicializado',
    'debug_mysqli_error' => '',
    'debug_connection_status' => ''
];

if (!isset($mysqli) || !($mysqli instanceof mysqli)) {
    $response['message'] = "Erro crítico: Conexão com banco de dados não estabelecida ou \$mysqli não é válido.";
    $response['debug_connection_status'] = "Variável \$mysqli não disponível ou não é instância de mysqli.";
    ob_end_clean();
    echo json_encode($response);
    exit;
}

if ($mysqli->connect_error) {
    $response['message'] = "Falha na conexão com o banco de dados.";
    $response['debug_connection_status'] = "Erro: " . $mysqli->connect_error;
    ob_end_clean();
    echo json_encode($response);
    exit;
}
$response['debug_connection_status'] = "Conexão bem-sucedida.";

try {
    $locais = [];

    // Buscar Pontos Turísticos Aprovados
    $sql_pontos = "SELECT 
                        id, nome, descricao, tipo, endereco, latitude, longitude, 
                        horario_abertura, horario_fechamento, taxaentrada,
                        'ponto' as item_tipo 
                   FROM ponto_turistico 
                   WHERE status = 'aprovado' AND latitude IS NOT NULL AND longitude IS NOT NULL AND latitude <> 0 AND longitude <> 0";
    
    $result_pontos = $mysqli->query($sql_pontos);

    if ($result_pontos) {
        while ($ponto = $result_pontos->fetch_assoc()) {
            if (!empty($ponto['horario_abertura'])) {
                try {
                    $dt_abertura = new DateTime($ponto['horario_abertura']);
                    $ponto['horario_abertura_fmt'] = $dt_abertura->format('H:i');
                } catch (Exception $e) {
                    $ponto['horario_abertura_fmt'] = $ponto['horario_abertura'];
                }
            }
            if (!empty($ponto['horario_fechamento'])) {
                try {
                    $dt_fechamento = new DateTime($ponto['horario_fechamento']);
                    $ponto['horario_fechamento_fmt'] = $dt_fechamento->format('H:i');
                } catch (Exception $e) {
                    $ponto['horario_fechamento_fmt'] = $ponto['horario_fechamento'];
                }
            }
            $locais[] = $ponto;
        }
        $result_pontos->free();
    } else {
        $response['message'] .= "Erro ao buscar pontos turísticos. ";
        $response['debug_mysqli_error'] .= "Pontos: " . $mysqli->error . ". ";
    }

    // Buscar Eventos Culturais Aprovados (QUERY CORRIGIDA)
    $sql_eventos = "SELECT 
                        ec.id, 
                        ec.nome, 
                        ec.descricao, 
                        ec.tipo, 
                        ec.local_evento, 
                        ec.horario_abertura, 
                        ec.horario_fechamento, 
                        c.latitude,  -- CORRIGIDO: Vem da tabela cidade
                        c.longitude, -- CORRIGIDO: Vem da tabela cidade
                        ec.taxaentrada,
                        'evento' as item_tipo 
                    FROM evento_cultural ec
                    JOIN cidade c ON ec.id_cidade = c.id  -- ADICIONADO JOIN COM CIDADE
                    WHERE ec.status = 'aprovado' 
                      AND c.latitude IS NOT NULL 
                      AND c.longitude IS NOT NULL 
                      AND c.latitude <> 0 
                      AND c.longitude <> 0"; // CORRIGIDO: Filtro nas colunas da tabela cidade
    
    $result_eventos = $mysqli->query($sql_eventos);

    if ($result_eventos) {
        while ($evento = $result_eventos->fetch_assoc()) {
            if (!empty($evento['horario_abertura'])) {
                 try {
                    $dt_inicio = new DateTime($evento['horario_abertura']);
                    $evento['data_inicio_fmt'] = $dt_inicio->format('d/m/Y H:i');
                } catch (Exception $e) {
                    $evento['data_inicio_fmt'] = $evento['horario_abertura'];
                }
            }
            if (!empty($evento['horario_fechamento'])) {
                try {
                    $dt_fim = new DateTime($evento['horario_fechamento']);
                    $evento['data_fim_fmt'] = $dt_fim->format('d/m/Y H:i');
                } catch (Exception $e) {
                    $evento['data_fim_fmt'] = $evento['horario_fechamento'];
                }
            }
            $locais[] = $evento;
        }
        $result_eventos->free();
    } else {
        $response['message'] .= "Erro ao buscar eventos culturais. ";
        $response['debug_mysqli_error'] .= "Eventos: " . $mysqli->error . ". ";
    }

    if (!empty($response['debug_mysqli_error'])) {
        $response['success'] = false;
        if (strpos($response['message'], 'Erro ao buscar') !== false && $response['message'] === 'Não inicializado') {
            // Se a mensagem ainda é "Não inicializado" mas houve erro mysqli, atualiza a mensagem.
            $response['message'] = "Ocorreram erros ao buscar dados do banco.";
        } else if (strpos($response['message'], 'Erro ao buscar') === false && $response['message'] === 'Não inicializado'){
             // Se a mensagem ainda é "Não inicializado" e não houve erro mysqli, significa que algo mais grave aconteceu
             $response['message'] = "Erro desconhecido antes de executar as queries.";
        }
    } else {
        $response['success'] = true;
        $response['message'] = 'Dados carregados.';
    }
    
    $response['data'] = $locais;
    if (empty($locais) && $response['success']) {
        $response['message'] = 'Nenhum local aprovado encontrado com coordenadas válidas.';
    }

} catch (Throwable $e) {
    $response['success'] = false;
    $response['message'] = 'Ocorreu uma exceção crítica no servidor.';
    $response['debug_exception_message'] = $e->getMessage();
    $response['debug_exception_file'] = $e->getFile();
    $response['debug_exception_line'] = $e->getLine();
    error_log("Exceção em buscar_locais_mapa.php: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine());
} finally {
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }
}

ob_end_clean();
echo json_encode($response);
exit;
?>