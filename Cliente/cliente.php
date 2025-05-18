<?php
// No TOPO do cliente.php, ANTES de qualquer saída HTML
if (session_status() == PHP_SESSION_NONE) { // Garante que a sessão seja iniciada apenas uma vez
    session_start();
}
// Use require para arquivos críticos como conexão. Ajuste o caminho se necessário.
require('../conexao.php');

// --- Início: Uso das chaves de sessão CORRETAS ---
// Seu script de login define: $_SESSION['id'], $_SESSION['nome'], $_SESSION['email']
if (!isset($_SESSION['id'])) { // Verificando a chave de sessão 'id'
    header('Location: ../Login/login.php'); // Ajuste o caminho para seu login.php
    exit();
}

// Usando as chaves de sessão corretas que seu login.php define
$id_usuario_logado = $_SESSION['id'];
$usuario_nome_atual = $_SESSION['nome'] ?? 'Usuário';
$usuario_email_atual = $_SESSION['email'] ?? 'email@exemplo.com';
// --- Fim: Uso das chaves de sessão CORRETAS ---


// --- INÍCIO: Recuperar mensagens e estado da sessão (para o GET após o REDIRECT) ---
$prg_feedback_msg = ''; // Para mensagens de sucesso ou avisos
if (isset($_SESSION['prg_feedback_msg'])) {
    $prg_feedback_msg = $_SESSION['prg_feedback_msg'];
    unset($_SESSION['prg_feedback_msg']);
}

$prg_errors = []; // Para erros de validação ou processamento
if (isset($_SESSION['prg_errors'])) {
    $prg_errors = $_SESSION['prg_errors'];
    unset($_SESSION['prg_errors']);
}

$prg_active_main_tab = $_SESSION['prg_active_main_tab'] ?? 'conta'; // Aba principal ('conta' ou 'cadastros')
if (isset($_SESSION['prg_active_main_tab'])) {
    unset($_SESSION['prg_active_main_tab']);
}

$prg_active_sub_tab = $_SESSION['prg_active_sub_tab'] ?? 'form-cidades'; // Sub-aba de cadastros
if (isset($_SESSION['prg_active_sub_tab'])) {
    unset($_SESSION['prg_active_sub_tab']);
}
// --- FIM: Recuperar mensagens e estado da sessão ---


// --- INÍCIO: Função processarUploads (COPIADA DO SEU CÓDIGO ORIGINAL e revisada) ---
function processarUploads($mysqli, $item_id, $item_tipo, $input_name, &$erros_array) { // Parâmetro de erros renomeado para clareza
    if (isset($_FILES[$input_name]) && !empty($_FILES[$input_name]['name'][0])) {
        $base_upload_dir = "../uploads/";
        if ($item_tipo === 'ponto_turistico') {
            $target_dir = $base_upload_dir . "pontos_turisticos/";
        } elseif ($item_tipo === 'evento_cultural') {
            $target_dir = $base_upload_dir . "eventos_culturais/";
        } else {
            $erros_array[] = "Tipo de item inválido para upload.";
            return;
        }

        if (!is_dir($target_dir)) {
            if (!mkdir($target_dir, 0777, true) && !is_dir($target_dir)) {
                 $erros_array[] = "Falha ao criar o diretório de uploads: " . htmlspecialchars($target_dir);
                 return;
            }
        }

        $stmt_midia = $mysqli->prepare("INSERT INTO midia (id_ponto_turistico, id_evento_cultural, tipo, url_arquivo) VALUES (?, ?, ?, ?)");
        if (!$stmt_midia) {
            $erros_array[] = "Erro ao preparar statement para mídia: " . $mysqli->error;
            return;
        }

        foreach ($_FILES[$input_name]['name'] as $key => $name) {
            $current_original_file_name = basename($name); // Variável para o nome original do arquivo atual no loop

            if ($_FILES[$input_name]['error'][$key] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES[$input_name]['tmp_name'][$key];
                $file_type = $_FILES[$input_name]['type'][$key];
                $file_extension = strtolower(pathinfo($current_original_file_name, PATHINFO_EXTENSION));
                $safe_file_name = uniqid($item_tipo . '_', true) . '.' . $file_extension;
                $target_file_path = $target_dir . $safe_file_name;

                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($file_extension, $allowed_types) && strpos($file_type, 'image/') === 0) {
                    if (move_uploaded_file($tmp_name, $target_file_path)) {
                        $id_ponto_db = ($item_tipo === 'ponto_turistico') ? $item_id : null;
                        $id_evento_db = ($item_tipo === 'evento_cultural') ? $item_id : null;
                        $tipo_midia_db = 'imagem';
                        $url_arquivo_db = str_replace('../', '', $target_file_path);

                        $stmt_midia->bind_param("iiss", $id_ponto_db, $id_evento_db, $tipo_midia_db, $url_arquivo_db);
                        if (!$stmt_midia->execute()) {
                            $erros_array[] = "Erro ao salvar mídia ('".htmlspecialchars($current_original_file_name)."') no banco de dados: " . $stmt_midia->error;
                        }
                    } else {
                        $erros_array[] = "Falha ao mover o arquivo '" . htmlspecialchars($current_original_file_name) . "'. Verifique as permissões.";
                    }
                } else {
                    $erros_array[] = "Arquivo '" . htmlspecialchars($current_original_file_name) . "' não é uma imagem válida (tipos permitidos: jpg, jpeg, png, gif) ou tipo não permitido.";
                }
            } elseif ($_FILES[$input_name]['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                $erros_array[] = "Erro no upload do arquivo '" . htmlspecialchars($current_original_file_name) . "': código " . $_FILES[$input_name]['error'][$key];
            }
        }
        $stmt_midia->close();
    }
}
// --- FIM: Função processarUploads ---


