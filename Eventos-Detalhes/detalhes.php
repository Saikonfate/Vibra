<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../protect.php');
require('../conexao.php');

$nome_usuario_logado = $_SESSION['nome'] ?? 'Usuário';
$id_usuario_logado = $_SESSION['id'] ?? null;
$tipo_usuario_logado = $_SESSION['tipo'] ?? 'cliente';

$item_id = null;
$item_tipo = null;
$item_details = null;
$item_midia = [];
$feedback_message = '';
$media_avaliacoes = 0;
$total_avaliacoes = 0;

if (isset($_GET['id']) && isset($_GET['tipo'])) {
    $item_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $item_tipo = htmlspecialchars($_GET['tipo']);

    if ($item_id === false) {
        $feedback_message = "<p class='error-message'>ID do item inválido.</p>";
    } elseif (!in_array($item_tipo, ['ponto', 'evento'])) {
        $feedback_message = "<p class='error-message'>Tipo de item inválido.</p>";
    } else {
        $table_name = ($item_tipo === 'ponto') ? 'ponto_turistico' : 'evento_cultural';
        $join_key = ($item_tipo === 'ponto') ? 'id_ponto_turistico' : 'id_evento_cultural';
        $alias = ($item_tipo === 'ponto') ? 'pt' : 'ec';

        $lat_lon_select = ($item_tipo === 'ponto') ? "{$alias}.latitude, {$alias}.longitude" : "c.latitude, c.longitude";

        $sql = "SELECT {$alias}.*, c.nome AS nome_cidade, c.estado AS estado_cidade,
                       {$lat_lon_select},
                       AVG(av.nota) as media_avaliacoes, COUNT(av.id) as total_avaliacoes
                FROM {$table_name} {$alias}
                JOIN cidade c ON {$alias}.id_cidade = c.id
                LEFT JOIN avaliacao av ON av.{$join_key} = {$alias}.id
                WHERE {$alias}.id = ? AND {$alias}.status = 'aprovado'
                GROUP BY {$alias}.id";
        $stmt = $mysqli->prepare($sql);

        if (isset($stmt)) {
            $stmt->bind_param("i", $item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $item_details = $result->fetch_assoc();
                $media_avaliacoes = $item_details['media_avaliacoes'] ? round($item_details['media_avaliacoes'], 1) : 0;
                $total_avaliacoes = $item_details['total_avaliacoes'] ?? 0;
            } else {
                $feedback_message = "<p class='error-message'>Item não encontrado ou não aprovado.</p>";
            }
            $stmt->close();
        } else {
            $feedback_message = "<p class='error-message'>Erro ao preparar a consulta para o item.</p>";
            error_log("Erro SQL (item_details): " . $mysqli->error);
        }

        if ($item_details) {
            $sql_midia_key = ($item_tipo === 'ponto') ? 'id_ponto_turistico' : 'id_evento_cultural';
            $sql_midia = "SELECT url_arquivo, tipo FROM midia WHERE $sql_midia_key = ? ORDER BY id ASC";
            $stmt_midia = $mysqli->prepare($sql_midia);
            if ($stmt_midia) {
                $stmt_midia->bind_param("i", $item_id);
                $stmt_midia->execute();
                $result_midia = $stmt_midia->get_result();
                while ($row_midia = $result_midia->fetch_assoc()) {
                    if (!empty($row_midia['url_arquivo']) && strpos($row_midia['url_arquivo'], 'http') !== 0) {
                        $row_midia['url_arquivo'] = '../' . ltrim($row_midia['url_arquivo'], '/');
                    }
                    $item_midia[] = $row_midia;
                }
                $stmt_midia->close();
            } else {
                 error_log("Erro SQL (midia): " . $mysqli->error);
            }
        }
    }
} else {
    $feedback_message = "<p class='error-message'>Informações insuficientes para carregar os detalhes.</p>";
}

