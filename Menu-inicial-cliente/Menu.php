<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['aviso_boas_vindas'])) {
    echo "<div style='padding: 15px; background-color: #d4edda; color: #155724; text-align: center; border-bottom: 1px solid #c3e6cb;'>" . htmlspecialchars($_SESSION['aviso_boas_vindas']) . "</div>";
    unset($_SESSION['aviso_boas_vindas']);
}

require('../protect.php'); 
require('../conexao.php');

// Pega o nome e tipo do usuário da sessão
$nome_usuario_logado = $_SESSION['nome'] ?? 'Usuário';
$tipo_usuario_logado = $_SESSION['tipo'] ?? 'cliente'; // Padrão para cliente se não definido

// --- Lógica para buscar Cidades, Estados e Tipos para os filtros ---
$cidades_disponiveis = [];
$sql_cidades = "SELECT id, nome, estado FROM cidade ORDER BY nome ASC";
$result_cidades = $mysqli->query($sql_cidades);
if ($result_cidades) {
    while ($row = $result_cidades->fetch_assoc()) {
        $cidades_disponiveis[] = $row;
    }
} else {
    error_log("Erro ao buscar cidades: " . $mysqli->error);
}

$estados_disponiveis = [];
$sql_estados = "SELECT DISTINCT estado FROM cidade ORDER BY estado ASC";
$result_estados = $mysqli->query($sql_estados);
if ($result_estados) {
    while ($row = $result_estados->fetch_assoc()) {
        $estados_disponiveis[] = $row['estado'];
    }
} else {
    error_log("Erro ao buscar estados: " . $mysqli->error);
}

$tipos_disponiveis = [];
$sql_tipos_pontos = "SELECT DISTINCT tipo FROM ponto_turistico ORDER BY tipo ASC";
$result_tipos_pontos = $mysqli->query($sql_tipos_pontos);
if ($result_tipos_pontos) {
    while ($row = $result_tipos_pontos->fetch_assoc()) {
        $tipos_disponiveis[] = $row['tipo'];
    }
} else {
    error_log("Erro ao buscar tipos de pontos turísticos: " . $mysqli->error);
}

$sql_tipos_eventos = "SELECT DISTINCT tipo FROM evento_cultural ORDER BY tipo ASC";
$result_tipos_eventos = $mysqli->query($sql_tipos_eventos);
if ($result_tipos_eventos) {
    while ($row = $result_tipos_eventos->fetch_assoc()) {
        if (!in_array($row['tipo'], $tipos_disponiveis)) {
            $tipos_disponiveis[] = $row['tipo'];
        }
    }
} else {
    error_log("Erro ao buscar tipos de eventos culturais: " . $mysqli->error);
}
sort($tipos_disponiveis); 


// --- Captura de parâmetros dos filtros ---
$filtro_cidade_id = $_GET['cidade'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_data = $_GET['data'] ?? ''; 

// Converte a data do formato "Dia, DD de Mês de AAAA" para "AAAA-MM-DD" para o SQL
$data_sql = null;
if (!empty($filtro_data)) {
    try {
        // Assegura que a extensão intl está habilitada no PHP para IntlDateFormatter
        if (extension_loaded('intl')) {
            $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE);
            $formatter->setPattern("EEE, dd 'de' MMMM 'de' yyyy");
            $timestamp = $formatter->parse($filtro_data);
            if ($timestamp !== false) {
                $data_sql = date('Y-m-d', $timestamp);
            }
        } else {
            error_log("Extensão 'intl' do PHP não está habilitada. O filtro de data pode não funcionar corretamente.");
            // Fallback simples se intl não estiver disponível (menos robusto)
            $date_parts = explode(' de ', str_replace(['Seg, ', 'Ter, ', 'Qua, ', 'Qui, ', 'Sex, ', 'Sáb, ', 'Dom, '], '', $filtro_data));
            if (count($date_parts) == 3) {
                $day = $date_parts[0];
                $month_name = $date_parts[1];
                $year = $date_parts[2];
                $month_num = date('m', strtotime("$month_name 1")); // Converte nome do mês para número
                $data_sql = sprintf("%04d-%02d-%02d", $year, $month_num, $day);
            }
        }
    } catch (Exception $e) {
        error_log("Erro ao parsear data do filtro: " . $e->getMessage());
        $data_sql = null;
    }
}

