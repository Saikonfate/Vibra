<?php

if (php_sapi_name() !== 'cli') {
    die("Acesso negado. Este script só pode ser executado via linha de comando.");
}

require_once(__DIR__ . '/../conexao.php');

echo "Iniciando processo de desativação de contas (" . date('Y-m-d H:i:s') . ")\n";

// Query para selecionar usuários pendentes de exclusão há mais de 7 dias
$sql_select = "SELECT id, email FROM usuario WHERE status = 'pendente_delecao' AND delecao_solicitada_em <= NOW() - INTERVAL 7 DAY";

$result = $mysqli->query($sql_select);

if (!$result) {
    echo "Erro ao buscar contas para desativar: " . $mysqli->error . "\n";
    $mysqli->close();
    exit;
}

$contas_desativadas = 0;
if ($result->num_rows > 0) {
    while ($usuario = $result->fetch_assoc()) {
        $id_usuario = $usuario['id'];
        $email_antigo = $usuario['email'];

        $email_anonimizado = $email_antigo . '.deleted.' . $id_usuario;
        $nome_anonimizado = 'Usuário Desativado';

        $sql_update = "UPDATE usuario 
                       SET status = 'desativado', 
                           data_desativacao = NOW(), 
                           email = ?, 
                           nome = ?
                       WHERE id = ?";
                       
        $stmt_update = $mysqli->prepare($sql_update);
        if ($stmt_update) {
            $stmt_update->bind_param("ssi", $email_anonimizado, $nome_anonimizado, $id_usuario);
            if ($stmt_update->execute()) {
                echo "Conta ID: $id_usuario desativada com sucesso.\n";
                $contas_desativadas++;
            } else {
                echo "Erro ao desativar conta ID: $id_usuario - " . $stmt_update->error . "\n";
            }
            $stmt_update->close();
        }
    }
}

echo "Processo finalizado. Total de contas desativadas: $contas_desativadas.\n";
$mysqli->close();
?>