if (empty($item_midia) && $item_details) {
    $item_midia[] = ['url_arquivo' => '../placeholder_geral.jpg', 'tipo' => 'imagem'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Detalhes - Vibra</title>
  <link rel="stylesheet" href="../Menu-inicial-cliente/reset.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="detalhes.css"/>
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
            <button class="map-btn" type="button">
                <i class="fa-regular fa-image"></i> Mapa
            </button>
        </a>
        <button type="button" class="user-btn" aria-expanded="false" aria-controls="sidebar">
          <i class="fa-solid fa-bars icon-space"></i>
          <i class="fa-regular fa-user"></i>
      </button>
    </div>
  </header>

  <aside id="sidebar" class="sidebar">
    <h2><?php echo htmlspecialchars($nome_usuario_logado); ?></h2>
    <ul>
        <li><a href="../Menu-inicial-cliente/Menu.php">Inicio</a></li>
        <li><a href="../Cliente/cliente.php">Minha Conta & Cadastros</a></li>
        <?php if (isset($tipo_usuario_logado) && $tipo_usuario_logado === 'admin'): ?>
            <li><a href="../ADM/adm.php" style="color: #c9302c; font-weight: bold;">Painel Admin</a></li>
        <?php endif; ?>
        <li><a href="../Tela de perfil de usuario/perfil.php">Perfil</a></li>
        <li><a href="../logout.php">Desconectar</a></li>
    </ul>
  </aside>
  <div id="overlay" class="overlay" style="display: none;"></div>

  <main class="main-content">
     <div class="container" id="detailsContainer"
          data-item-id="<?php echo htmlspecialchars($item_id); ?>"
          data-item-tipo="<?php echo htmlspecialchars($item_tipo); ?>"
          data-user-id="<?php echo htmlspecialchars($id_usuario_logado); ?>">

        <?php if (!empty($feedback_message)): ?>
            <?php echo $feedback_message; ?>
        <?php elseif ($item_details): ?>
            <div class="event-header">
                <h1 class="event-title"><?php echo htmlspecialchars($item_details['nome']); ?></h1>
                <div class="average-rating-display">
                    <?php if ($total_avaliacoes > 0): ?>
                        Média: <span class="stars-avg"><?php echo str_repeat('★', round($media_avaliacoes)); ?><?php echo str_repeat('☆', 5 - round($media_avaliacoes)); ?></span>
                        <strong><?php echo htmlspecialchars($media_avaliacoes); ?></strong>
                        <span class="rating-count-display">(<?php echo htmlspecialchars($total_avaliacoes); ?> <?php echo ($total_avaliacoes === 1 ? 'avaliação' : 'avaliações'); ?>)</span>
                    <?php else: ?>
                        <span>Este item ainda não possui avaliações.</span>
                    <?php endif; ?>
                </div>

                <?php if (!empty($item_midia)): ?>
                <div class="event-image-wrapper">
                     <div class="arrow arrow-left">❮</div>
                    <div class="event-image" id="eventImageContainer">
                        <img id="eventImage" src="<?php echo htmlspecialchars($item_midia[0]['url_arquivo']); ?>" alt="Imagem de <?php echo htmlspecialchars($item_details['nome']); ?>">
                    </div>
                    <div class="arrow arrow-right">❯</div>
                </div>
                <?php if (count($item_midia) > 1): ?>
                <div class="carousel-indicators" id="carouselIndicators">
                </div>
                <?php endif; ?>
                <?php endif; ?>


                <div class="tabs">
                    <div class="tab active">Informações Gerais</div>
                </div>

                <div class="tab-content">
                    <div class="info-section">
                        <div class="section">
                            <div class="section-title">Descrição</div>
                            <p><?php echo nl2br(htmlspecialchars($item_details['descricao'] ?? 'Nenhuma descrição fornecida.')); ?></p>
                        </div>

                        <div class="section">
                            <div class="section-title">Localização</div>
                            <p>
                                <?php echo htmlspecialchars($item_details['nome_cidade'] ?? 'N/D'); ?>, <?php echo htmlspecialchars($item_details['estado_cidade'] ?? 'N/D'); ?>
                                <?php if ($item_tipo === 'evento' && !empty($item_details['local_evento'])): ?>
                                    <br><strong>Local Específico:</strong> <?php echo htmlspecialchars($item_details['local_evento']); ?>
                                <?php elseif ($item_tipo === 'ponto' && !empty($item_details['endereco'])): ?>
                                    <br><strong>Endereço:</strong> <?php echo htmlspecialchars($item_details['endereco']); ?>
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if ($item_tipo === 'evento'): ?>
                            <div class="section">
                                <div class="section-title">Data e Horário</div>
                                <p>
                                    <strong>Início:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item_details['horario_abertura']))); ?><br>
                                    <strong>Fim:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($item_details['horario_fechamento']))); ?>
                                </p>
                            </div>
                        <?php elseif ($item_tipo === 'ponto'): ?>
                            <?php if (!empty($item_details['horario_abertura']) && !empty($item_details['horario_fechamento'])): ?>
                            <div class="section">
                                <div class="section-title">Horário de Funcionamento</div>
                                <p>Das <?php echo htmlspecialchars(date('H:i', strtotime($item_details['horario_abertura']))); ?> às <?php echo htmlspecialchars(date('H:i', strtotime($item_details['horario_fechamento']))); ?></p>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="section">
                            <div class="section-title">Tipo/Categoria</div>
                            <p><?php echo htmlspecialchars($item_details['tipo'] ?? 'N/D'); ?></p>
                        </div>

                        <?php if (isset($item_details['taxaentrada']) && $item_details['taxaentrada'] !== null): ?>
                        <div class="section">
                            <div class="section-title">Taxa de Entrada</div>
                            <p><?php echo ($item_details['taxaentrada'] > 0) ? 'R$ ' . htmlspecialchars(number_format($item_details['taxaentrada'], 2, ',', '.')) : 'Gratuito'; ?></p>
                        </div>
                        <?php endif; ?>

                         <?php if (isset($item_details['latitude']) && isset($item_details['longitude']) && $item_details['latitude'] != 0 && $item_details['longitude'] != 0): ?>
                        <div class="section">
                            <div class="section-title">Ver no Mapa</div>
                             <p><a href="../Mapa/mapa.php?lat=<?php echo $item_details['latitude']; ?>&lon=<?php echo $item_details['longitude']; ?>" target="_blank">Clique para ver no mapa do site</a></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="reviews">
                <h2>Avaliações</h2>
                <?php if ($id_usuario_logado): ?>
                <div class="review-form">
                    <p>Deixe sua avaliação:</p>
                    <form id="form-avaliacao">
                        <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_id); ?>">
                        <input type="hidden" name="item_tipo" value="<?php echo htmlspecialchars($item_tipo); ?>">
                        <input type="hidden" name="action" value="salvar_avaliacao">
                        <input type="hidden" name="nota" id="nota-avaliacao" value="0">

                        <div class="stars-input" data-rating="0">
                            <span data-value="1">★</span>
                            <span data-value="2">★</span>
                            <span data-value="3">★</span>
                            <span data-value="4">★</span>
                            <span data-value="5">★</span>
                        </div>
                        <textarea name="comentario" placeholder="Escreva aqui sua opinião..." rows="3"></textarea>
                        <button type="submit" class="btn-avaliar">Enviar Avaliação</button>
                        <div id="review-feedback"></div>
                    </form>
                </div>
                <?php else: ?>
                    <p>Para deixar uma avaliação, <a href="../Login/login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">faça login</a> ou <a href="../Cadastro/PROJETO.php">cadastre-se</a>.</p>
                <?php endif; ?>

                <div class="review-list" id="review-list-container">
                    <p class="no-reviews">Carregando avaliações...</p>
                </div>
            </div>

        <?php else: ?>
            <p class='error-message'>Não foi possível carregar os detalhes do item solicitado.</p>
        <?php endif; ?>
    </div>
  </main>

  <script>
    window.pageData = {
        eventImages: <?php echo json_encode(array_column($item_midia, 'url_arquivo')); ?>,
        initialImage: '<?php echo !empty($item_midia[0]['url_arquivo']) ? htmlspecialchars($item_midia[0]['url_arquivo']) : "../placeholder_geral.jpg"; ?>',
        itemId: <?php echo json_encode($item_id); ?>,
        itemTipo: <?php echo json_encode($item_tipo); ?>,
        idUsuarioLogado: <?php echo json_encode($id_usuario_logado); ?>
    };
  </script>

  <script src="detalhes.js" defer></script>

  <?php if(isset($mysqli)) $mysqli->close(); ?>
</body>
</html>