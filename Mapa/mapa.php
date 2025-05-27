<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../protect.php'); // Proteção de sessão
require('../conexao.php'); // Conexão com o banco de dados

$nome_usuario_logado = $_SESSION['nome'] ?? 'Usuário';
$tipo_usuario_logado = $_SESSION['tipo'] ?? 'cliente';

// Buscar tipos/categorias únicas de pontos turísticos e eventos para os filtros
$categorias_db = [];
$sql_tipos_pontos = "SELECT DISTINCT tipo FROM ponto_turistico WHERE status = 'aprovado' AND tipo IS NOT NULL AND tipo <> '' ORDER BY tipo ASC";
$result_tipos_pontos = $mysqli->query($sql_tipos_pontos);
if ($result_tipos_pontos) {
    while ($row = $result_tipos_pontos->fetch_assoc()) {
        $categorias_db[$row['tipo']] = $row['tipo']; // Usar o próprio tipo como chave e valor para evitar duplicatas
    }
}
$sql_tipos_eventos = "SELECT DISTINCT tipo FROM evento_cultural WHERE status = 'aprovado' AND tipo IS NOT NULL AND tipo <> '' ORDER BY tipo ASC";
$result_tipos_eventos = $mysqli->query($sql_tipos_eventos);
if ($result_tipos_eventos) {
    while ($row = $result_tipos_eventos->fetch_assoc()) {
        $categorias_db[$row['tipo']] = $row['tipo'];
    }
}
ksort($categorias_db); // Ordena as categorias alfabeticamente pela chave (que é o nome da categoria)

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Mapa Interativo - Vibra</title>
  <link rel="stylesheet" href="../Menu-inicial-cliente/reset.css"> <link rel="stylesheet" href="mapa.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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
  
  <aside id="user-sidebar" class="sidebar-map default-sidebar">
    <h2><?php echo htmlspecialchars($nome_usuario_logado); ?></h2>
    <ul>
      <li><a href="../Menu-inicial-cliente/Menu.php">Início</a></li>
      <li><a href="../Cliente/cliente.php">Minha Conta & Cadastros</a></li>
      <?php if ($tipo_usuario_logado === 'admin'): ?>
          <li><a href="../ADM/adm.php" style="color: #c9302c; font-weight: bold;">Painel Admin</a></li>
      <?php endif; ?>
      <li><a href="../Tela de perfil de usuario/perfil.php">Perfil</a></li>
      <li><a href="../logout.php">Desconectar</a></li>
    </ul>
  </aside>

  <aside id="category-filters-sidebar" class="sidebar-map category-sidebar">
    <div class="sidebar-header">
        <h3>Filtrar por Categoria</h3>
        <button id="close-category-filters" class="close-btn">&times;</button>
    </div>
    <div id="category-list">
        <div class="category-item active" data-category-value="all">
            <span class="category-icon"><i class="fas fa-globe"></i></span> Todos
        </div>
        <?php if (!empty($categorias_db)): ?>
            <?php foreach ($categorias_db as $categoria): ?>
                <div class="category-item" data-category-value="<?php echo htmlspecialchars($categoria); ?>">
                    <span class="category-icon">
                        <i class="fas fa-tag"></i> 
                    </span>
                    <?php echo htmlspecialchars($categoria); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhuma categoria encontrada.</p>
        <?php endif; ?>
    </div>
  </aside>

  <aside id="event-info-sidebar" class="sidebar-map event-sidebar">
      <div class="sidebar-header">
          <h3 id="event-list-title">Detalhes do Local</h3>
          <button id="close-event-info" class="close-btn">&times;</button>
      </div>
      <div id="event-info-content">
          <p>Clique em um marcador no mapa para ver os detalhes.</p>
      </div>
  </aside>

  <div id="overlay" class="overlay-map"></div>
  
  <main class="map-main-content">
    <div id="map-container">
      <div id="map"></div>
    </div>
  </main>

  <script src="mapa.js"></script>
</body>
</html>
