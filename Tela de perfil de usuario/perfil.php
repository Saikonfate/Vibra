<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require('../protect.php');
require('../conexao.php');

$id_usuario_logado = $_SESSION['id'];
$nome_usuario_logado_sessao = $_SESSION['nome'] ?? 'Usuário';
$email_usuario_logado_sessao = $_SESSION['email'] ?? '';
$tipo_usuario_logado = $_SESSION['tipo'] ?? 'cliente';

$db_nome_usuario = $nome_usuario_logado_sessao;
$db_email_usuario = $email_usuario_logado_sessao;
$db_descricao_perfil = '';
$db_url_foto_perfil = '../placeholder_usuario.jpg'; 

if (isset($mysqli)) {
    $stmt_user = $mysqli->prepare("SELECT nome, email, descricao_perfil, url_foto_perfil FROM usuario WHERE id = ?");
    if ($stmt_user) {
        $stmt_user->bind_param("i", $id_usuario_logado);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        if ($usuario_data = $result_user->fetch_assoc()) {
            $db_nome_usuario = $usuario_data['nome'];
            $db_email_usuario = $usuario_data['email'];
            if (!empty($usuario_data['descricao_perfil'])) {
                $db_descricao_perfil = $usuario_data['descricao_perfil'];
            }
            if (!empty($usuario_data['url_foto_perfil'])) {
                $db_url_foto_perfil = '../' . ltrim($usuario_data['url_foto_perfil'], '/');
            }
        }
        $stmt_user->close();
    } else {
        error_log("Erro ao preparar consulta de usuário: " . $mysqli->error);
    }

    $avaliacoes_usuario = [];
    $sql_eventos_avaliados = "
        SELECT e.id, e.nome,
               (SELECT m.url_arquivo FROM midia m WHERE m.id_evento_cultural = e.id ORDER BY m.id ASC LIMIT 1) as imagem_url,
               'evento' as tipo_item, a.nota, a.comentario as comentario_avaliacao, a.data as data_avaliacao
        FROM avaliacao a
        JOIN evento_cultural e ON a.id_evento_cultural = e.id
        WHERE a.id_usuario = ? AND a.id_evento_cultural IS NOT NULL AND e.status = 'aprovado'
        ORDER BY a.data DESC, a.id DESC LIMIT 10";
    
    $stmt_eventos_avaliados = $mysqli->prepare($sql_eventos_avaliados);
    if ($stmt_eventos_avaliados) {
        $stmt_eventos_avaliados->bind_param("i", $id_usuario_logado);
        $stmt_eventos_avaliados->execute();
        $result_eventos = $stmt_eventos_avaliados->get_result();
        while ($row = $result_eventos->fetch_assoc()) {
            if (!empty($row['imagem_url']) && strpos($row['imagem_url'], 'http') !== 0) {
                $row['imagem_url'] = '../' . ltrim($row['imagem_url'], '/');
            } elseif (empty($row['imagem_url'])) {
                $row['imagem_url'] = '../placeholder_geral.jpg';
            }
            $avaliacoes_usuario[] = $row;
        }
        $stmt_eventos_avaliados->close();
    }

    // Avaliações de Pontos Turísticos
    $sql_pontos_avaliados = "
        SELECT p.id, p.nome,
               (SELECT m.url_arquivo FROM midia m WHERE m.id_ponto_turistico = p.id ORDER BY m.id ASC LIMIT 1) as imagem_url,
               'ponto' as tipo_item, a.nota, a.comentario as comentario_avaliacao, a.data as data_avaliacao
        FROM avaliacao a
        JOIN ponto_turistico p ON a.id_ponto_turistico = p.id
        WHERE a.id_usuario = ? AND a.id_ponto_turistico IS NOT NULL AND p.status = 'aprovado'
        ORDER BY a.data DESC, a.id DESC LIMIT 10";

    $stmt_pontos_avaliados = $mysqli->prepare($sql_pontos_avaliados);
    if ($stmt_pontos_avaliados) {
        $stmt_pontos_avaliados->bind_param("i", $id_usuario_logado);
        $stmt_pontos_avaliados->execute();
        $result_pontos = $stmt_pontos_avaliados->get_result();
        while ($row = $result_pontos->fetch_assoc()) {
             if (!empty($row['imagem_url']) && strpos($row['imagem_url'], 'http') !== 0) {
                $row['imagem_url'] = '../' . ltrim($row['imagem_url'], '/');
            } elseif (empty($row['imagem_url'])) {
                $row['imagem_url'] = '../placeholder_geral.jpg';
            }
            $avaliacoes_usuario[] = $row;
        }
        $stmt_pontos_avaliados->close();
    }

    if (!empty($avaliacoes_usuario)) {
        usort($avaliacoes_usuario, function($a, $b) {
            return strtotime($b['data_avaliacao']) - strtotime($a['data_avaliacao']);
        });
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Meu Perfil - Vibra</title>
  <link rel="stylesheet" href="style.css"/>
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
        <button type="button" class="user-btn" onclick="toggleSidebar()" aria-expanded="false" aria-controls="sidebar">
          <i class="fa-solid fa-bars icon-space"></i>
          <i class="fa-regular fa-user"></i>
        </button>
    </div>
  </header>
  <aside id="sidebar" class="sidebar">
    <h2><?php echo htmlspecialchars($db_nome_usuario); ?></h2>
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

  <main class="perfil-container">
    <section class="perfil-topo">
      <div class="foto-perfil-container"> <img id="fotoPerfilAtual" src="<?php echo htmlspecialchars($db_url_foto_perfil); ?>" alt="Foto de <?php echo htmlspecialchars($db_nome_usuario); ?>" class="foto-perfil-img">
      </div>
      <div class="info-perfil">
        <h2 id="nomePerfilAtual"><?php echo htmlspecialchars($db_nome_usuario); ?></h2>
        <p class="descricao" id="descricaoPerfilAtual"><?php echo nl2br(htmlspecialchars($db_descricao_perfil)); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($db_email_usuario); ?></p>
      </div>
      <button class="btn-editar" onclick="abrirModalPerfil()">Personalizar Perfil <span class="icone-editar">✎</span></button>
    </section>

    <hr class="linha-divisoria" />

    <section class="eventos-realizados">
      <h3>Minhas Avaliações Recentes</h3>
      <div class="lista-eventos">
        <?php if (!empty($avaliacoes_usuario)): ?>
          <?php foreach (array_slice($avaliacoes_usuario, 0, 10) as $item_avaliado): ?>
            <div class="evento">
              <a href="../Eventos-Detalhes/detalhes.php?tipo=<?php echo $item_avaliado['tipo_item']; ?>&id=<?php echo $item_avaliado['id']; ?>" class="evento-link-perfil">
                  <div class="img-evento" style="background-image: url('<?php echo htmlspecialchars($item_avaliado['imagem_url']); ?>');">
                      <?php if (strpos($item_avaliado['imagem_url'], 'placeholder_geral.jpg') !== false): ?>
                          <span>Sem Imagem</span>
                      <?php endif; ?>
                  </div>
                  <h4><?php echo htmlspecialchars($item_avaliado['nome']); ?></h4>
              </a>
              <p class="avaliacao-info">
                  <strong>Sua Nota:</strong> 
                  <span class="estrelas-avaliacao"><?php echo str_repeat('★', (int)$item_avaliado['nota']) . str_repeat('☆', 5 - (int)$item_avaliado['nota']); ?></span>
              </p>
              <?php if (!empty($item_avaliado['comentario_avaliacao'])): ?>
                  <p class="comentario-usuario"><em>"<?php echo nl2br(htmlspecialchars($item_avaliado['comentario_avaliacao'])); ?>"</em></p>
              <?php endif; ?>
              <p class="data-avaliacao">Avaliado em: <?php echo htmlspecialchars(date('d/m/Y', strtotime($item_avaliado['data_avaliacao']))); ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>Você ainda não fez nenhuma avaliação.</p>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <div id="modalEditarPerfil" class="modal-perfil" style="display:none;">
      <div class="modal-perfil-content">
          <span class="modal-close-btn" onclick="fecharModalPerfil()">&times;</span>
          <h3>Editar Perfil</h3>
          <form id="formEditarPerfil" enctype="multipart/form-data">
              <div class="form-group-modal">
                  <label for="nova_descricao">Descrição:</label>
                  <textarea id="nova_descricao" name="descricao_perfil" rows="5" placeholder="Fale um pouco sobre você..."><?php echo htmlspecialchars(str_replace('<br />', '', $db_descricao_perfil === 'Para adicionar uma descrição ou alterar seus dados, vá para "Minha Conta & Cadastros".' ? '' : $db_descricao_perfil)); ?></textarea>
              </div>
              <div class="form-group-modal">
                  <label for="nova_foto_perfil">Alterar Foto de Perfil (JPG, PNG, GIF - Máx 5MB):</label>
                  <input type="file" class="hidden-file-input" id="nova_foto_perfil" name="foto_perfil" accept="image/jpeg, image/png, image/gif">
                  <img id="previewFotoPerfil" src="<?php echo htmlspecialchars($db_url_foto_perfil); ?>" alt="Pré-visualização" class="preview-foto-modal"/>
              </div>
              <button type="submit" class="btn-salvar-modal">Salvar Alterações</button>
              <div id="editarPerfilFeedback" class="feedback-message-modal"></div>
          </form>
      </div>
  </div>

<script src="detalhes.js"></script>
<?php if(isset($mysqli) && $mysqli instanceof mysqli) $mysqli->close(); ?>
</body>
</html>
