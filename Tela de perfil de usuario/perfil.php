<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>avaliações</title>
  <link rel="stylesheet" href="style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <header class="barra">
    <div class="left-section">
        <a href="Menu.php" class="logo-link">
            <div class="logo">V</div>
        </a>
    </div>

    <div class="right-section">
        <a href="../Mapa/mapa.php">
            <button class="map-btn" type="button">
                <i class="fa-regular fa-image"></i> Mapa
            </button>
        </a>
        <button type="button" class="user-btn" onclick="toggleSidebar()" aria-expanded="false" aria-controls="sidebar">
          <i class="fa-solid fa-bars icon-space"></i>
          <i class="fa-regular fa-user"></i>
      </button>
    </div>
  </header>
  <aside id="sidebar" class="sidebar">
    <h2><?php echo 'Nome_Usuario'; // Substituir?></h2>
    <ul>
        <li><a href="../Menu-inicial-cliente/Menu.php">Inicio</a></li>
        <li><a href="../Cliente/cliente.php">Conta & Cadastros</a></li>
        <?php // if (isset($tipo_usuario_logado) && $tipo_usuario_logado === 'admin'): ?>
            <li><a href="../ADM/adm.php" style="color: #c9302c; font-weight: bold;">Painel Admin</a></li>
        <?php // endif; ?>
        <li><a href="../Tela de perfil de usuario/perfil.html">Perfil</a></li>
        <li><a href="../logout.php">Desconectar</a></li>
    </ul>
</aside>
<div id="overlay" class="overlay" style="display: none;"></div>
  <main class="perfil-container">
  <section class="perfil-topo">
    <div class="foto-perfil"></div>
    <div class="info-perfil">
      <h2>User_test1</h2>
      <p class="localizacao">Juazeiro do Norte, CE</p>
      <p class="descricao">
        Empresa especializada na organização de eventos personalizados, sociais e corporativos.
        Transformamos ideias em experiências únicas, com criatividade, excelência e atenção aos detalhes.
      </p>
    </div>
    <button class="btn-editar">Personalizar Perfil <span class="icone-editar">✎</span></button>
  </section>

  <hr class="linha-divisoria" />

  <section class="eventos-realizados">
    <h3>Eventos Realizados</h3>
    <div class="lista-eventos">
      <div class="evento">
        <div class="img-evento"></div>
        <h4>Evento Exemplo para Teste</h4>
        <p>“Esse é um Evento teste para ilustração de página do perfil.”</p>
      </div>
      <div class="evento">
        <div class="img-evento"></div>
        <h4>Evento Exemplo para Teste</h4>
        <p>“Esse é um Evento teste para ilustração de página do perfil.”</p>
      </div>
      <div class="evento">
        <div class="img-evento"></div>
        <h4>Evento Exemplo para Teste</h4>
        <p>“Esse é um Evento teste para ilustração de página do perfil.”</p>
      </div>
    </div>
  </section>
</main>

<script src="detalhes.js"></script>

</body>
</html>
