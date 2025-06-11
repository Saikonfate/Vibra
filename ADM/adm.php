<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Autenticação do Admin para acessar esta página
if (!isset($_SESSION['id']) || !isset($_SESSION['tipo']) || $_SESSION['tipo'] !== 'admin') {
    header('Location: ../Login/login.php?erro=acesso_negado_admin'); // Redireciona para login
    exit;
}
$nome_admin_logado = $_SESSION['nome'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Painel Administrativo - Vibra</title>
  <link href="https://fonts.googleapis.com/css2?family=Saira+Stencil+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="adm.css">
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
    <h2><?php echo htmlspecialchars($nome_admin_logado); ?></h2>
    <ul>
        <li><a href="../Menu-inicial-cliente/Menu.php">Inicio</a></li>
        <li><a href="../Cliente/cliente.php">Minha Conta & Cadastros</a></li>
        <?php // if (isset($tipo_usuario_logado) && $tipo_usuario_logado === 'admin'): ?>
            <li><a href="../ADM/adm.php" style="color: #c9302c; font-weight: bold;">Painel Admin</a></li>
        <?php // endif; ?>
        <li><a href="../Tela de perfil de usuario/perfil.php">Perfil</a></li>
        <li><a href="../logout.php">Desconectar</a></li>
    </ul>
  </aside>
    <div id="overlay" class="overlay" style="display: none;"></div>

    <main>
        <div class="container">
            <div class="tab-container">
                <div class="tabs">
                    <button class="tab-btn active" data-tab="painel" aria-selected="true">Painel de Aprovações</button>
                    <button class="tab-btn" data-tab="relatorio" aria-selected="false">Emitir Relatório</button>
                </div>

                <div id="painel" class="tab-content active">
                    <h2 class="profile-heading">Itens Pendentes de Aprovação</h2>
                    <p class="profile-subheading">Aprove ou reprove os eventos e pontos turísticos cadastrados.</p>

                    <div class="search-container" style="margin-bottom: 20px;">
                        <div class="search-input-wrapper">
                            <input type="text" class="search-input" id="painel-search-input" placeholder="Pesquisar em itens pendentes...">
                            <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </div>
                    </div>

                    <div class="tabs-secondary">
                        <button class="tab-secondary-btn active" data-tab="pendentes-eventos" aria-selected="true">Eventos Pendentes</button>
                        <button class="tab-secondary-btn" data-tab="pendentes-pontos" aria-selected="false">Pontos Turísticos Pendentes</button>
                    </div>

                    <div id="pendentes-eventos" class="tab-secondary-content active">
                        <h3 class="profile-subheading list-title">Eventos Aguardando Aprovação</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nome do Evento</th>
                                        <th>Cidade</th>
                                        <th>Data de Abertura</th>
                                        <th>Status</th>
                                        <th style="text-align:center;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="eventos-pendentes-tbody">
                                    <tr><td colspan="5" style="text-align:center; padding: 20px;">Carregando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div id="pendentes-pontos" class="tab-secondary-content">
                        <h3 class="profile-subheading list-title">Pontos Turísticos Aguardando Aprovação</h3>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Nome do Ponto</th>
                                        <th>Cidade</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th style="text-align:center;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="pontos-pendentes-tbody">
                                    <tr><td colspan="5" style="text-align:center; padding: 20px;">Carregando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="relatorio" class="tab-content">
                    <div class="relatorio-section">
                        <h2 class="profile-heading">Relatórios</h2>
                        <p class="profile-subheading">Emita relatórios detalhados.</p>
                        <form id="form-relatorio"> 
                            <div class="relatorio-search-container">
                                    <button type="button" class="btn-exportar">Exportar Relatório CSV</button>
                                </div>
                                <div class="relatorio-filtros">
                                    <div class="filtro-group">
                                        <label class="filtro-label" for="tipo-relatorio">Tipo</label>
                                        <select class="filtro-select" id="tipo-relatorio">
                                            <option value="">Selecione o Tipo</option>
                                            <option value="cidades">Cidades</option>
                                            <option value="pontos">Pontos Turísticos</option>
                                            <option value="eventos">Eventos</option>
                                        </select>
                                    </div>
                                    <div class="filtro-group">
                                        <label class="filtro-label" for="estado-relatorio">Estado</label>
                                        <select class="filtro-select" id="estado-relatorio">
                                            <option value="">Todos</option>
                                            <option value="AC">Acre</option><option value="AL">Alagoas</option><option value="AP">Amapá</option><option value="AM">Amazonas</option><option value="BA">Bahia</option><option value="CE">Ceará</option><option value="DF">Distrito Federal</option><option value="ES">Espírito Santo</option><option value="GO">Goiás</option><option value="MA">Maranhão</option><option value="MT">Mato Grosso</option><option value="MS">Mato Grosso do Sul</option><option value="MG">Minas Gerais</option><option value="PA">Pará</option><option value="PB">Paraíba</option><option value="PR">Paraná</option><option value="PE">Pernambuco</option><option value="PI">Piauí</option><option value="RJ">Rio de Janeiro</option><option value="RN">Rio Grande do Norte</option><option value="RS">Rio Grande do Sul</option><option value="RO">Rondônia</option><option value="RR">Roraima</option><option value="SC">Santa Catarina</option><option value="SP">São Paulo</option><option value="SE">Sergipe</option><option value="TO">Tocantins</option>
                                        </select>
                                    </div>
                                    <div class="filtro-group">
                                        <label class="filtro-label" for="periodo-relatorio">Período Cadastro</label>
                                        <select class="filtro-select" id="periodo-relatorio">
                                            <option value="">Todo o período</option>
                                            <option value="7dias">Últimos 7 dias</option>
                                            <option value="30dias">Últimos 30 dias</option>
                                            <option value="custom">Personalizado</option>
                                        </select>
                                    </div>
                                    <div class="filtro-group">
                                        <label class="filtro-label" for="status-relatorio">Status do Item</label>
                                        <select class="filtro-select" id="status-relatorio">
                                            <option value="">Todos</option>
                                            <option value="aprovado">Aprovado</option>
                                            <option value="pendente">Em Análise</option>
                                            <option value="reprovado">Reprovado</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="datas-personalizadas" style="display: none; margin-top: 15px; gap: 15px; flex-wrap:wrap; align-items: flex-end;">
                                    <div class="filtro-group" style="flex-basis: calc(50% - 7.5px);">
                                        <label class="filtro-label" for="data-inicial">Data inicial (Cadastro)</label>
                                        <input type="date" class="filtro-select" id="data-inicial">
                                    </div>
                                    <div class="filtro-group" style="flex-basis: calc(50% - 7.5px);">
                                        <label class="filtro-label" for="data-final">Data final (Cadastro)</label>
                                        <input type="date" class="filtro-select" id="data-final">
                                    </div>
                                </div>
                                <div class="divider" style="margin: 20px 0;"></div>
                                <div class="relatorio-tabela-container">
                                    <div class="relatorio-resultado" id="relatorio-resultado">
                                       <p>Selecione os filtros e clique em "Exportar Relatório CSV" para baixar.</p>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
  <script src="adm.js"></script>
</body>
</html>
