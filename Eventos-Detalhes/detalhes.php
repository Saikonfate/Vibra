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
        if ($item_tipo === 'ponto') {
            $sql = "SELECT pt.*, c.nome AS nome_cidade, c.estado AS estado_cidade,
                           AVG(av.nota) as media_avaliacoes, COUNT(av.id) as total_avaliacoes
                    FROM ponto_turistico pt
                    JOIN cidade c ON pt.id_cidade = c.id
                    LEFT JOIN avaliacao av ON av.id_ponto_turistico = pt.id
                    WHERE pt.id = ? AND pt.status = 'aprovado'
                    GROUP BY pt.id";
            $stmt = $mysqli->prepare($sql);
        } elseif ($item_tipo === 'evento') {
            $sql = "SELECT ec.*, c.nome AS nome_cidade, c.estado AS estado_cidade,
                           AVG(av.nota) as media_avaliacoes, COUNT(av.id) as total_avaliacoes
                    FROM evento_cultural ec
                    JOIN cidade c ON ec.id_cidade = c.id
                    LEFT JOIN avaliacao av ON av.id_evento_cultural = ec.id
                    WHERE ec.id = ? AND ec.status = 'aprovado'
                    GROUP BY ec.id";
            $stmt = $mysqli->prepare($sql);
        }

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
</head>
<body>
  <header class="barra">
    <a href="../Menu-inicial-cliente/Menu.php" class="logo-link">
        <div class="logo">V</div>
    </a>
    <input type="text" placeholder="Pesquisar..." class="search-bar">
    <button type="button" class="user-btn" onclick="toggleDetailsSidebar()" aria-expanded="false" aria-controls="sidebar-details">
      <i class="fa-solid fa-bars icon-space"></i>
      <i class="fa-regular fa-user"></i>
    </button>
  </header>

  <aside id="sidebar-details" class="sidebar">
    <h2 id="sidebar-username-details"><?php echo htmlspecialchars($nome_usuario_logado); ?></h2>
    <ul>
      <li><a href="../Menu-inicial-cliente/Menu.php">Inicio</a></li>
      <li><a href="../Cliente/cliente.php">Minha Conta & Cadastros</a></li>
      <?php if ($tipo_usuario_logado === 'admin'): ?>
          <li><a href="../ADM/adm.php" style="color: #c9302c; font-weight: bold;">Painel Admin</a></li>
      <?php endif; ?>
      <li><a href="../logout.php">Desconectar</a></li>
    </ul>
  </aside>
  <div id="overlay-details" class="overlay" style="display: none;"></div>


  <main class="main-content">
     <div class="container">
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
                     <div class="arrow arrow-left" onclick="prevImage()">❮</div>
                    <div class="event-image" id="eventImageContainer">
                        <img id="eventImage" src="<?php echo htmlspecialchars($item_midia[0]['url_arquivo']); ?>" alt="Imagem de <?php echo htmlspecialchars($item_details['nome']); ?>">
                    </div>
                    <div class="arrow arrow-right" onclick="nextImage()">❯</div>
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
                             <p><a href="https://www.google.com/maps/search/?api=1&query=<?php echo $item_details['latitude']; ?>,<?php echo $item_details['longitude']; ?>" target="_blank" rel="noopener noreferrer">Clique para abrir no Google Maps</a></p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="sidebar1">
                        <div class="section">
                            <div class="section-title">Organizador (Exemplo)</div>
                            <p>Nome do Organizador / Empresa</p>
                        </div>
                        <div class="section">
                            <div class="section-title">Contato (Exemplo)</div>
                            <p>Telefone: (XX) XXXXX-XXXX <br> Email: contato@exemplo.com</p>
                        </div>
                         <div class="accessibility-info section">
                            <div class="section-title">Acessibilidade</div>
                            <p>Informações sobre acessibilidade do local/evento.</p>
                        </div>
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
    const eventImages = <?php echo json_encode(array_column($item_midia, 'url_arquivo')); ?>;
    let currentImageIndex = 0;
    const eventImageElement = document.getElementById("eventImage");
    const indicatorsContainer = document.getElementById("carouselIndicators");

    function updateImage() {
        if (eventImageElement && eventImages.length > 0) {
            eventImageElement.src = eventImages[currentImageIndex];
            if (indicatorsContainer) updateIndicators();
        }
    }

    function prevImage() {
        if (eventImages.length > 1) {
            currentImageIndex = (currentImageIndex - 1 + eventImages.length) % eventImages.length;
            updateImage();
        }
    }

    function nextImage() {
         if (eventImages.length > 1) {
            currentImageIndex = (currentImageIndex + 1) % eventImages.length;
            updateImage();
        }
    }

    function updateIndicators() {
        if (!indicatorsContainer || eventImages.length <= 1) {
            if(indicatorsContainer) indicatorsContainer.style.display = 'none';
             return;
        }
        indicatorsContainer.style.display = 'flex';
        indicatorsContainer.innerHTML = ""; 
        eventImages.forEach((_, index) => {
            const dot = document.createElement("div");
            dot.classList.add("dot");
            if (index === currentImageIndex) dot.classList.add("active");
            dot.addEventListener("click", () => {
                currentImageIndex = index;
                updateImage();
            });
            indicatorsContainer.appendChild(dot);
        });
    }
    
    document.addEventListener("DOMContentLoaded", () => {
        if (eventImages.length > 0 && eventImageElement) {
             updateImage(); 
        } else if (eventImageElement && eventImages.length === 0) { 
             eventImageElement.src = '<?php echo !empty($item_midia[0]['url_arquivo']) ? htmlspecialchars($item_midia[0]['url_arquivo']) : "../placeholder_geral.jpg"; ?>';
        }

        if(indicatorsContainer){
            if(eventImages.length <=1 ){
                indicatorsContainer.style.display = 'none';
            } else {
                 updateIndicators(); 
            }
        }
        
        const arrows = document.querySelectorAll('.arrow');
        if(eventImages.length <= 1) {
            arrows.forEach(arrow => arrow.style.display = 'none');
        } else {
            arrows.forEach(arrow => arrow.style.display = 'block');
        }
        
        const starsContainer = document.querySelector('.stars-input');
        const hiddenNotaInput = document.getElementById('nota-avaliacao');
        const stars = starsContainer ? Array.from(starsContainer.querySelectorAll('span')) : [];

        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                const rating = parseInt(this.dataset.value);
                stars.forEach((s, i) => {
                    s.classList.toggle('hovered', i < rating);
                });
            });
            star.addEventListener('mouseout', function() {
                stars.forEach(s => s.classList.remove('hovered'));
                const currentSelectedRating = starsContainer ? parseInt(starsContainer.dataset.rating) : 0;
                 stars.forEach((s, i) => {
                    s.classList.toggle('selected', i < currentSelectedRating);
                });
            });
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.value);
                if(starsContainer) starsContainer.dataset.rating = rating;
                if(hiddenNotaInput) hiddenNotaInput.value = rating;
                stars.forEach((s, i) => {
                    s.classList.toggle('selected', i < rating);
                });
            });
        });

        const formAvaliacao = document.getElementById('form-avaliacao');
        if (formAvaliacao) {
            formAvaliacao.addEventListener('submit', async function(e) {
                e.preventDefault();
                const feedbackDiv = document.getElementById('review-feedback');
                if (!feedbackDiv) return;

                const currentNota = hiddenNotaInput ? parseInt(hiddenNotaInput.value) : 0;
                const isEditing = !!this.querySelector('input[name="avaliacao_id"]');
                if (currentNota === 0 && !isEditing) {
                    feedbackDiv.textContent = 'Por favor, selecione uma nota (1 a 5 estrelas).';
                    feedbackDiv.className = 'error';
                    return;
                }

                feedbackDiv.textContent = 'Enviando...';
                feedbackDiv.className = '';

                const formData = new FormData(this);
                try {
                    const response = await fetch('processa_avaliacao.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        feedbackDiv.textContent = result.message;
                        feedbackDiv.classList.add('success');
                        this.reset(); 
                        if(hiddenNotaInput) hiddenNotaInput.value = 0;
                        if(starsContainer) starsContainer.dataset.rating = 0; 
                        stars.forEach(s => s.classList.remove('selected'));
                        
                        const hiddenAvaliacaoIdField = formAvaliacao.querySelector('input[name="avaliacao_id"]');
                        if (hiddenAvaliacaoIdField) {
                            hiddenAvaliacaoIdField.remove();
                        }
                        const formTitle = formAvaliacao.querySelector('p');
                        if (formTitle) formTitle.textContent = 'Deixe sua avaliação:';


                        carregarAvaliacoes(); 
                        if (result.new_average_rating !== undefined && result.new_total_ratings !== undefined) {
                            updateAverageRatingDisplay(result.new_average_rating, result.new_total_ratings);
                        }

                    } else {
                        feedbackDiv.textContent = result.message || 'Erro ao enviar avaliação.';
                        feedbackDiv.classList.add('error');
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    feedbackDiv.textContent = 'Erro de comunicação ao enviar avaliação.';
                    feedbackDiv.classList.add('error');
                }
            });
        }
        if (<?php echo json_encode($item_id !== null); ?>) {
            carregarAvaliacoes(); 
        }
    });

    async function carregarAvaliacoes() {
        const reviewListContainer = document.getElementById('review-list-container');
        const itemIdInput = document.querySelector('input[name="item_id"]');
        const itemTipoInput = document.querySelector('input[name="item_tipo"]');
        const idUsuarioLogado = <?php echo json_encode($id_usuario_logado); ?>;

        if (!reviewListContainer || !itemIdInput || !itemTipoInput ) {
            if(reviewListContainer) reviewListContainer.innerHTML = '<p class="no-reviews" style="color:red;">Erro interno ao configurar carregamento de avaliações.</p>';
            return;
        }
        const itemId = itemIdInput.value;
        const itemTipo = itemTipoInput.value;

        reviewListContainer.innerHTML = '<p class="no-reviews">Carregando avaliações...</p>';

        try {
            const response = await fetch(`processa_avaliacao.php?action=carregar_avaliacoes&item_id=${itemId}&item_tipo=${itemTipo}`);
            const result = await response.json();
            reviewListContainer.innerHTML = ''; 

            if (result.success && result.avaliacoes && result.avaliacoes.length > 0) {
                result.avaliacoes.forEach(aval => {
                    const reviewDiv = document.createElement('div');
                    reviewDiv.className = 'review';
                    reviewDiv.setAttribute('data-avaliacao-id', aval.avaliacao_id); 
                    
                    let starsHTML = '';
                    for (let i = 0; i < 5; i++) {
                        starsHTML += `<span class="star-display">${i < aval.nota ? '★' : '☆'}</span>`;
                    }

                    let actionsHTML = '';
                    if (idUsuarioLogado && parseInt(aval.id_usuario) === parseInt(idUsuarioLogado)) {
                        const sanitizedComment = aval.comentario ? aval.comentario.replace(/'/g, "\\'").replace(/\n/g, "\\n") : '';
                        actionsHTML = `
                            <div class="review-actions">
                                <button class="btn-edit-review" onclick="preencherFormularioParaEdicao(${aval.avaliacao_id}, ${aval.nota}, '${sanitizedComment}')">Editar</button>
                                <button class="btn-delete-review" onclick="deletarAvaliacao(${aval.avaliacao_id})">Deletar</button>
                            </div>
                        `;
                    }

                    reviewDiv.innerHTML = `
                        <div class="review-header">
                            <span class="review-user">${aval.nome_usuario || 'Anônimo'}</span>
                            <span class="review-date">${aval.data_formatada || ''}</span>
                        </div>
                        <div class="review-rating">${starsHTML}</div>
                        <p class="review-text">${aval.comentario ? aval.comentario.replace(/\n/g, '<br>') : '<em>Sem comentário.</em>'}</p>
                        ${actionsHTML}
                    `;
                    reviewListContainer.appendChild(reviewDiv);
                });
            } else {
                reviewListContainer.innerHTML = '<p class="no-reviews">Ainda não há avaliações para este item. Seja o primeiro a avaliar!</p>';
            }
        } catch (error) {
            console.error('Erro ao carregar avaliações:', error);
            reviewListContainer.innerHTML = '<p class="no-reviews" style="color:red;">Erro ao carregar avaliações.</p>';
        }
    }

    function preencherFormularioParaEdicao(avaliacaoId, notaAtual, comentarioAtual) {
        const formAvaliacao = document.getElementById('form-avaliacao');
        const hiddenNotaInput = document.getElementById('nota-avaliacao');
        const starsContainer = formAvaliacao.querySelector('.stars-input');
        const textareaComentario = formAvaliacao.querySelector('textarea[name="comentario"]');
        const formTitle = formAvaliacao.querySelector('p'); 

        if (formTitle) formTitle.textContent = 'Editando sua avaliação:';
        
        let hiddenAvaliacaoIdField = formAvaliacao.querySelector('input[name="avaliacao_id"]');
        if (hiddenAvaliacaoIdField) {
            hiddenAvaliacaoIdField.value = avaliacaoId;
        } else {
            hiddenAvaliacaoIdField = document.createElement('input');
            hiddenAvaliacaoIdField.type = 'hidden';
            hiddenAvaliacaoIdField.name = 'avaliacao_id';
            hiddenAvaliacaoIdField.value = avaliacaoId;
            formAvaliacao.appendChild(hiddenAvaliacaoIdField);
        }

        if(hiddenNotaInput) hiddenNotaInput.value = notaAtual;
        if(starsContainer) {
            starsContainer.dataset.rating = notaAtual;
            Array.from(starsContainer.querySelectorAll('span')).forEach((star, index) => {
                star.classList.toggle('selected', index < notaAtual);
            });
        }
        
        if(textareaComentario) textareaComentario.value = comentarioAtual.replace(/\\n/g, '\n');
        
        formAvaliacao.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if(textareaComentario) textareaComentario.focus();
    }

    async function deletarAvaliacao(avaliacaoId) {
        if (!confirm('Tem certeza que deseja deletar sua avaliação? Esta ação não pode ser desfeita.')) {
            return;
        }

        const feedbackDiv = document.getElementById('review-feedback'); 
        if(feedbackDiv) {
            feedbackDiv.textContent = 'Deletando...';
            feedbackDiv.className = '';
        }

        const formData = new FormData();
        formData.append('action', 'deletar_avaliacao');
        formData.append('avaliacao_id', avaliacaoId);
        
        const itemIdInput = document.querySelector('input[name="item_id"]');
        const itemTipoInput = document.querySelector('input[name="item_tipo"]');
        if(itemIdInput) formData.append('item_id', itemIdInput.value);
        if(itemTipoInput) formData.append('item_tipo', itemTipoInput.value);

        try {
            const response = await fetch('processa_avaliacao.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                if(feedbackDiv) {
                    feedbackDiv.textContent = result.message;
                    feedbackDiv.classList.add('success');
                }
                carregarAvaliacoes(); 
                if (result.new_average_rating !== undefined && result.new_total_ratings !== undefined) {
                    updateAverageRatingDisplay(result.new_average_rating, result.new_total_ratings);
                }
            } else {
                 if(feedbackDiv) {
                    feedbackDiv.textContent = result.message || 'Erro ao deletar avaliação.';
                    feedbackDiv.classList.add('error');
                }
            }
        } catch (error) {
            console.error('Erro:', error);
            if(feedbackDiv) {
                feedbackDiv.textContent = 'Erro de comunicação ao deletar avaliação.';
                feedbackDiv.classList.add('error');
            }
        }
    }

    function updateAverageRatingDisplay(newAverage, newTotal) {
        const avgDisplayContainer = document.querySelector('.average-rating-display');
        if (avgDisplayContainer) {
            if (newTotal > 0) {
                let starsHTML = '';
                const roundedAvg = Math.round(newAverage);
                for (let i = 0; i < 5; i++) {
                    starsHTML += i < roundedAvg ? '★' : '☆';
                }
                avgDisplayContainer.innerHTML = `
                    Média: <span class="stars-avg">${starsHTML}</span>
                    <strong>${parseFloat(newAverage).toFixed(1)}</strong>
                    <span class="rating-count-display">(${newTotal} ${newTotal === 1 ? 'avaliação' : 'avaliações'})</span>
                `;
            } else {
                avgDisplayContainer.innerHTML = '<span>Este item ainda não possui avaliações.</span>';
            }
        }
    }

    // JavaScript para a Sidebar específica desta página
    const sidebarDetails = document.getElementById("sidebar-details");
    const overlayDetails = document.getElementById("overlay-details");
    const userBtnDetails = document.querySelector(".user-btn"); 

    window.toggleDetailsSidebar = function() { 
        if (sidebarDetails) {
            const isVisible = sidebarDetails.classList.toggle("visible");
            if(userBtnDetails) userBtnDetails.setAttribute('aria-expanded', isVisible.toString());
            if (overlayDetails) overlayDetails.style.display = isVisible ? "block" : "none";
        }
    };

    if (overlayDetails) {
        overlayDetails.addEventListener("click", () => {
            if (sidebarDetails && sidebarDetails.classList.contains("visible")) {
                 sidebarDetails.classList.remove("visible");
                 if(userBtnDetails) userBtnDetails.setAttribute('aria-expanded', 'false');
                 overlayDetails.style.display = "none";
            }
        });
    }
  </script>
  <?php if(isset($mysqli)) $mysqli->close(); ?>
</body>
</html>