// --- INÍCIO: Lógica de Cadastro com PRG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_action = $_POST['form_action'] ?? '';
    $erros_para_sessao = []; // Erros a serem passados para a sessão
    $feedback_para_sessao = ''; // Mensagem de sucesso/aviso para a sessão
    $sub_aba_destino = 'form-cidades'; // Sub-aba padrão após redirecionamento

    // --- Processar Cadastro de Cidade ---
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
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssssdd", $nome, $estado, $pais, $descricao, $latitude, $longitude);
                if ($stmt->execute()) {
                    $feedback_para_sessao = "<p class='feedback-success'>Cidade '" . htmlspecialchars($nome) . "' cadastrada com sucesso!</p>";
                } else {
                    $erros_para_sessao[] = "Erro ao cadastrar cidade: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $erros_para_sessao[] = "Erro ao preparar query (cidade): " . $mysqli->error;
            }
        }
    }
    // --- Processar Cadastro de Ponto Turístico ---
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
        $taxaentrada = (isset($_POST['taxaentrada']) && $_POST['taxaentrada'] !== '') ? filter_var($_POST['taxaentrada'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) : null;
        if ($taxaentrada === false && isset($_POST['taxaentrada']) && $_POST['taxaentrada'] !== '' && $_POST['taxaentrada'] !== '0') { // Tratar explicitamente o '0'
            $erros_para_sessao[] = "Taxa de entrada do ponto turístico inválida.";
            $taxaentrada = null; // Resetar se inválido e não for '0'
        }


        if (empty($nome)) $erros_para_sessao[] = "Nome do Ponto Turístico é obrigatório.";
        if ($id_cidade === false || $id_cidade === null) $erros_para_sessao[] = "Cidade inválida ou não selecionada para o ponto turístico.";
        if (empty($tipo)) $erros_para_sessao[] = "Tipo do Ponto Turístico é obrigatório.";
        if (isset($_POST['latitude']) && $_POST['latitude'] !== '' && $latitude === false) $erros_para_sessao[] = "Latitude do ponto turístico inválida.";
        if (isset($_POST['longitude']) && $_POST['longitude'] !== '' && $longitude === false) $erros_para_sessao[] = "Longitude do ponto turístico inválida.";
        // A validação da taxa de entrada já foi feita acima


        if (empty($erros_para_sessao)) { // Somente prossegue se não houver erros de validação dos campos principais
            $sql = "INSERT INTO ponto_turistico (id_cidade, nome, descricao, tipo, endereco, latitude, longitude, horario_abertura, horario_fechamento, taxaentrada) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("issssddssd", $id_cidade, $nome, $descricao, $tipo, $endereco, $latitude, $longitude, $horario_abertura, $horario_fechamento, $taxaentrada);
                if ($stmt->execute()) {
                    $ponto_id_inserido = $stmt->insert_id;
                    $feedback_para_sessao = "<p class='feedback-success'>Ponto turístico '" . htmlspecialchars($nome) . "' cadastrado com sucesso!</p>";
                    // Processar uploads e adicionar quaisquer erros de upload a $erros_para_sessao
                    processarUploads($mysqli, $ponto_id_inserido, 'ponto_turistico', 'imagens_ponto', $erros_para_sessao);
                } else {
                    $erros_para_sessao[] = "Erro ao cadastrar ponto turístico: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $erros_para_sessao[] = "Erro ao preparar query (ponto): " . $mysqli->error;
            }
        }
    }
    // --- Processar Cadastro de Evento Cultural ---
    elseif ($form_action === 'cadastrar_evento') {
        $sub_aba_destino = 'form-evento';
        $nome = trim($_POST['nome'] ?? '');
        $id_cidade = (isset($_POST['id_cidade']) && $_POST['id_cidade'] !== '') ? filter_var($_POST['id_cidade'], FILTER_VALIDATE_INT) : null;
        $descricao = trim($_POST['descricao'] ?? null);
        $horario_abertura = trim($_POST['horario_abertura'] ?? '');
        $horario_fechamento = trim($_POST['horario_fechamento'] ?? '');
        $local_evento = trim($_POST['local_evento'] ?? '');
        $tipo = trim($_POST['tipo'] ?? '');
        $taxaentrada = (isset($_POST['taxaentrada']) && $_POST['taxaentrada'] !== '') ? filter_var($_POST['taxaentrada'], FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE) : null;
         if ($taxaentrada === false && isset($_POST['taxaentrada']) && $_POST['taxaentrada'] !== '' && $_POST['taxaentrada'] !== '0') {
            $erros_para_sessao[] = "Taxa de entrada do evento inválida.";
            $taxaentrada = null;
        }


        if (empty($nome)) $erros_para_sessao[] = "Nome do Evento é obrigatório.";
        if ($id_cidade === false || $id_cidade === null) $erros_para_sessao[] = "Cidade inválida ou não selecionada para o evento.";
        if (empty($horario_abertura)) $erros_para_sessao[] = "Data e Horário de abertura do evento são obrigatórios.";
        if (empty($horario_fechamento)) $erros_para_sessao[] = "Data e Horário de fechamento do evento são obrigatórios.";
        if (empty($local_evento)) $erros_para_sessao[] = "Local do evento é obrigatório.";
        if (empty($tipo)) $erros_para_sessao[] = "Tipo do evento é obrigatório.";
        if (!empty($horario_abertura) && !empty($horario_fechamento) && strtotime($horario_fechamento) <= strtotime($horario_abertura)) {
            $erros_para_sessao[] = "O horário de fechamento deve ser posterior ao horário de abertura.";
        }


        if (empty($erros_para_sessao)) { // Somente prossegue se não houver erros de validação dos campos principais
            $sql = "INSERT INTO evento_cultural (id_cidade, nome, descricao, horario_abertura, horario_fechamento, local_evento, tipo, taxaentrada) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("issssssd", $id_cidade, $nome, $descricao, $horario_abertura, $horario_fechamento, $local_evento, $tipo, $taxaentrada);
                if ($stmt->execute()) {
                    $evento_id_inserido = $stmt->insert_id;
                    $feedback_para_sessao = "<p class='feedback-success'>Evento cultural '" . htmlspecialchars($nome) . "' cadastrado com sucesso!</p>";
                    processarUploads($mysqli, $evento_id_inserido, 'evento_cultural', 'imagens_evento', $erros_para_sessao);
                } else {
                    $erros_para_sessao[] = "Erro ao cadastrar evento cultural: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $erros_para_sessao[] = "Erro ao preparar query (evento): " . $mysqli->error;
            }
        }
    }

    // Armazenar feedback na sessão para o PRG
    if (!empty($erros_para_sessao)) {
        // Se houve um feedback de sucesso E erros (provavelmente de uploads), mescla
        if (!empty($feedback_para_sessao)) {
            $feedback_para_sessao .= " No entanto, ocorreram problemas adicionais:<ul>";
            foreach($erros_para_sessao as $erro_item) {
                $feedback_para_sessao .= "<li>" . htmlspecialchars($erro_item) . "</li>";
            }
            $feedback_para_sessao .= "</ul>";
            $_SESSION['prg_feedback_msg'] = "<div class='feedback-warning'>" . $feedback_para_sessao . "</div>"; // Use warning se sucesso parcial
        } else {
            $_SESSION['prg_errors'] = $erros_para_sessao;
        }
    } elseif (!empty($feedback_para_sessao)) {
        $_SESSION['prg_feedback_msg'] = $feedback_para_sessao; // Já contém a tag <p class='feedback-success'>
    }

    $_SESSION['prg_active_main_tab'] = 'cadastros';
    $_SESSION['prg_active_sub_tab'] = $sub_aba_destino;

    header("Location: cliente.php#cadastros", true, 303);
    exit();
}
// --- FIM: Lógica de Cadastro com PRG ---
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cliente</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cliente.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <header class="barra">
        <div class="esquerda">
            <a href="../Menu-inicial-cliente/Menu.php" class="logo-link">
                <div class="logo">V</div>
            </a>
            <input type="text" placeholder="Pesquisar eventos, shows, teatros, turismo..." class="search-bar">
        </div>
        <div class="direita">
            <a href="../Mapa/mapa.html">
                <button class="map-btn" type="button"><i class="fa-regular fa-image"></i> Mapa</button>
            </a>
            <button type="button" class="user-btn" onclick="toggleSidebar()" aria-expanded="false" aria-controls="sidebar">
                <i class="fa-solid fa-bars icon-space"></i>
                <i class="fa-regular fa-user"></i>
            </button>
        </div>
    </header>

    <aside id="sidebar" class="sidebar">
        <h2 id="sidebar-username"><?php echo htmlspecialchars($usuario_nome_atual); ?></h2>
        <ul>
            <li><a href="../Menu-inicial-cliente/Menu.php">Inicio</a></li>
            <li><a href="cliente.php">Conta</a></li>
            <li><a href="#">Configurações</a></li>
            <li><a href="../logout.php">Desconectar</a></li>
        </ul>
    </aside>
    <div id="overlay" class="overlay" style="display: none;"></div>

    <main>
        <div class="container">
            <div class="tab-container">
                <div class="tabs">
                    <button class="tab-btn <?php echo ($prg_active_main_tab === 'conta') ? 'active' : ''; ?>" data-tab="conta" aria-selected="<?php echo ($prg_active_main_tab === 'conta') ? 'true' : 'false'; ?>">Conta</button>
                    <button class="tab-btn <?php echo ($prg_active_main_tab === 'cadastros') ? 'active' : ''; ?>" data-tab="cadastros" id="cadastros-tab-button" aria-selected="<?php echo ($prg_active_main_tab === 'cadastros') ? 'true' : 'false'; ?>">Cadastros</button>
                </div>

                <div id="conta" class="tab-content <?php echo ($prg_active_main_tab === 'conta') ? 'active' : ''; ?>">
                    <h2 class="profile-heading">Configurações de perfil</h2>
                    <p class="profile-subheading">Alterar detalhes de identificação da sua conta</p>

                    <div class="form-group">
                        <label class="form-label" for="input-usuario">Usuário</label>
                        <div class="form-input-container">
                            <input type="text" id="input-usuario" class="form-input" value="<?php echo htmlspecialchars($usuario_nome_atual); ?>" readonly data-field="nome">
                            <button class="edit-btn" type="button" aria-label="Editar nome de usuário">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                        <small class="form-feedback" aria-live="polite"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="input-email">E-mail</label>
                        <div class="form-input-container">
                            <input type="email" id="input-email" class="form-input" value="<?php echo htmlspecialchars($usuario_email_atual); ?>" readonly data-field="email">
                            <button class="edit-btn" type="button" aria-label="Editar e-mail">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 3a2.85 2.85 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                            </button>
                        </div>
                        <small class="form-feedback" aria-live="polite"></small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Senha</label>
                        <div class="form-input-container">
                            <p class="password-text">
                                <a href="#/alterar-senha" class="password-link">Altere sua senha</a>. Aumente sua segurança com uma senha forte.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <div id="cadastros" class="tab-content <?php echo ($prg_active_main_tab === 'cadastros') ? 'active' : ''; ?>">
                    <h2 class="profile-heading">Formulário de Cadastro</h2>
                    <p class="profile-subheading">Cadastre novos Eventos e pontos turísticos</p>

                    <?php
                    if (!empty($prg_feedback_msg)) {
                        echo "<div class='cadastro-feedback-area'>" . $prg_feedback_msg . "</div>";
                    }
                    if (!empty($prg_errors)) {
                        echo "<div class='cadastro-feedback-area feedback-error'><strong>Ocorreram os seguintes erros:</strong><ul>";
                        foreach ($prg_errors as $erro_item) {
                            echo "<li>" . htmlspecialchars($erro_item) . "</li>";
                        }
                        echo "</ul></div>";
                    }
                    ?>

                    <div class="tabs-secondary">
                        <button class="tab-secondary-btn <?php echo ($prg_active_sub_tab === 'form-cidades') ? 'active' : ''; ?>" data-tab="form-cidades" aria-selected="<?php echo ($prg_active_sub_tab === 'form-cidades') ? 'true' : 'false'; ?>">Cidade</button>
                        <button class="tab-secondary-btn <?php echo ($prg_active_sub_tab === 'form-pontos') ? 'active' : ''; ?>" data-tab="form-pontos" aria-selected="<?php echo ($prg_active_sub_tab === 'form-pontos') ? 'true' : 'false'; ?>">Pontos turísticos</button>
                        <button class="tab-secondary-btn <?php echo ($prg_active_sub_tab === 'form-evento') ? 'active' : ''; ?>" data-tab="form-evento" aria-selected="<?php echo ($prg_active_sub_tab === 'form-evento') ? 'true' : 'false'; ?>">Eventos</button>
                    </div>

                    <div id="form-cidades" class="tab-secondary-content <?php echo ($prg_active_sub_tab === 'form-cidades') ? 'active' : ''; ?>">
                        <div class="form-container">
                            <form id="cidade-form" class="cadastro-form" method="post" action="cliente.php">
                                <input type="hidden" name="form_action" value="cadastrar_cidade">
                                <div class="form-row">
                                    <label for="nome-cidade" class="cadastro-label">Nome da Cidade <span class="required">*</span></label>
                                    <input type="text" id="nome-cidade" name="nome" class="cadastro-input" placeholder="Ex: Rio de Janeiro" required>
                                </div>
                                <div class="form-row">
                                    <label for="estado-cidade" class="cadastro-label">Estado <span class="required">*</span></label>
                                    <input type="text" id="estado-cidade" name="estado" class="cadastro-input" placeholder="Ex: RJ" required>
                                </div>
                                <div class="form-row">
                                    <label for="pais-cidade" class="cadastro-label">País <span class="required">*</span></label>
                                    <input type="text" id="pais-cidade" name="pais" class="cadastro-input" placeholder="Ex: Brasil" required>
                                </div>
                                <div class="form-row">
                                    <label for="descricao-cidade" class="cadastro-label">Descrição</label>
                                    <textarea id="descricao-cidade" name="descricao" class="cadastro-input" placeholder="Breve descrição sobre a cidade"></textarea>
                                </div>
                                <div class="form-row">
                                    <label for="latitude-cidade" class="cadastro-label">Latitude</label>
                                    <input type="number" step="any" id="latitude-cidade" name="latitude" class="cadastro-input" placeholder="Ex: -22.9068">
                                </div>
                                <div class="form-row">
                                    <label for="longitude-cidade" class="cadastro-label">Longitude</label>
                                    <input type="number" step="any" id="longitude-cidade" name="longitude" class="cadastro-input" placeholder="Ex: -43.1729">
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
                                    <label for="ponto-turistico" class="cadastro-label">Ponto Turístico <span class="required">*</span></label>
                                    <input type="text" id="ponto-turistico" name="nome" class="cadastro-input" placeholder="Ex: Cristo Redentor" required>
                                </div>
                                <div class="form-row">
                                    <label for="id_cidade-ponto" class="cadastro-label">Cidade <span class="required">*</span></label>
                                    <select id="id_cidade-ponto" name="id_cidade" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione a cidade</option>
                                        <?php
                                        $query_cidades_select_ponto = "SELECT id, nome, estado FROM cidade ORDER BY nome ASC";
                                        $result_cidades_select_ponto = mysqli_query($mysqli, $query_cidades_select_ponto);
                                        if ($result_cidades_select_ponto) {
                                            while ($cidade_select_item = mysqli_fetch_assoc($result_cidades_select_ponto)) {
                                                echo "<option value='" . htmlspecialchars($cidade_select_item['id']) . "'>" . htmlspecialchars($cidade_select_item['nome']) . " - " . htmlspecialchars($cidade_select_item['estado']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="descricao-ponto" class="cadastro-label">Descrição</label>
                                    <textarea id="descricao-ponto" name="descricao" class="cadastro-input" placeholder="Detalhes sobre o ponto turístico"></textarea>
                                </div>
                                <div class="form-row">
                                    <label for="tipo-ponto" class="cadastro-label">Tipo <span class="required">*</span></label>
                                    <select id="tipo-ponto" name="tipo" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione o tipo</option>
                                        <option value="Monumento">Monumento</option>
                                        <option value="Museu">Museu</option>
                                        <option value="Parque">Parque</option>
                                        <option value="Praia">Praia</option>
                                        <option value="Restaurante">Restaurante</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="endereco-ponto" class="cadastro-label">Endereço/Localização</label>
                                    <input type="text" id="endereco-ponto" name="endereco" class="cadastro-input" placeholder="Rua, Número, Bairro">
                                </div>
                                <div class="form-row">
                                    <label for="latitude-ponto" class="cadastro-label">Latitude</label>
                                    <input type="number" step="any" id="latitude-ponto" name="latitude" class="cadastro-input" placeholder="Ex: -22.9519">
                                </div>
                                <div class="form-row">
                                    <label for="longitude-ponto" class="cadastro-label">Longitude</label>
                                    <input type="number" step="any" id="longitude-ponto" name="longitude" class="cadastro-input" placeholder="Ex: -43.2105">
                                </div>
                                <div class="form-row">
                                    <label for="horario-abertura-ponto" class="cadastro-label">Horário de Abertura</label>
                                    <input type="time" id="horario-abertura-ponto" name="horario_abertura" class="cadastro-input">
                                </div>
                                <div class="form-row">
                                    <label for="horario-fechamento-ponto" class="cadastro-label">Horário de Fechamento</label>
                                    <input type="time" id="horario-fechamento-ponto" name="horario_fechamento" class="cadastro-input">
                                </div>
                                <div class="form-row">
                                    <label for="taxaentrada-ponto" class="cadastro-label">Preço/Entrada (R$)</label>
                                    <input type="number" step="0.01" id="taxaentrada-ponto" name="taxaentrada" class="cadastro-input" placeholder="Ex: 25.50">
                                </div>
                                <div class="form-row">
                                    <label class="cadastro-label">Imagens do Ponto Turístico</label>
                                    <div class="file-upload-area">
                                        <input type="file" id="imagens-ponto" name="imagens_ponto[]" multiple accept="image/png, image/jpeg, image/gif" class="file-input">
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
                                    <input type="text" id="nome-evento" name="nome" class="cadastro-input" placeholder="Ex: Show da Virada" required>
                                </div>
                                <div class="form-row">
                                     <label for="id_cidade-evento" class="cadastro-label">Cidade <span class="required">*</span></label>
                                    <select id="id_cidade-evento" name="id_cidade" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione a cidade</option>
                                        <?php
                                        // Reutilizar a query e resultado do select de cidades anterior, se possível, ou refazer
                                        // Se $result_cidades_select_ponto foi definido e é válido:
                                        if (isset($result_cidades_select_ponto) && $result_cidades_select_ponto) {
                                            mysqli_data_seek($result_cidades_select_ponto, 0); // Resetar o ponteiro do resultado
                                            while ($cidade_select_item = mysqli_fetch_assoc($result_cidades_select_ponto)) {
                                                echo "<option value='" . htmlspecialchars($cidade_select_item['id']) . "'>" . htmlspecialchars($cidade_select_item['nome']) . " - " . htmlspecialchars($cidade_select_item['estado']) . "</option>";
                                            }
                                        } else { // Fallback se a query anterior não foi executada ou falhou
                                            $query_cidades_fb = "SELECT id, nome, estado FROM cidade ORDER BY nome ASC";
                                            $result_cidades_fb = mysqli_query($mysqli, $query_cidades_fb);
                                            if($result_cidades_fb){
                                                while ($cidade_fb_item = mysqli_fetch_assoc($result_cidades_fb)) {
                                                    echo "<option value='" . htmlspecialchars($cidade_fb_item['id']) . "'>" . htmlspecialchars($cidade_fb_item['nome']) . " - " . htmlspecialchars($cidade_fb_item['estado']) . "</option>";
                                                }
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="descricao-evento" class="cadastro-label">Descrição do Evento</label>
                                    <textarea id="descricao-evento" name="descricao" class="cadastro-input" placeholder="Detalhes sobre o evento"></textarea>
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
                                    <input type="text" id="local-evento" name="local_evento" class="cadastro-input" placeholder="Ex: Sambódromo" required>
                                </div>
                                <div class="form-row">
                                    <label for="tipo-evento" class="cadastro-label">Tipo <span class="required">*</span></label>
                                    <select id="tipo-evento" name="tipo" class="cadastro-input styled-select" required>
                                        <option value="" disabled selected>Selecione o tipo</option>
                                        <option value="FestasShows">Festas e Shows</option>
                                        <option value="Passeios">Passeios</option>
                                        <option value="Restaurantes">Restaurantes</option>
                                        <option value="StandUpComédia">Stand Up e Comédia</option>
                                        <option value="Esportes">Esportes</option>
                                        <option value="CursosWorkshop">Cursos e Workshop</option>
                                        <option value="TeatrosEspetáculos">Teatros e Espetáculos</option>
                                        <option value="Infantil">Infantil</option>
                                        <option value="CongressosPalestras">Congressos e Palestras</option>
                                        <option value="Outro">Outro</option>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <label for="taxa-evento" class="cadastro-label">Taxa de Entrada (R$)</label>
                                    <input type="number" step="0.01" id="taxa-evento" name="taxaentrada" class="cadastro-input" placeholder="Ex: 50.00 ou deixe em branco se gratuito">
                                </div>
                                <div class="form-row">
                                    <label class="cadastro-label">Imagens do Evento</label>
                                    <div class="file-upload-area">
                                        <input type="file" id="imagens-evento" name="imagens_evento[]" multiple accept="image/png, image/jpeg, image/gif" class="file-input">
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
                </div> </div> </div> </main>
    <script src="cliente.js"></script> <script>
        // Script para garantir que a aba e sub-aba corretas sejam ativadas no carregamento da página
        document.addEventListener('DOMContentLoaded', () => {
            const abaPrincipalAtivaPHP = '<?php echo $prg_active_main_tab; ?>';
            const subAbaAtivaPHP = '<?php echo $prg_active_sub_tab; ?>';

            // Ativa a aba principal correta
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

            // Se a aba principal ativa for "cadastros", ativa a sub-aba correta
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

            // Rolar para a âncora se presente na URL (após o redirect)
            if (window.location.hash && window.location.hash === '#cadastros') {
                const cadastrosSection = document.getElementById('cadastros');
                if (cadastrosSection) {
                    setTimeout(() => {
                        // Uma forma mais suave de rolar, ou usar o scrollIntoView
                        const yOffset = -70; // Ajuste para compensar a altura da barra de navegação fixa, se houver
                        const y = cadastrosSection.getBoundingClientRect().top + window.pageYOffset + yOffset;
                        window.scrollTo({top: y, behavior: 'smooth'});
                        // cadastrosSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100); // Pequeno delay para garantir que o layout esteja estável
                }
            }
        });
    </script>
</body>
</html>