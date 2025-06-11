<?php
// Inicia a sessão se ainda não estiver iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Inclui o arquivo de proteção e conexão com o banco de dados
// ATENÇÃO: Caminhos ajustados para a sua estrutura de pastas!
require('../protect.php'); // '..' para sair de 'Menu-inicial-cliente/' e ir para a raiz do projeto
require('../conexao.php'); // '..' para sair de 'Menu-inicial-cliente/' e ir para a raiz do projeto

// Pega o nome e tipo do usuário da sessão
$nome_usuario_logado = $_SESSION['nome'] ?? 'Usuário';
$tipo_usuario_logado = $_SESSION['tipo'] ?? 'cliente';

$termo_pesquisa = $_GET['termo_pesquisa'] ?? '';
$resultados_completos = []; // Para armazenar todos os resultados combinados

if (!empty($termo_pesquisa)) {
    // Prepara o termo de pesquisa para a cláusula LIKE, adicionando '%'
    $termo_pesquisa_like = '%' . $mysqli->real_escape_string($termo_pesquisa) . '%';

    // Consulta UNION ALL para encontrar IDs e o tipo de item (evento ou ponto) que correspondem ao termo
    // 'AS tipo_item' é necessário para diferenciar se o ID veio de evento_cultural ou ponto_turistico.
    $sql_union_busca = "
        SELECT id, nome, 'evento_cultural' AS tipo_item
        FROM evento_cultural
        WHERE status = 'aprovado' AND nome LIKE ?

        UNION ALL

        SELECT id, nome, 'ponto_turistico' AS tipo_item
        FROM ponto_turistico
        WHERE status = 'aprovado' AND nome LIKE ?
    ";

    $stmt_union_busca = $mysqli->prepare($sql_union_busca);
    if ($stmt_union_busca) {
        // Vincula o mesmo termo de pesquisa para ambas as partes da UNION
        $stmt_union_busca->bind_param("ss", $termo_pesquisa_like, $termo_pesquisa_like);
        $stmt_union_busca->execute();
        $result_union_busca = $stmt_union_busca->get_result();

        $ids_eventos = [];
        $ids_pontos = [];

        // Coleta os IDs e separa por tipo
        while ($row = $result_union_busca->fetch_assoc()) {
            if ($row['tipo_item'] === 'evento_cultural') {
                $ids_eventos[] = $row['id'];
            } elseif ($row['tipo_item'] === 'ponto_turistico') {
                $ids_pontos[] = $row['id'];
            }
        }
        $stmt_union_busca->close();

        // Agora busca os detalhes completos dos eventos encontrados
        if (!empty($ids_eventos)) {
            // Cria placeholders (?) para a cláusula IN
            $ids_placeholder = implode(',', array_fill(0, count($ids_eventos), '?'));
            $sql_eventos_detalhes = "
                SELECT
                    evento_cultural.id,
                    evento_cultural.nome,
                    evento_cultural.descricao,
                    evento_cultural.horario_abertura,
                    evento_cultural.local_evento,
                    cidade.nome AS nome_cidade, -- Alias necessário pois 'nome' existe em várias tabelas
                    (SELECT midia.url_arquivo FROM midia WHERE midia.id_evento_cultural = evento_cultural.id ORDER BY midia.id ASC LIMIT 1) AS imagem_url,
                    'evento_cultural' AS tipo_exibicao
                FROM evento_cultural
                JOIN cidade ON evento_cultural.id_cidade = cidade.id
                WHERE evento_cultural.id IN ($ids_placeholder)
                ORDER BY evento_cultural.horario_abertura DESC
            ";
            $stmt_eventos_detalhes = $mysqli->prepare($sql_eventos_detalhes);
            if ($stmt_eventos_detalhes) {
                // 'i' para cada ID inteiro
                $types = str_repeat('i', count($ids_eventos));
                $stmt_eventos_detalhes->bind_param($types, ...$ids_eventos);
                $stmt_eventos_detalhes->execute();
                $result_eventos_detalhes = $stmt_eventos_detalhes->get_result();
                while ($row = $result_eventos_detalhes->fetch_assoc()) {
                    // Ajusta o caminho da imagem com base na sua estrutura de 'uploads'
                    // As imagens estão em 'uploads/' na raiz do projeto.
                    // O 'pesquisar_resultados.php' está em 'Menu-inicial-cliente/', então precisa de '../'
                    if (!empty($row['imagem_url']) && strpos($row['imagem_url'], 'http') !== 0) {
                        $row['imagem_url'] = '../' . ltrim($row['imagem_url'], '/');
                    } elseif (empty($row['imagem_url'])) {
                        $row['imagem_url'] = '../placeholder_geral.jpg'; // Imagem padrão, assumindo que esteja na raiz
                    }
                    $resultados_completos[] = $row;
                }
                $stmt_eventos_detalhes->close();
            } else {
                error_log("Erro ao buscar detalhes de eventos para pesquisa: " . $mysqli->error);
            }
        }

        // Agora busca os detalhes completos dos pontos turísticos encontrados
        if (!empty($ids_pontos)) {
            // Cria placeholders (?) para a cláusula IN
            $ids_placeholder = implode(',', array_fill(0, count($ids_pontos), '?'));
            $sql_pontos_detalhes = "
                SELECT
                    ponto_turistico.id,
                    ponto_turistico.nome,
                    ponto_turistico.descricao,
                    ponto_turistico.tipo,
                    cidade.nome AS nome_cidade, -- Alias necessário pois 'nome' existe em várias tabelas
                    (SELECT midia.url_arquivo FROM midia WHERE midia.id_ponto_turistico = ponto_turistico.id ORDER BY midia.id ASC LIMIT 1) AS imagem_url,
                    'ponto_turistico' AS tipo_exibicao
                FROM ponto_turistico
                JOIN cidade ON ponto_turistico.id_cidade = cidade.id
                WHERE ponto_turistico.id IN ($ids_placeholder)
                ORDER BY ponto_turistico.id DESC
            ";
            $stmt_pontos_detalhes = $mysqli->prepare($sql_pontos_detalhes);
            if ($stmt_pontos_detalhes) {
                // 'i' para cada ID inteiro
                $types = str_repeat('i', count($ids_pontos));
                $stmt_pontos_detalhes->bind_param($types, ...$ids_pontos);
                $stmt_pontos_detalhes->execute();
                $result_pontos_detalhes = $stmt_pontos_detalhes->get_result();
                while ($row = $result_pontos_detalhes->fetch_assoc()) {
                    // Ajusta o caminho da imagem com base na sua estrutura de 'uploads'
                    // As imagens estão em 'uploads/' na raiz do projeto.
                    // O 'pesquisar_resultados.php' está em 'Menu-inicial-cliente/', então precisa de '../'
                    if (!empty($row['imagem_url']) && strpos($row['imagem_url'], 'http') !== 0) {
                        $row['imagem_url'] = '../' . ltrim($row['imagem_url'], '/');
                    } elseif (empty($row['imagem_url'])) {
                        $row['imagem_url'] = '../placeholder_geral.jpg'; // Imagem padrão, assumindo que esteja na raiz
                    }
                    $resultados_completos[] = $row;
                }
                $stmt_pontos_detalhes->close();
            } else {
                error_log("Erro ao buscar detalhes de pontos turísticos para pesquisa: " . $mysqli->error);
            }
        }
    } else {
        error_log("Erro ao preparar query UNION: " . $mysqli->error);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados da Pesquisa - Vibra</title>
    <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="reset.css"> <link rel="stylesheet" href="Menu.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .body {
            font-family: 'Saira Stencil One', sans-serif;
        }

        .search-results-section {
            padding: 20px;
            margin-top: 20px;
        }
        .search-results-section h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        .no-results {
            text-align: center;
            padding: 50px;
            font-size: 1.2em;
            color: #555;
        }
        .event-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: flex-start;
            padding: 10px;
        }
        .event-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 280px;
            min-width: 250px;
            height: 380px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease-in-out;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        .event-image {
            width: 100%;
            height: 200px;
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.2em;
            background-color: #ccc;
        }
        .event-details {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .event-title {
            font-size: 1.4em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .event-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 10px;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }
        .event-info {
            font-size: 0.85em;
            color: #999;
        }
        .event-link {
            text-decoration: none;
            color: inherit;
        }
        
    </style>
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
            <a href="../Mapa/mapa.html">
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
            <li><a href="../logout.php">Desconectar</a></li>
        </ul>
    </aside>
    <div id="overlay" class="overlay" style="display: none;"></div>
    <main class="eventos">
        <div class="search-results-section">
            <h2>Resultados da Pesquisa para: "<?php echo htmlspecialchars($termo_pesquisa); ?>"</h2>

            <?php if (empty($resultados_completos)): ?>
                <p class="no-results">Nenhum resultado encontrado para "<?php echo htmlspecialchars($termo_pesquisa); ?>".</p>
            <?php else: ?>
                <div class="event-container">
                    <?php foreach ($resultados_completos as $item): ?>
                        <a href="../Eventos-Detalhes/detalhes.php?tipo=<?php echo $item['tipo_exibicao'] === 'evento_cultural' ? 'evento' : 'ponto'; ?>&id=<?php echo $item['id']; ?>" class="event-link">
                            <div class="event-card">
                                <div class="event-image" style="background-image: url('<?php echo htmlspecialchars($item['imagem_url']); ?>');">
                                    <?php if (strpos($item['imagem_url'], 'placeholder_geral.jpg') !== false): ?>
                                        <span>Sem Imagem</span>
                                    <?php endif; ?>
                                </div>
                                <div class="event-details">
                                    <div class="event-title"><?php echo htmlspecialchars($item['nome']); ?></div>
                                    <div class="event-description">
                                        <?php echo htmlspecialchars(mb_substr($item['descricao'] ?? '', 0, 60)) . (mb_strlen($item['descricao'] ?? '') > 60 ? '...' : ''); ?>
                                    </div>
                                    <div class="event-info">
                                        <?php if ($item['tipo_exibicao'] === 'evento_cultural'): ?>
                                            <?php
                                            try {
                                                $dataEvento = new DateTime($item['horario_abertura']);
                                                echo htmlspecialchars($dataEvento->format('d/m/Y \à\s H:i'));
                                            } catch (Exception $e) {
                                                echo htmlspecialchars($item['horario_abertura']);
                                            }
                                            echo " - " . htmlspecialchars($item['local_evento']);
                                            ?>
                                        <?php elseif ($item['tipo_exibicao'] === 'ponto_turistico'): ?>
                                            <?php echo htmlspecialchars($item['nome_cidade']); ?> - <?php echo htmlspecialchars($item['tipo']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script src="Menu.js"></script> <script>
        // Adicione seu JavaScript existente aqui
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
