<?php

if (php_sapi_name() !== 'cli') {
    die("Acesso negado.");
}
require_once(__DIR__ . '/../conexao.php');

echo "Iniciando processo de expurgo de dados antigos (" . date('Y-m-d H:i:s') . ")\n";

$sql_select = "SELECT id FROM usuario WHERE status = 'desativado' AND data_desativacao <= NOW() - INTERVAL 5 YEAR";
$result = $mysqli->query($sql_select);

if (!$result) {
    echo "Erro ao buscar contas para expurgo: " . $mysqli->error . "\n";
    $mysqli->close();
    exit;
}

$contas_expurgadas = 0;
if ($result->num_rows > 0) {
   
    $stmt_delete_avaliacoes = $mysqli->prepare("DELETE FROM avaliacao WHERE id_usuario = ?");
    $stmt_delete_favoritos = $mysqli->prepare("DELETE FROM favoritos WHERE id_usuario = ?");
    
    $stmt_delete_resets = $mysqli->prepare("DELETE FROM password_resets WHERE id_usuario = ?");
    
    $stmt_delete_usuario = $mysqli->prepare("DELETE FROM usuario WHERE id = ?");

    if (!$stmt_delete_avaliacoes || !$stmt_delete_favoritos || !$stmt_delete_resets || !$stmt_delete_usuario) {
        echo "Erro ao preparar statements de deleção.\n";
        exit;
    }

    while ($usuario = $result->fetch_assoc()) {
        $id_usuario = $usuario['id'];
        
        $mysqli->begin_transaction();
        try {
           
            $stmt_delete_avaliacoes->bind_param("i", $id_usuario);
            $stmt_delete_avaliacoes->execute();

            $stmt_delete_favoritos->bind_param("i", $id_usuario);
            $stmt_delete_favoritos->execute();

            $stmt_delete_resets->bind_param("i", $id_usuario);
            $stmt_delete_resets->execute();
            
            
            $stmt_delete_usuario->bind_param("i", $id_usuario);
            $stmt_delete_usuario->execute();
            
            $mysqli->commit();
            echo "Dados da conta ID: $id_usuario foram permanentemente expurgados.\n";
            $contas_expurgadas++;

        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();
            echo "Erro ao expurgar dados para o ID: $id_usuario. Transação revertida. Erro: " . $exception->getMessage() . "\n";
        }
    }
    $stmt_delete_avaliacoes->close();
    $stmt_delete_favoritos->close();
    $stmt_delete_resets->close();
    $stmt_delete_usuario->close();
}

echo "Processo de expurgo finalizado. Total de contas expurgadas: $contas_expurgadas.\n";
$mysqli->close();
?>