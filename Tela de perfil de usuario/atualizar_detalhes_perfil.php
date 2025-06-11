<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../protect.php');
require('../conexao.php');

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Ocorreu um erro.'];

if (!isset($mysqli)) {
    $response['message'] = 'Erro: Conexão com banco de dados não estabelecida.';
    echo json_encode($response);
    exit;
}

$id_usuario_logado = $_SESSION['id'];
$nova_descricao = $_POST['descricao_perfil'] ?? null;
$nova_foto_url = null;
$update_fields = [];
$params = [];
$types = "";

// Atualizar descrição se fornecida
if ($nova_descricao !== null) {
    $update_fields[] = "descricao_perfil = ?";
    $params[] = $nova_descricao;
    $types .= "s";
}

// Processar upload da foto de perfil
if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] == UPLOAD_ERR_OK) {
    $foto_temp = $_FILES['foto_perfil']['tmp_name'];
    $nome_foto_original = $_FILES['foto_perfil']['name'];
    $tamanho_foto = $_FILES['foto_perfil']['size'];
    $tipo_foto = $_FILES['foto_perfil']['type'];

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($tipo_foto, $allowed_types)) {
        $response['message'] = 'Formato de imagem inválido. Apenas JPG, PNG e GIF são permitidos.';
        echo json_encode($response);
        exit;
    }

    if ($tamanho_foto > $max_size) {
        $response['message'] = 'A imagem é muito grande. O tamanho máximo permitido é 5MB.';
        echo json_encode($response);
        exit;
    }

    $extensao = strtolower(pathinfo($nome_foto_original, PATHINFO_EXTENSION));
    $nome_foto_servidor = "user_" . $id_usuario_logado . "_" . time() . "." . $extensao;
    $caminho_upload = '../uploads/fotos_perfil/' . $nome_foto_servidor;

    // Antes de salvar a nova foto, excluir a antiga se existir
    $stmt_old_pic = $mysqli->prepare("SELECT url_foto_perfil FROM usuario WHERE id = ?");
    if ($stmt_old_pic) {
        $stmt_old_pic->bind_param("i", $id_usuario_logado);
        $stmt_old_pic->execute();
        $result_old_pic = $stmt_old_pic->get_result();
        if ($old_pic_data = $result_old_pic->fetch_assoc()) {
            if (!empty($old_pic_data['url_foto_perfil']) && file_exists('../' . $old_pic_data['url_foto_perfil'])) {
                unlink('../' . $old_pic_data['url_foto_perfil']);
            }
        }
        $stmt_old_pic->close();
    }


    if (move_uploaded_file($foto_temp, $caminho_upload)) {
        $nova_foto_url_db = 'uploads/fotos_perfil/' . $nome_foto_servidor; // Caminho relativo a partir da raiz do projeto PI.3/
        $update_fields[] = "url_foto_perfil = ?";
        $params[] = $nova_foto_url_db;
        $types .= "s";
        $response['novaFotoUrl'] = '../' . $nova_foto_url_db; // Caminho para o cliente JS usar
    } else {
        $response['message'] = 'Erro ao fazer upload da imagem.';
        error_log("Erro no upload para o usuário $id_usuario_logado: erro " . $_FILES['foto_perfil']['error']);
        echo json_encode($response);
        exit;
    }
}

if (!empty($update_fields)) {
    $sql = "UPDATE usuario SET " . implode(", ", $update_fields) . " WHERE id = ?";
    $params[] = $id_usuario_logado;
    $types .= "i";

    $stmt_update = $mysqli->prepare($sql);
    if ($stmt_update) {
        $stmt_update->bind_param($types, ...$params);
        if ($stmt_update->execute()) {
            $response['success'] = true;
            $response['message'] = 'Perfil atualizado com sucesso!';
            if ($nova_descricao !== null) {
                 $_SESSION['descricao_perfil'] = $nova_descricao; // Atualiza sessão se necessário
                 $response['novaDescricao'] = $nova_descricao;
            }
             if (isset($response['novaFotoUrl'])) {
                 $_SESSION['url_foto_perfil'] = $response['novaFotoUrl']; // Atualiza sessão se necessário
            }

        } else {
            $response['message'] = 'Erro ao atualizar o perfil no banco de dados: ' . $stmt_update->error;
            error_log("Erro SQL ao atualizar perfil do usuário $id_usuario_logado: " . $stmt_update->error);
        }
        $stmt_update->close();
    } else {
        $response['message'] = 'Erro ao preparar a atualização do perfil: ' . $mysqli->error;
        error_log("Erro ao preparar SQL para atualizar perfil do usuário $id_usuario_logado: " . $mysqli->error);
    }
} elseif (empty($_FILES['foto_perfil']) && $nova_descricao === null) {
     $response['message'] = 'Nenhuma alteração enviada.';
     // Considerar success true aqui se não for um erro, apenas nada a fazer.
     // $response['success'] = true;
}


if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}
echo json_encode($response);
?>