<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require('../conexao.php');


if (!isset($_SESSION['id'])) {
    header('Location: ../Login/login.php');
    exit();
}

// Usando as chaves de sessão corretas que seu login.php define
$id_usuario_logado = $_SESSION['id'];
$usuario_nome_atual = $_SESSION['nome'] ?? 'Usuário';
$usuario_email_atual = $_SESSION['email'] ?? 'email@exemplo.com';
$tipo_usuario_logado = $_SESSION['tipo'] ?? 'cliente'; // Para a sidebar condicional




$prg_feedback_msg = '';
if (isset($_SESSION['prg_feedback_msg'])) {
    $prg_feedback_msg = $_SESSION['prg_feedback_msg'];
    unset($_SESSION['prg_feedback_msg']);
}

$prg_errors = [];
if (isset($_SESSION['prg_errors'])) {
    $prg_errors = $_SESSION['prg_errors'];
    unset($_SESSION['prg_errors']);
}

// Determina qual aba principal e sub-aba devem estar ativas
$prg_active_main_tab = $_SESSION['prg_active_main_tab'] ?? 'conta'; // Padrão: 'conta'
if (isset($_SESSION['prg_active_main_tab'])) {
    unset($_SESSION['prg_active_main_tab']);
}

$prg_active_sub_tab = $_SESSION['prg_active_sub_tab'] ?? 'form-cidades'; // Padrão para sub-aba
if (isset($_SESSION['prg_active_sub_tab'])) {
    unset($_SESSION['prg_active_sub_tab']);
}