// Inicializa as cláusulas WHERE
$where_pontos = ["pt.status = 'aprovado'"];
$where_eventos = ["ec.status = 'aprovado'", "ec.horario_fechamento >= NOW()"];
$params_pontos = [];
$types_pontos = "";
$params_eventos = [];
$types_eventos = "";

// Adiciona filtros para Pontos Turísticos
if (!empty($filtro_cidade_id)) {
    $where_pontos[] = "pt.id_cidade = ?";
    $params_pontos[] = $filtro_cidade_id;
    $types_pontos .= "i";
}
if (!empty($filtro_tipo)) {
    $where_pontos[] = "pt.tipo = ?";
    $params_pontos[] = $filtro_tipo;
    $types_pontos .= "s";
}

// Adiciona filtros para Eventos Culturais
if (!empty($filtro_cidade_id)) {
    $where_eventos[] = "ec.id_cidade = ?";
    $params_eventos[] = $filtro_cidade_id;
    $types_eventos .= "i";
}

if (!empty($filtro_estado)) {
    $where_eventos[] = "c.estado = ?";
    $params_eventos[] = $filtro_estado;
    $types_eventos .= "s";
}
if (!empty($filtro_tipo)) {
    $where_eventos[] = "ec.tipo = ?";
    $params_eventos[] = $filtro_tipo;
    $types_eventos .= "s";
}
if (!empty($data_sql)) {
    $where_eventos[] = "(DATE(ec.horario_abertura) <= ? AND (ec.horario_fechamento IS NULL OR DATE(ec.horario_fechamento) >= ?))";
    $params_eventos[] = $data_sql;
    $params_eventos[] = $data_sql; // Adiciona a data duas vezes para as duas comparações
    $types_eventos .= "ss";      // 'ss' porque são duas strings de data
}

// Constrói as cláusulas WHERE
$where_clause_pontos = count($where_pontos) > 0 ? " WHERE " . implode(" AND ", $where_pontos) : "";
$where_clause_eventos = count($where_eventos) > 0 ? " WHERE " . implode(" AND ", $where_eventos) : "";


// Buscar Pontos Turísticos Aprovados
$pontos_turisticos_aprovados = [];
$sql_pontos = "SELECT pt.id, pt.nome, pt.descricao, pt.tipo, c.nome AS nome_cidade,
                (SELECT m.url_arquivo FROM midia m WHERE m.id_ponto_turistico = pt.id ORDER BY m.id ASC LIMIT 1) as imagem_url
                FROM ponto_turistico pt
                JOIN cidade c ON pt.id_cidade = c.id"
                . $where_clause_pontos .
                " ORDER BY pt.id DESC LIMIT 10";

$stmt_pontos = $mysqli->prepare($sql_pontos);
if ($stmt_pontos === false) {
    error_log("Erro ao preparar statement para pontos turísticos: " . $mysqli->error);
} else {
    if (!empty($params_pontos)) {
        $stmt_pontos->bind_param($types_pontos, ...$params_pontos);
    }
    $stmt_pontos->execute();
    $result_pontos = $stmt_pontos->get_result();

    if ($result_pontos) {
        while ($row = $result_pontos->fetch_assoc()) {
            if (!empty($row['imagem_url']) && strpos($row['imagem_url'], 'http') !== 0) {
                $row['imagem_url'] = '../' . ltrim($row['imagem_url'], '/');
            } elseif (empty($row['imagem_url'])) {
                $row['imagem_url'] = '../placeholder_geral.jpg';
            }
            $pontos_turisticos_aprovados[] = $row;
        }
    } else {
        error_log("Erro ao buscar pontos turísticos: " . $mysqli->error);
    }
    $stmt_pontos->close();
}


