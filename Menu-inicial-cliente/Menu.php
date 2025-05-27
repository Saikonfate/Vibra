<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../protect.php'); 
require('../conexao.php');

// Pega o nome e tipo do usuário da sessão
$nome_usuario_logado = $_SESSION['nome'] ?? 'Usuário';
$tipo_usuario_logado = $_SESSION['tipo'] ?? 'cliente'; // Padrão para cliente se não definido

// Buscar Pontos Turísticos Aprovados
$pontos_turisticos_aprovados = [];
$sql_pontos = "SELECT pt.id, pt.nome, pt.descricao, pt.tipo, c.nome AS nome_cidade,
                (SELECT m.url_arquivo FROM midia m WHERE m.id_ponto_turistico = pt.id ORDER BY m.id ASC LIMIT 1) as imagem_url
               FROM ponto_turistico pt
               JOIN cidade c ON pt.id_cidade = c.id
               WHERE pt.status = 'aprovado'
               ORDER BY pt.id DESC LIMIT 10";
$result_pontos = $mysqli->query($sql_pontos);
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

// Buscar Eventos Culturais Aprovados
$eventos_culturais_aprovados = [];
$sql_eventos = "SELECT ec.id, ec.nome, ec.descricao, ec.horario_abertura, ec.local_evento, c.nome AS nome_cidade,
                (SELECT m.url_arquivo FROM midia m WHERE m.id_evento_cultural = ec.id ORDER BY m.id ASC LIMIT 1) as imagem_url
                FROM evento_cultural ec
                JOIN cidade c ON ec.id_cidade = c.id
                WHERE ec.status = 'aprovado'
                ORDER BY ec.horario_abertura DESC LIMIT 10";
$result_eventos = $mysqli->query($sql_eventos);
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

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vibra - Eventos e Turismo</title>
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
    <section class="filters-section">
        <div class="filter-group">
            <span class="icon">📍</span>
            <select id="location-city" class="filter-select">
                <option value="">Juazeiro do Norte</option>
                <option value="crato">Crato</option>
                <option value="barbalha">Barbalha</option>
            </select>
        </div>

        <div class="filter-group">
            <span class="icon">🌍</span>
            <select id="location-state" class="filter-select">
                <option value="">Ceará</option>
                <option value="pernambuco">Pernambuco</option>
                <option value="rio-grande-do-norte">Rio Grande do Norte</option>
            </select>
        </div>
        <div class="filter-group">
            <span class="icon" id="calendar-icon">🗓️</span>
            <input type="text" id="event-date" class="filter-calendar" placeholder="Data" readonly>

            <div class="calendar-popup" id="calendar-popup">
                <div class="calendar-header">
                    <button class="nav-btn" onclick="changeMonth(-1)">‹</button>
                    <div class="month-year" id="month-year"></div>
                    <button class="nav-btn" onclick="changeMonth(1)">›</button>
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
            <select id="event-type" class="filter-select">
                <option value="">Evento</option>
                <option value="show">Show</option>
                <option value="teatro">Teatro</option>
                <option value="palestra">Palestra</option>
            </select>
        </div>
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