function processarUploads($mysqli_conn, $item_id, $item_tipo, $input_name, &$erros_ref) {
    if (isset($_FILES[$input_name]) && !empty($_FILES[$input_name]['name'][0])) {
        $base_upload_dir = "../uploads/";
        $target_dir = '';
        if ($item_tipo === 'ponto_turistico') {
            $target_dir = $base_upload_dir . "pontos_turisticos/";
        } elseif ($item_tipo === 'evento_cultural') {
            $target_dir = $base_upload_dir . "eventos_culturais/";
        } else {
            $erros_ref[] = "Tipo de item inválido para upload.";
            return;
        }

        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true) && !is_dir($target_dir)) {
                 $erros_ref[] = "Falha ao criar o diretório de uploads: " . htmlspecialchars($target_dir);
                 return;
            }
        }

        $stmt_midia_local = $mysqli_conn->prepare("INSERT INTO midia (id_ponto_turistico, id_evento_cultural, tipo, url_arquivo) VALUES (?, ?, ?, ?)");
        if (!$stmt_midia_local) {
            $erros_ref[] = "Erro ao preparar statement para mídia (upload): " . $mysqli_conn->error;
            return;
        }

        foreach ($_FILES[$input_name]['name'] as $key => $name) {
            $current_original_file_name = basename($name);
            if ($_FILES[$input_name]['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES[$input_name]['tmp_name'][$key];
                $file_type = $_FILES[$input_name]['type'][$key];
                $file_extension = strtolower(pathinfo($current_original_file_name, PATHINFO_EXTENSION));
                $safe_file_name = uniqid($item_tipo . '_', true) . '.' . $file_extension;
                $target_file_path = $target_dir . $safe_file_name;
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array($file_extension, $allowed_types) && strpos($file_type, 'image/') === 0) {
                    if (move_uploaded_file($tmp_name, $target_file_path)) {
                        $id_ponto_upload = ($item_tipo === 'ponto_turistico') ? $item_id : null;
                        $id_evento_upload = ($item_tipo === 'evento_cultural') ? $item_id : null;
                        $tipo_midia_db_upload = 'imagem';
                        $url_arquivo_db_upload = str_replace('../', '', $target_file_path);

                        $stmt_midia_local->bind_param("iiss", $id_ponto_upload, $id_evento_upload, $tipo_midia_db_upload, $url_arquivo_db_upload);
                        if (!$stmt_midia_local->execute()) {
                            $erros_ref[] = "Erro ao salvar mídia ('".htmlspecialchars($current_original_file_name)."') no DB: " . $stmt_midia_local->error;
                        }
                    } else {
                        $erros_ref[] = "Falha ao mover arquivo '" . htmlspecialchars($current_original_file_name) . "'. Verifique as permissões.";
                    }
                } else {
                    $erros_ref[] = "Arquivo '" . htmlspecialchars($current_original_file_name) . "' tipo não permitido.";
                }
            } elseif ($_FILES[$input_name]['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $erros_ref[] = "Erro upload '" . htmlspecialchars($current_original_file_name) . "': cod " . $_FILES[$input_name]['error'][$key];
            }
        }
        $stmt_midia_local->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_action = $_POST['form_action'] ?? '';
    $erros_para_sessao = [];
    $feedback_para_sessao = '';
    $sub_aba_destino = 'form-cidades';

    if ($form_action === 'cadastrar_cidade') {
        $sub_aba_destino = 'form-cidades';
        $nome = trim($_POST['nome'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $pais = trim($_POST['pais'] ?? '');
        $descricao = trim($_POST['descricao'] ?? null);
        $latitude = (isset($_POST['latitude']) && $_POST['latitude'] !== '') ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : null;
        $longitude = (isset($_POST['longitude']) && $_POST['longitude'] !== '') ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;

        if (empty($nome)) $erros_para_sessao[] = "Nome da cidade é obrigatório.";
        if (empty($estado)) $erros_para_sessao[] = "Estado é obrigatório.";
        if (empty($pais)) $erros_para_sessao[] = "País é obrigatório.";
        if (isset($_POST['latitude']) && $_POST['latitude'] !== '' && $latitude === false) $erros_para_sessao[] = "Latitude da cidade inválida.";
        if (isset($_POST['longitude']) && $_POST['longitude'] !== '' && $longitude === false) $erros_para_sessao[] = "Longitude da cidade inválida.";

        if (empty($erros_para_sessao)) {
            $sql = "INSERT INTO cidade (nome, estado, pais, descricao, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_cidade = $mysqli->prepare($sql);
            if ($stmt_cidade) {
                $stmt_cidade->bind_param("ssssdd", $nome, $estado, $pais, $descricao, $latitude, $longitude);
                if ($stmt_cidade->execute()) {
                    $feedback_para_sessao = "<p class='feedback-success'>Cidade '" . htmlspecialchars($nome) . "' cadastrada com sucesso e enviada para análise!</p>";
                } else {
                    $erros_para_sessao[] = "Erro ao cadastrar cidade: " . $stmt_cidade->error;
                }
                $stmt_cidade->close();
            } else {
                $erros_para_sessao[] = "Erro ao preparar query (cidade): " . $mysqli->error;
            }
        }
    }
    elseif ($form_action === 'cadastrar_ponto') {
        $sub_aba_destino = 'form-pontos';
        $nome = trim($_POST['nome'] ?? '');
        $id_cidade = (isset($_POST['id_cidade']) && $_POST['id_cidade'] !== '') ? filter_var($_POST['id_cidade'], FILTER_VALIDATE_INT) : null;
        $descricao = trim($_POST['descricao'] ?? null);
        $tipo = trim($_POST['tipo'] ?? '');
        $endereco = trim($_POST['endereco'] ?? null);
        $latitude = (isset($_POST['latitude']) && $_POST['latitude'] !== '') ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : null;
        $longitude = (isset($_POST['longitude']) && $_POST['longitude'] !== '') ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;
        $horario_abertura = !empty($_POST['horario_abertura']) ? $_POST['horario_abertura'] : null;
        $horario_fechamento = !empty($_POST['horario_fechamento']) ? $_POST['horario_fechamento'] : null;
        $taxaentrada_str = $_POST['taxaentrada'] ?? '';
        $taxaentrada = ($taxaentrada_str !== '') ? filter_var($taxaentrada_str, FILTER_NULL_ON_FAILURE) : null;

        
        if (empty($nome)) $erros_para_sessao[] = "Nome do Ponto Turístico é obrigatório.";
        if ($id_cidade === false || $id_cidade === null) $erros_para_sessao[] = "Cidade inválida ou não selecionada.";
        if (empty($tipo)) $erros_para_sessao[] = "Tipo do Ponto Turístico é obrigatório.";
        if (isset($_POST['latitude']) && $_POST['latitude'] !== '' && $latitude === false) $erros_para_sessao[] = "Latitude inválida.";
        if (isset($_POST['longitude']) && $_POST['longitude'] !== '' && $longitude === false) $erros_para_sessao[] = "Longitude inválida.";
        if ($taxaentrada_str !== '' && $taxaentrada === null && $taxaentrada_str !== '0' && $taxaentrada_str !== '0.00') {
             $erros_para_sessao[] = "Taxa de entrada inválida.";
        }
        if ($taxaentrada !== null && $taxaentrada < 0) {
            $erros_para_sessao[] = "A taxa de entrada do ponto turístico não pode ser negativa.";
        }

        if (empty($erros_para_sessao)) {
            $sql = "INSERT INTO ponto_turistico (id_cidade, nome, descricao, tipo, endereco, latitude, longitude, horario_abertura, horario_fechamento, taxaentrada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_ponto = $mysqli->prepare($sql);
            if ($stmt_ponto) {
                $stmt_ponto->bind_param("issssddssd", $id_cidade, $nome, $descricao, $tipo, $endereco, $latitude, $longitude, $horario_abertura, $horario_fechamento, $taxaentrada);
                if ($stmt_ponto->execute()) {
                    $ponto_id_inserido = $stmt_ponto->insert_id;
                    $feedback_para_sessao = "<p class='feedback-success'>Ponto turístico '" . htmlspecialchars($nome) . "' cadastrado com sucesso e enviado para análise!</p>";
                    processarUploads($mysqli, $ponto_id_inserido, 'ponto_turistico', 'imagens_ponto', $erros_para_sessao);
                } else {
                    $erros_para_sessao[] = "Erro ao cadastrar ponto turístico: " . $stmt_ponto->error;
                }
                $stmt_ponto->close();
            } else {
                $erros_para_sessao[] = "Erro ao preparar query (ponto): " . $mysqli->error;
            }
        }
    }
    elseif ($form_action === 'cadastrar_evento') {
        $sub_aba_destino = 'form-evento';
        $nome = trim($_POST['nome'] ?? '');
        $id_cidade = (isset($_POST['id_cidade']) && $_POST['id_cidade'] !== '') ? filter_var($_POST['id_cidade'], FILTER_VALIDATE_INT) : null;
        $descricao = trim($_POST['descricao'] ?? null);
        $horario_abertura = trim($_POST['horario_abertura'] ?? '');
        $horario_fechamento = trim($_POST['horario_fechamento'] ?? '');
        $local_evento = trim($_POST['local_evento'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $latitude = (isset($_POST['latitude']) && $_POST['latitude'] !== '') ? filter_var($_POST['latitude'], FILTER_VALIDATE_FLOAT) : null;
        $longitude = (isset($_POST['longitude']) && $_POST['longitude'] !== '') ? filter_var($_POST['longitude'], FILTER_VALIDATE_FLOAT) : null;
        $taxaentrada_str = $_POST['taxaentrada'] ?? '';
        $taxaentrada = ($taxaentrada_str !== '') ? filter_var($taxaentrada_str, FILTER_NULL_ON_FAILURE) : null;

        if (empty($nome)) $erros_para_sessao[] = "Nome do Evento é obrigatório.";
        if ($id_cidade === false || $id_cidade === null) $erros_para_sessao[] = "Cidade inválida ou não selecionada.";
        if (empty($horario_abertura)) $erros_para_sessao[] = "Data e Horário de abertura são obrigatórios.";
        if (empty($horario_fechamento)) $erros_para_sessao[] = "Data e Horário de fechamento são obrigatórios.";
        if (empty($local_evento)) $erros_para_sessao[] = "Local do evento é obrigatório.";
        if (empty($tipo)) $erros_para_sessao[] = "Tipo do evento é obrigatório.";
        if (isset($_POST['latitude']) && $_POST['latitude'] !== '' && $latitude === false) $erros_para_sessao[] = "Latitude do evento inválida.";
        if (isset($_POST['longitude']) && $_POST['longitude'] !== '' && $longitude === false) $erros_para_sessao[] = "Longitude do evento inválida.";
        if ($taxaentrada_str !== '' && $taxaentrada === null && $taxaentrada_str !== '0' && $taxaentrada_str !== '0.00') {
            $erros_para_sessao[] = "Taxa de entrada inválida.";
        }
        if ($taxaentrada !== null && $taxaentrada < 0) {
            $erros_para_sessao[] = "A taxa de entrada do evento não pode ser negativa.";
        }
        if (!empty($horario_abertura) && !empty($horario_fechamento) && strtotime($horario_fechamento) <= strtotime($horario_abertura)) {
            $erros_para_sessao[] = "O horário de fechamento deve ser posterior ao de abertura.";
        }

        if (empty($erros_para_sessao)) {
            $sql = "INSERT INTO evento_cultural (id_cidade, nome, descricao, horario_abertura, horario_fechamento, local_evento, tipo, latitude, longitude, taxaentrada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_evento = $mysqli->prepare($sql);
            if ($stmt_evento) {
                $stmt_evento->bind_param("issssssddd", $id_cidade, $nome, $descricao, $horario_abertura, $horario_fechamento, $local_evento, $tipo, $latitude, $longitude, $taxaentrada);
                if ($stmt_evento->execute()) {
                    $evento_id_inserido = $stmt_evento->insert_id;
                    $feedback_para_sessao = "<p class='feedback-success'>Evento cultural '" . htmlspecialchars($nome) . "' cadastrado com sucesso e enviado para análise!</p>";
                    processarUploads($mysqli, $evento_id_inserido, 'evento_cultural', 'imagens_evento', $erros_para_sessao);
                } else {
                    $erros_para_sessao[] = "Erro ao cadastrar evento cultural: " . $stmt_evento->error;
                }
                $stmt_evento->close();
            } else {
                $erros_para_sessao[] = "Erro ao preparar query (evento): " . $mysqli->error;
            }
        }
    }

    if (!empty($erros_para_sessao)) {
        if (!empty($feedback_para_sessao)) {
            $feedback_para_sessao .= " No entanto, ocorreram problemas com alguns uploads:<ul>";
            foreach($erros_para_sessao as $erro_item_upload) {
                $feedback_para_sessao .= "<li>" . htmlspecialchars($erro_item_upload) . "</li>";
            }
            $feedback_para_sessao .= "</ul>";
            $_SESSION['prg_feedback_msg'] = "<div class='feedback-warning'>" . $feedback_para_sessao . "</div>";
        } else {
            $_SESSION['prg_errors'] = $erros_para_sessao;
        }
    } elseif (!empty($feedback_para_sessao)) {
        $_SESSION['prg_feedback_msg'] = $feedback_para_sessao;
    }

    $_SESSION['prg_active_main_tab'] = 'cadastros';
    $_SESSION['prg_active_sub_tab'] = $sub_aba_destino;

    header("Location: cliente.php#cadastros", true, 303);
    exit();
}

$estados_brasileiros = [
    'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
    'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
    'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
    'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
    'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
    'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
    'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente | Vibra</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>
<body>
  <header class="barra">
    <div class="left-section">
        <a href="../Menu-inicial-cliente/Menu.php" class="logo-link">
            <div class="logo">V</div>
        </a>
    </div>
    <div class="right-section">
        <a href="../Mapa/mapa.php">
            <button class="map-btn" type="button"><i class="fa-regular fa-image"></i> Mapa</button>
        </a>
        <button type="button" class="user-btn" aria-expanded="false" aria-controls="sidebar">
          <i class="fa-solid fa-bars icon-space"></i>
          <i class="fa-regular fa-user"></i>
        </button>
    </div>
  </header>
    <aside id="sidebar" class="sidebar">
        <h2 id="sidebar-username"><?php echo htmlspecialchars($usuario_nome_atual); ?></h2>
        <ul>
            <li><a href="../Menu-inicial-cliente/Menu.php">Inicio</a></li>
            <li><a href="cliente.php">Minha Conta & Cadastros</a></li>
            <?php if (isset($tipo_usuario_logado) && $tipo_usuario_logado === 'admin'): ?>
                <li><a href="../ADM/adm.php" style="color: #c9302c; font-weight: bold;">Painel Admin</a></li>
            <?php endif; ?>
            <li><a href="../Tela de perfil de usuario/perfil.php">Perfil</a></li>
            <li><a href="../logout.php">Desconectar</a></li>
        </ul>
    </aside>
    <div id="overlay" class="overlay" style="display: none;"></div>

    <main>
        <div class="container">
            <div class="tab-container">
                <div class="tabs">
                    <button class="tab-btn <?php echo ($prg_active_main_tab === 'conta') ? 'active' : ''; ?>" data-tab="conta" aria-selected="<?php echo ($prg_active_main_tab === 'conta') ? 'true' : 'false'; ?>">Conta</button>
                    <button class="tab-btn <?php echo ($prg_active_main_tab === 'cadastros') ? 'active' : ''; ?>" data-tab="cadastros" id="cadastros-tab-button" aria-selected="<?php echo ($prg_active_main_tab === 'cadastros') ? 'true' : 'false'; ?>">Meus Cadastros</button>
                </div>

                <div id="conta" class="tab-content <?php echo ($prg_active_main_tab === 'conta') ? 'active' : ''; ?>">
                    <h2 class="profile-heading">Configurações de Perfil</h2>
                    <p class="profile-subheading">Alterar detalhes de identificação da sua conta.</p>
                    <div class="form-group">
                        <label class="form-label" for="input-usuario">Usuário</label>
                        <div class="form-input-container">
                            <input type="text" id="input-usuario" class="form-input" value="<?php echo htmlspecialchars($usuario_nome_atual); ?>" readonly data-field="nome">
                            <button class="edit-btn" type="button" aria-label="Editar nome de usuário">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                            </button>
                        </div>
                        <small class="form-feedback" aria-live="polite"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="input-email">E-mail</label>
                        <div class="form-input-container">
                            <input type="email" id="input-email" class="form-input" value="<?php echo htmlspecialchars($usuario_email_atual); ?>" readonly data-field="email">
                            <button class="edit-btn" type="button" aria-label="Editar e-mail">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                            </button>
                        </div>
                        <small class="form-feedback" aria-live="polite"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <div class="form-input-container">
                            <p class="password-text">
                                <a href="#" id="openChangePasswordModal" class="password-link">Altere sua senha</a>. Aumente sua segurança.
                            </p>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    <div class="form-group">
                        <label class="form-label">Deletar Conta</label>
                        <div class="form-input-container">
                            <p class="password-text">
                                Uma vez solicitada, sua conta será permanentemente desativada após 7 dias de inatividade. 
                                <a href="#" id="openDeleteAccountModal" class="password-link" style="color: #dc3545;">Deletar minha conta</a>.
                            </p>
                        </div>
                    </div>
                </div>

                <div id="cadastros" class="tab-content <?php echo ($prg_active_main_tab === 'cadastros') ? 'active' : ''; ?>">
                    <h2 class="profile-heading">Formulário de Cadastro</h2>
                    <p class="profile-subheading">Cadastre novas cidades, pontos turísticos e eventos culturais para o Vibra!</p>
                    <?php
                    if (!empty($prg_feedback_msg)) {
                        echo "<div class='cadastro-feedback-area'>" . $prg_feedback_msg . "</div>";
                    }
                    if (!empty($prg_errors)) {
                        echo "<div class='cadastro-feedback-area feedback-error'><strong>Ocorreram os seguintes erros:</strong><ul>";
                        foreach ($prg_errors as $erro_item_display) {
                            echo "<li>" . htmlspecialchars($erro_item_display) . "</li>";
                        }
                        echo "</ul></div>";
                    }
                    ?>
                    <div class="tabs-secondary">
                        <button class="tab-secondary-btn <?php echo ($prg_active_sub_tab === 'form-cidades') ? 'active' : ''; ?>" data-tab="form-cidades" aria-selected="<?php echo ($prg_active_sub_tab === 'form-cidades') ? 'true' : 'false'; ?>">Nova Cidade</button>
                        <button class="tab-secondary-btn <?php echo ($prg_active_sub_tab === 'form-pontos') ? 'active' : ''; ?>" data-tab="form-pontos" aria-selected="<?php echo ($prg_active_sub_tab === 'form-pontos') ? 'true' : 'false'; ?>">Novo Ponto Turístico</button>
                        <button class="tab-secondary-btn <?php echo ($prg_active_sub_tab === 'form-evento') ? 'active' : ''; ?>" data-tab="form-evento" aria-selected="<?php echo ($prg_active_sub_tab === 'form-evento') ? 'true' : 'false'; ?>">Novo Evento</button>
                    </div>

                    <div id="form-cidades" class="tab-secondary-content <?php echo ($prg_active_sub_tab === 'form-cidades') ? 'active' : ''; ?>">
                        <div class="form-container">
                            <form id="cidade-form" class="cadastro-form" method="post" action="cliente.php">
                                <input type="hidden" name="form_action" value="cadastrar_cidade">
                                <div class="form-row">
                                    <label for="nome-cidade" class="cadastro-label">Nome da Cidade <span class="required">*</span></label>
                                    <input type="text" id="nome-cidade" name="nome" class="cadastro-input" placeholder="Ex: Juazeiro do Norte" required>
                                </div>
                                <div class="form-row">
                                    <label for="estado-cidade" class="cadastro-label">Estado (UF) <span class="required">*</span></label>
                                    <select id="estado-cidade" name="estado" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione o Estado</option>
                                        <?php
                                        foreach ($estados_brasileiros as $sigla => $nome_estado) {
                                            echo "<option value=\"" . htmlspecialchars($sigla) . "\">" . htmlspecialchars($nome_estado) . " (" . htmlspecialchars($sigla) . ")</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="pais-cidade" class="cadastro-label">País <span class="required">*</span></label>
                                    <input type="text" id="pais-cidade" name="pais" class="cadastro-input" placeholder="Ex: Brasil" value="Brasil" required>
                                </div>
                                <div class="form-row">
                                    <label for="descricao-cidade" class="cadastro-label">Descrição da Cidade <span class="required">*</span></label>
                                    <textarea id="descricao-cidade" name="descricao" class="cadastro-input" placeholder="Breve descrição sobre a cidade..." rows="4" required></textarea>
                                </div>
                                <div class="form-row">
                                    <label for="latitude-cidade" class="cadastro-label">Latitude <span class="required">*</span></label>
                                    <input type="number" step="any" id="latitude-cidade" name="latitude" class="cadastro-input" placeholder="Ex: -7.2127" required>
                                </div>
                                <div class="form-row">
                                    <label for="longitude-cidade" class="cadastro-label">Longitude <span class="required">*</span></label>
                                    <input type="number" step="any" id="longitude-cidade" name="longitude" class="cadastro-input" placeholder="Ex: -39.3132" required>
                                </div>
                                <div class="form-row">
                                    <button type="submit" class="btn-cadastrar">Cadastrar Cidade</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="form-pontos" class="tab-secondary-content <?php echo ($prg_active_sub_tab === 'form-pontos') ? 'active' : ''; ?>">
                        <div class="form-container">
                            <form id="ponto-form" class="cadastro-form" action="cliente.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="form_action" value="cadastrar_ponto">
                                <div class="form-row">
                                    <label for="ponto-turistico-nome" class="cadastro-label">Nome do Ponto Turístico <span class="required">*</span></label>
                                    <input type="text" id="ponto-turistico-nome" name="nome" class="cadastro-input" placeholder="Ex: Estátua do Padre Cícero" required>
                                </div>
                                <div class="form-row">
                                    <label for="id_cidade-ponto" class="cadastro-label">Cidade <span class="required">*</span></label>
                                    <select id="id_cidade-ponto" name="id_cidade" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione a cidade</option>
                                        <?php
                                        if (isset($mysqli)) {
                                            $query_cidades_select_ponto = "SELECT id, nome, estado FROM cidade ORDER BY nome ASC";
                                            $result_cidades_select_ponto = mysqli_query($mysqli, $query_cidades_select_ponto);
                                            if ($result_cidades_select_ponto) {
                                                while ($cidade_select_item_ponto = mysqli_fetch_assoc($result_cidades_select_ponto)) {
                                                    echo "<option value='" . htmlspecialchars($cidade_select_item_ponto['id']) . "'>" . htmlspecialchars($cidade_select_item_ponto['nome']) . " - " . htmlspecialchars($cidade_select_item_ponto['estado']) . "</option>";
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="descricao-ponto" class="cadastro-label">Descrição do Ponto <span class="required">*</span></label>
                                    <textarea id="descricao-ponto" name="descricao" class="cadastro-input" placeholder="Detalhes sobre o ponto turístico..." rows="4" required></textarea>
                                </div>
                                <div class="form-row">
                                    <label for="tipo-ponto" class="cadastro-label">Tipo/Categoria <span class="required">*</span></label>
                                    <select id="tipo-ponto" name="tipo" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione o tipo</option>
                                        <option value="Monumento">Monumento</option><option value="Museu">Museu</option><option value="Parque">Parque</option><option value="Praia">Praia</option><option value="Religioso">Religioso</option><option value="Cultural">Cultural</option><option value="Natureza">Natureza</option><option value="Gastronomia (Local)">Gastronomia (Local)</option><option value="Outro">Outro</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="endereco-ponto" class="cadastro-label">Endereço <span class="required">*</span></label>
                                    <input type="text" id="endereco-ponto" name="endereco" class="cadastro-input" placeholder="Rua, Número, Bairro, CEP" required>
                                </div>
                                <div class="form-row">
                                    <label for="latitude-ponto" class="cadastro-label">Latitude <span class="required">*</span></label>
                                    <input type="number" step="any" id="latitude-ponto" name="latitude" class="cadastro-input" placeholder="Ex: -7.1696" required>
                                </div>
                                <div class="form-row">
                                    <label for="longitude-ponto" class="cadastro-label">Longitude <span class="required">*</span></label>
                                    <input type="number" step="any" id="longitude-ponto" name="longitude" class="cadastro-input" placeholder="Ex: -39.3246" required>
                                </div>
                                <div class="form-row">
                                    <label for="horario-abertura-ponto" class="cadastro-label">Horário de Abertura <span class="required">*</span></label>
                                    <input type="time" id="horario-abertura-ponto" name="horario_abertura" class="cadastro-input" required>
                                </div>
                                <div class="form-row">
                                    <label for="horario-fechamento-ponto" class="cadastro-label">Horário de Fechamento <span class="required">*</span></label>
                                    <input type="time" id="horario-fechamento-ponto" name="horario_fechamento" class="cadastro-input" required>
                                </div>
                                <div class="form-row">
                                    <label for="taxaentrada-ponto" class="cadastro-label">Taxa de Entrada (R$) <span class="required">*</span></label>
                                    <input type="number" step="0.01" id="taxaentrada-ponto" name="taxaentrada" class="cadastro-input" placeholder="Ex: 25.50 (0 se gratuito)" required min="0">
                                </div>
                                <div class="form-row">
                                    <label class="cadastro-label">Imagens do Ponto Turístico <span class="required">*</span></label>
                                    <div class="file-upload-area">
                                        <input type="file" id="imagens-ponto" name="imagens_ponto[]" multiple accept="image/png, image/jpeg, image/gif" class="file-input" required>
                                        <label for="imagens-ponto" class="btn-anexo">Escolher Imagens</label>
                                        <span class="file-name">Nenhum arquivo selecionado</span>
                                    </div>
                                    <div id="preview-container-ponto" class="image-preview-container"></div>
                                </div>
                                <div class="form-row">
                                    <button type="submit" class="btn-cadastrar">Cadastrar Ponto Turístico</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div id="form-evento" class="tab-secondary-content <?php echo ($prg_active_sub_tab === 'form-evento') ? 'active' : ''; ?>">
                        <div class="form-container">
                            <form id="evento-form" class="cadastro-form" action="cliente.php" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="form_action" value="cadastrar_evento">
                                <div class="form-row">
                                    <label for="nome-evento" class="cadastro-label">Nome do Evento <span class="required">*</span></label>
                                    <input type="text" id="nome-evento" name="nome" class="cadastro-input" placeholder="Ex: Festival de Inverno" required>
                                </div>
                                <div class="form-row">
                                     <label for="id_cidade-evento" class="cadastro-label">Cidade do Evento <span class="required">*</span></label>
                                    <select id="id_cidade-evento" name="id_cidade" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione a cidade</option>
                                        <?php
                                        if (isset($result_cidades_select_ponto) && $result_cidades_select_ponto) {
                                            mysqli_data_seek($result_cidades_select_ponto, 0);
                                            while ($cidade_select_item_evento = mysqli_fetch_assoc($result_cidades_select_ponto)) {
                                                echo "<option value='" . htmlspecialchars($cidade_select_item_evento['id']) . "'>" . htmlspecialchars($cidade_select_item_evento['nome']) . " - " . htmlspecialchars($cidade_select_item_evento['estado']) . "</option>";
                                            }
                                        } elseif(isset($mysqli)) {
                                            $query_cidades_select_evento = "SELECT id, nome, estado FROM cidade ORDER BY nome ASC";
                                            $result_cidades_select_evento = mysqli_query($mysqli, $query_cidades_select_evento);
                                            if ($result_cidades_select_evento) {
                                                while ($cidade_select_item_evento = mysqli_fetch_assoc($result_cidades_select_evento)) {
                                                    echo "<option value='" . htmlspecialchars($cidade_select_item_evento['id']) . "'>" . htmlspecialchars($cidade_select_item_evento['nome']) . " - " . htmlspecialchars($cidade_select_item_evento['estado']) . "</option>";
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="descricao-evento" class="cadastro-label">Descrição do Evento <span class="required">*</span></label>
                                    <textarea id="descricao-evento" name="descricao" class="cadastro-input" placeholder="Detalhes sobre o evento, atrações..." rows="4" required></textarea>
                                </div>
                                <div class="form-row">
                                    <label for="abertura-evento" class="cadastro-label">Data e Horário de Abertura <span class="required">*</span></label>
                                    <input type="datetime-local" id="abertura-evento" name="horario_abertura" class="cadastro-input" required>
                                </div>
                                <div class="form-row">
                                    <label for="fechamento-evento" class="cadastro-label">Data e Horário de Fechamento <span class="required">*</span></label>
                                    <input type="datetime-local" id="fechamento-evento" name="horario_fechamento" class="cadastro-input" required>
                                </div>
                                <div class="form-row">
                                    <label for="local-evento" class="cadastro-label">Local do Evento <span class="required">*</span></label>
                                    <input type="text" id="local-evento" name="local_evento" class="cadastro-input" placeholder="Ex: Praça da Matriz" required>
                                </div>
                                <div class="form-row">
                                    <label for="tipo-evento" class="cadastro-label">Tipo/Categoria do Evento <span class="required">*</span></label>
                                    <select id="tipo-evento" name="tipo" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione o tipo</option>
                                        <option value="FestasShows">Festas e Shows</option><option value="Passeios">Passeios e Roteiros</option><option value="Gastronomia (Evento)">Gastronomia (Evento)</option><option value="StandUpComédia">Stand Up e Comédia</option><option value="Esportes">Esportes (Competições, Eventos)</option><option value="CursosWorkshop">Cursos e Workshops</option><option value="TeatrosEspetáculos">Teatros e Espetáculos</option><option value="Infantil">Infantil (Eventos para Crianças)</option><option value="CongressosPalestras">Congressos e Palestras</option><option value="FeirasExposicoes">Feiras e Exposições</option><option value="Religioso (Evento)">Religioso (Evento)</option><option value="Outro">Outro</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="latitude-evento" class="cadastro-label">Latitude <span class="required">*</span></label>
                                    <input type="number" step="any" id="latitude-evento" name="latitude" class="cadastro-input" placeholder="Ex: -7.2300" required>
                                </div>
                                <div class="form-row">
                                    <label for="longitude-evento" class="cadastro-label">Longitude <span class="required">*</span></label>
                                    <input type="number" step="any" id="longitude-evento" name="longitude" class="cadastro-input" placeholder="Ex: -39.4100" required>
                                </div>
                                <div class="form-row">
                                    <label for="taxa-evento" class="cadastro-label">Taxa de Entrada (R$) <span class="required">*</span></label>
                                    <input type="number" step="0.01" id="taxa-evento" name="taxaentrada" class="cadastro-input" placeholder="Ex: 50.00 (0 se gratuito)" required min="0">
                                </div>
                                <div class="form-row">
                                    <label class="cadastro-label">Imagens do Evento <span class="required">*</span></label>
                                    <div class="file-upload-area">
                                        <input type="file" id="imagens-evento" name="imagens_evento[]" multiple accept="image/png, image/jpeg, image/gif" class="file-input" required>
                                        <label for="imagens-evento" class="btn-anexo">Escolher Imagens</label>
                                        <span class="file-name">Nenhum arquivo selecionado</span>
                                    </div>
                                    <div id="preview-container-evento" class="image-preview-container"></div>
                                </div>
                                <div class="form-row">
                                    <button type="submit" class="btn-cadastrar">Cadastrar Evento</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="changePasswordModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close-btn" id="closeChangePasswordModal">&times;</span>
                <h3>Alterar Senha</h3>
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label for="current_password" class="form-label">Senha Atual:</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password" class="form-label">Nova Senha (mín. 8 caracteres):</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirmar Nova Senha:</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required minlength="8">
                    </div>
                    <div id="passwordChangeFeedback" class="form-feedback" style="min-height: 20px; margin-bottom: 10px;"></div>
                    <button type="submit" class="btn-cadastrar">Alterar Senha</button>
                </form>
            </div>
        </div>
    </main>
    <script src="cliente.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const abaPrincipalAtivaPHP = '<?php echo $prg_active_main_tab; ?>';
            const subAbaAtivaPHP = '<?php echo $prg_active_sub_tab; ?>';

            document.querySelectorAll('.tab-btn').forEach(btn => {
                const tabId = btn.getAttribute('data-tab');
                const tabContent = document.getElementById(tabId);
                if (tabId === abaPrincipalAtivaPHP) {
                    btn.classList.add('active');
                    btn.setAttribute('aria-selected', 'true');
                    if (tabContent) tabContent.classList.add('active');
                } else {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                    if (tabContent) tabContent.classList.remove('active');
                }
            });

            if (abaPrincipalAtivaPHP === 'cadastros') {
                document.querySelectorAll('.tab-secondary-btn').forEach(btn => {
                    const subTabId = btn.getAttribute('data-tab');
                    const subTabContent = document.getElementById(subTabId);
                    if (subTabId === subAbaAtivaPHP) {
                        btn.classList.add('active');
                        btn.setAttribute('aria-selected', 'true');
                        if (subTabContent) subTabContent.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                        btn.setAttribute('aria-selected', 'false');
                        if (subTabContent) subTabContent.classList.remove('active');
                    }
                });
            }

            if (window.location.hash && window.location.hash === '#cadastros') {
                const cadastrosSection = document.getElementById('cadastros');
                if (cadastrosSection) {
                    setTimeout(() => {
                        const headerOffset = document.querySelector('.barra') ? document.querySelector('.barra').offsetHeight + 10 : 70;
                        const elementPosition = cadastrosSection.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                        window.scrollTo({top: offsetPosition, behavior: 'smooth'});
                    }, 100);
                }
            }
        });
    </script>
    <?php if(isset($mysqli)) $mysqli->close(); ?>
</body>
</html>