// Buscar Eventos Culturais Aprovados
$eventos_culturais_aprovados = [];
$sql_eventos = "SELECT ec.id, ec.nome, ec.descricao, ec.horario_abertura, ec.local_evento, c.nome AS nome_cidade,
                (SELECT m.url_arquivo FROM midia m WHERE m.id_evento_cultural = ec.id ORDER BY m.id ASC LIMIT 1) as imagem_url
                FROM evento_cultural ec
                JOIN cidade c ON ec.id_cidade = c.id"
                . $where_clause_eventos .
                " ORDER BY ec.horario_abertura ASC LIMIT 10";

$stmt_eventos = $mysqli->prepare($sql_eventos);
if ($stmt_eventos === false) {
    error_log("Erro ao preparar statement para eventos culturais: " . $mysqli->error);
} else {
    if (!empty($params_eventos)) {
        $stmt_eventos->bind_param($types_eventos, ...$params_eventos);
    }
    $stmt_eventos->execute();
    $result_eventos = $stmt_eventos->get_result();

    if ($result_eventos) {
        while ($row = $result_eventos->fetch_assoc()) {
             if (!empty($row['imagem_url']) && strpos($row['imagem_url'], 'http') !== 0) {
                $row['imagem_url'] = '../' . ltrim($row['imagem_url'], '/');
            } elseif (empty($row['imagem_url'])) {
                $row['imagem_url'] = '../placeholder_geral.jpg';
            }
            $eventos_culturais_aprovados[] = $row;
        }
    } else {
        error_log("Erro ao buscar eventos culturais: " . $mysqli->error);
    }
    $stmt_eventos->close();
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vibra - Eventos e Turismo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="reset.css">
    <link rel="stylesheet" href="Menu.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<header class="barra">
    <div class="left-section">
        <a href="Menu.php" class="logo-link">
            <div class="logo">V</div>
        </a>
        <form action="pesquisar_resultados.php" method="GET" class="search-form">
            <input type="text" name="termo_pesquisa" placeholder="Pesquisar eventos, shows, teatros, turismo..." class="search-bar">
            <button type="submit" style="display: none;"></button>
        </form>
    </div>
    
    <div class="right-section">
        <a href="../Mapa/mapa.php">
            <button class="map-btn" type="button">
                <i class="fa-regular fa-image"></i> Mapa
            </button>
        </a>
        <button type="button" class="user-btn" onclick="toggleClienteSidebar()">
            <i class="fa-solid fa-bars icon-space"></i>
            <i class="fa-regular fa-user"></i>
        </button>
    </div>
</header>
    
<aside id="sidebar" class="sidebar">
    <h2><?php echo htmlspecialchars($nome_usuario_logado); ?></h2>
    <ul>
        <li><a href="Menu.php">Inicio</a></li>
        <li><a href="../Cliente/cliente.php">Minha Conta & Cadastros</a></li>
        <?php if ($tipo_usuario_logado === 'admin'): ?>
            <li><a href="../ADM/adm.php" style="color: #c9302c; font-weight: bold;">Painel Admin</a></li>
        <?php endif; ?>
        <li><a href="../Tela de perfil de usuario/perfil.php">Perfil</a></li>
        <li><a href="../logout.php">Desconectar</a></li>
    </ul>
</aside>
<div id="overlay" class="overlay" style="display: none;"></div>

<section class="filters-section1">
    <form action="Menu.php" method="GET" class="filters-section">
        <div class="filter-group">
            <span class="icon">📍</span>
            <select id="location-city" name="cidade" class="filter-select">
                <option value="" <?php echo empty($filtro_cidade_id) ? 'selected' : ''; ?>>Todas as Cidades</option>
                <?php foreach ($cidades_disponiveis as $cidade): ?>
                    <option value="<?php echo htmlspecialchars($cidade['id']); ?>"
                        <?php echo ($filtro_cidade_id == $cidade['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cidade['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <span class="icon">🌍</span>
            <select id="location-state" name="estado" class="filter-select">
                <option value="" <?php echo empty($filtro_estado) ? 'selected' : ''; ?>>Todos os Estados</option>
                <?php foreach ($estados_disponiveis as $estado): ?>
                    <option value="<?php echo htmlspecialchars($estado); ?>"
                        <?php echo ($filtro_estado == $estado) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($estado); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <span class="icon" id="calendar-icon">🗓️</span>
            <input type="text" id="event-date" name="data" class="filter-calendar" placeholder="Data" readonly
                   value="<?php echo htmlspecialchars($filtro_data); ?>">

            <div class="calendar-popup" id="calendar-popup">
                <div class="calendar-header">
                    <button type="button" class="nav-btn" onclick="changeMonth(-1, event)">‹</button>
                    <div class="month-year" id="month-year"></div>
                    <button type="button" class="nav-btn" onclick="changeMonth(1, event)">›</button>
                </div>
                <div class="calendar-grid">
                    <div class="weekdays">
                        <div class="weekday">Dom</div>
                        <div class="weekday">Seg</div>
                        <div class="weekday">Ter</div>
                        <div class="weekday">Qua</div>
                        <div class="weekday">Qui</div>
                        <div class="weekday">Sex</div>
                        <div class="weekday">Sáb</div>
                    </div>
                    <div class="days-grid" id="days-grid"></div>
                </div>
            </div>
        </div>
        <div class="filter-group">
            <span class="icon">🎫</span>
            <select id="event-type" name="tipo" class="filter-select">
                <option value="" <?php echo empty($filtro_tipo) ? 'selected' : ''; ?>>Todos os Tipos</option>
                <?php foreach ($tipos_disponiveis as $tipo_opcao) : ?>
                    <option value="<?php echo htmlspecialchars($tipo_opcao); ?>"
                        <?php echo ($filtro_tipo == $tipo_opcao) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo_opcao); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <button type="submit" class="apply-filters-button">Aplicar Filtros</button>
        </div>
    </form>
</section>
<main class="eventos">
    <?php if (!empty($pontos_turisticos_aprovados)): ?>
    <div class="section" id="pontos-turisticos">
        <div class="section-header">
            <h2 class="section-title">Pontos Turísticos em Destaque</h2>
            <div class="nav-buttons">
                <button class="nav-button" aria-label="Anterior" onclick="scrollItemContainer('pontos-destaque-container', -1)">&lt;</button>
                <button class="nav-button" aria-label="Próximo" onclick="scrollItemContainer('pontos-destaque-container', 1)">&gt;</button>
            </div>
        </div>
        <div class="event-container" id="pontos-destaque-container">
            <?php foreach ($pontos_turisticos_aprovados as $ponto): ?>
            <a href="../Eventos-Detalhes/detalhes.php?tipo=ponto&id=<?php echo $ponto['id']; ?>" class="event-link">
                <div class="event-card">
                    <div class="event-image" style="background-image: url('<?php echo htmlspecialchars($ponto['imagem_url']); ?>');">
                        <?php if (strpos($ponto['imagem_url'], 'placeholder_geral.jpg') !== false): ?>
                            <span>Sem Imagem</span>
                        <?php endif; ?>
                    </div>
                    <div class="event-details">
                        <div class="event-title"><?php echo htmlspecialchars($ponto['nome']); ?></div>
                        <div class="event-description"><?php echo htmlspecialchars(mb_substr($ponto['descricao'] ?? '', 0, 60)) . (mb_strlen($ponto['descricao'] ?? '') > 60 ? '...' : ''); ?></div>
                        <div class="event-info"><?php echo htmlspecialchars($ponto['nome_cidade']); ?> - <?php echo htmlspecialchars($ponto['tipo']); ?></div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($eventos_culturais_aprovados)): ?>
    <div class="section" id="eventos-culturais">
        <div class="section-header">
            <h2 class="section-title">Próximos Eventos</h2>
            <div class="nav-buttons">
                <button class="nav-button" aria-label="Anterior" onclick="scrollItemContainer('eventos-proximos-container', -1)">&lt;</button>
                <button class="nav-button" aria-label="Próximo" onclick="scrollItemContainer('eventos-proximos-container', 1)">&gt;</button>
            </div>
        </div>
        <div class="event-container" id="eventos-proximos-container">
            <?php foreach ($eventos_culturais_aprovados as $evento): ?>
            <a href="../Eventos-Detalhes/detalhes.php?tipo=evento&id=<?php echo $evento['id']; ?>" class="event-link">
                <div class="event-card">
                   <div class="event-image" style="background-image: url('<?php echo htmlspecialchars($evento['imagem_url']); ?>');">
                        <?php if (strpos($evento['imagem_url'], 'placeholder_geral.jpg') !== false): ?>
                            <span>Sem Imagem</span>
                        <?php endif; ?>
                    </div>
                    <div class="event-details">
                        <div class="event-title"><?php echo htmlspecialchars($evento['nome']); ?></div>
                        <div class="event-description"><?php echo htmlspecialchars(mb_substr($evento['descricao'] ?? '', 0, 60)) . (mb_strlen($evento['descricao'] ?? '') > 60 ? '...' : ''); ?></div>
                        <div class="event-info">
                            <?php
                            try {
                                $dataEvento = new DateTime($evento['horario_abertura']);
                                echo htmlspecialchars($dataEvento->format('d/m/Y \à\s H:i'));
                            } catch (Exception $e) {
                                echo htmlspecialchars($evento['horario_abertura']);
                            }
                            echo " - " . htmlspecialchars($evento['local_evento']);
                            ?>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($pontos_turisticos_aprovados) && empty($eventos_culturais_aprovados)): ?>
        <div class="section" style="text-align: center; padding: 40px;">
            <p>Nenhum ponto turístico ou evento aprovado para exibição no momento.</p>
            <p>Explore as categorias ou volte mais tarde!</p>
        </div>
    <?php endif; ?>

</main>
<script src="Menu.js"></script>
<script>
    function scrollItemContainer(containerId, direction) {
        const container = document.getElementById(containerId);
        if (container) {
            const card = container.querySelector('.event-card');
            if (card) {
                const cardWidth = card.offsetWidth;
                const gap = parseFloat(window.getComputedStyle(container).gap) || 20;
                const scrollAmount = cardWidth + gap;
                container.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
            }
        }
    }

    window.toggleClienteSidebar = function() {
        const sidebarCliente = document.getElementById("sidebar");
        const overlayCliente = document.getElementById("overlay");
        const userBtnCliente = document.querySelector(".user-btn");

        if (sidebarCliente) {
            const isVisible = sidebarCliente.classList.toggle("visible");
            if(userBtnCliente) userBtnCliente.setAttribute('aria-expanded', isVisible.toString());
            if (overlayCliente) overlayCliente.style.display = isVisible ? "block" : "none";
        }
    };

    const overlayGlobal = document.getElementById('overlay');
    const menuCategoriaGlobal = document.getElementById('menuCategoria');
    const btnCategoriaGlobal = document.getElementById('btnCategoria');

     if (overlayGlobal) {
        overlayGlobal.addEventListener("click", () => {
            const sidebarCliente = document.getElementById("sidebar");
            if (sidebarCliente && sidebarCliente.classList.contains("visible")) {
                 sidebarCliente.classList.remove("visible");
                 const userBtn = document.querySelector(".user-btn");
                 if(userBtn) userBtn.setAttribute('aria-expanded', 'false');
                 overlayGlobal.style.display = "none";
            }
            if (menuCategoriaGlobal && menuCategoriaGlobal.style.display !== 'none') {
                 menuCategoriaGlobal.style.display = 'none';
                 overlayGlobal.style.display = 'none'; 
            }
        });
    }
     if (btnCategoriaGlobal && menuCategoriaGlobal && overlayGlobal) {
        btnCategoriaGlobal.addEventListener('click', (event) => {
            event.stopPropagation();
            const isMenuVisible = menuCategoriaGlobal.style.display === 'grid';
            menuCategoriaGlobal.style.display = isMenuVisible ? 'none' : 'grid';
            overlayGlobal.style.display = menuCategoriaGlobal.style.display === 'grid' ? 'block' : 'none';
        });
    }
</script>
<?php if(isset($mysqli)) $mysqli->close(); ?>
</body>
</html>
