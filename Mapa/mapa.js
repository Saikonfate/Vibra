document.addEventListener('DOMContentLoaded', () => {
    // Elementos da UI do Mapa
    const categoryFiltersSidebar = document.getElementById("category-filters-sidebar");
    const eventInfoSidebar = document.getElementById("event-info-sidebar");
    const overlayMap = document.getElementById("map-elements-overlay"); // Overlay para as sidebars do mapa

    // Botões para controlar as sidebars de filtros e informações do evento
    const mapFiltersBtnJS = document.getElementById("map-filters-btn"); 
    const closeCategoryFiltersBtnJS = document.getElementById("close-category-filters");
    const closeEventInfoBtnJS = document.getElementById("close-event-info");

    const eventDetailsContainer = document.getElementById("event-info-content");
    const eventListTitle = document.getElementById("event-list-title");

    let map;
    let allMarkersLayerGroup;
    let allFetchedLocais = [];

    // --- Funções de Controle das Sidebars (Filtros e Info) e Overlay do Mapa ---
    function openMapSidebar(sidebarElement) {
        console.log('Tentando abrir sidebar do mapa:', sidebarElement ? sidebarElement.id : 'Elemento indefinido');
        
        // Fecha a outra sidebar do MAPA (se houver)
        if (sidebarElement === categoryFiltersSidebar && eventInfoSidebar.classList.contains('visible')) {
            eventInfoSidebar.classList.remove('visible');
        } else if (sidebarElement === eventInfoSidebar && categoryFiltersSidebar.classList.contains('visible')) {
            categoryFiltersSidebar.classList.remove('visible');
        }
        
        if (sidebarElement) {
            sidebarElement.classList.add("visible");
            console.log(sidebarElement.id, 'classes APÓS add .visible:', sidebarElement.className);
        }
        if (overlayMap) {
            overlayMap.classList.add("visible"); // Mostra o overlay dos elementos do mapa
            overlayMap.style.display = 'block'; 
            console.log('Overlay do mapa (#map-elements-overlay) tornado visível.');
        }
        
        // Fecha a user-sidebar (controlada por mapa.php) se estiver aberta
        const userSidebarGlobal = document.getElementById('user-sidebar');
        const userOverlayGlobal = document.getElementById('user-sidebar-overlay');
        const userBtnGlobal = document.querySelector('.barra .user-btn');

        if (userSidebarGlobal && userSidebarGlobal.classList.contains("visible")) {
            console.log('Fechando user-sidebar porque uma sidebar do mapa (' + (sidebarElement ? sidebarElement.id : '') + ') foi aberta.');
            userSidebarGlobal.classList.remove("visible");
            if (userOverlayGlobal) userOverlayGlobal.style.display = 'none';
            if (userBtnGlobal) userBtnGlobal.setAttribute('aria-expanded', 'false');
        }
    }

    function closeMapSidebar(sidebarElement) {
        console.log('Tentando fechar sidebar do mapa:', sidebarElement ? sidebarElement.id : 'Elemento indefinido');
        if (sidebarElement) {
            sidebarElement.classList.remove("visible");
            console.log(sidebarElement.id, 'classes APÓS remove .visible:', sidebarElement.className);
        }

        // Verifica se ALGUMA sidebar do mapa ainda está visível
        const isCategoryVisible = categoryFiltersSidebar?.classList.contains("visible");
        const isEventInfoVisible = eventInfoSidebar?.classList.contains("visible");

        if (!isCategoryVisible && !isEventInfoVisible && overlayMap) {
            overlayMap.classList.remove("visible");
            overlayMap.style.display = 'none'; // Garante que está none
            console.log('Overlay do mapa (#map-elements-overlay) ocultado.');
        }
    }

    // Event Listeners para Sidebars de Filtros e Info
    if (mapFiltersBtnJS && categoryFiltersSidebar) {
        mapFiltersBtnJS.addEventListener("click", (e) => {
            e.stopPropagation();
            // Se já estiver visível, o toggle vai esconder. Se não, vai mostrar.
            const vaiFicarVisivel = !categoryFiltersSidebar.classList.contains("visible");
            if (vaiFicarVisivel) {
                openMapSidebar(categoryFiltersSidebar);
            } else {
                closeMapSidebar(categoryFiltersSidebar);
            }
        });
    }

    if (closeCategoryFiltersBtnJS) {
        closeCategoryFiltersBtnJS.addEventListener("click", (e) => {
            e.stopPropagation();
            closeMapSidebar(categoryFiltersSidebar);
        });
    }

    if (closeEventInfoBtnJS) {
        closeEventInfoBtnJS.addEventListener("click", (e) => {
            e.stopPropagation();
            closeMapSidebar(eventInfoSidebar);
        });
    }

    if (overlayMap) { 
        overlayMap.addEventListener("click", () => {
            console.log('Overlay do mapa clicado. Fechando sidebars do mapa.');
            closeMapSidebar(categoryFiltersSidebar); 
            closeMapSidebar(eventInfoSidebar);
        });
    }

    // --- Lógica do Mapa Leaflet ---
    function initMap() {
        if (document.getElementById('map')) {
            map = L.map('map').setView([-14.235004, -51.92528], 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            allMarkersLayerGroup = L.layerGroup().addTo(map);
            fetchMapData();
        } else {
            console.error("Elemento #map não encontrado. O mapa não pode ser inicializado.");
        }
    }

    async function fetchMapData() {
        try {
            const response = await fetch('../buscar_locais_mapa.php');
            
            console.log('[mapa.js] Fetch response status:', response.status);
            console.log('[mapa.js] Fetch response content-type:', response.headers.get('Content-Type'));

            if (!response.ok) {
                const errorText = await response.text();
                console.error('[mapa.js] Fetch error response text:', errorText);
                throw new Error(`HTTP error! status: ${response.status}, Response: ${errorText}`);
            }
            
            const result = await response.json();
            console.log('[mapa.js] Dados recebidos de buscar_locais_mapa.php:', result);


            if (result.success && Array.isArray(result.data)) {
                allFetchedLocais = result.data;
                addMarkersToMap(allFetchedLocais);
                 if (allFetchedLocais.length === 0 && eventDetailsContainer) {
                    eventDetailsContainer.innerHTML = `<p>${result.message || 'Nenhum local encontrado.'}</p>`;
                }
            } else {
                console.error("[mapa.js] Erro ao buscar dados do mapa ou formato inesperado:", result.message || "Resposta não contém 'success: true' ou 'data' não é um array.", "Resultado completo:", result);
                allFetchedLocais = [];
                addMarkersToMap([]);
                if (eventDetailsContainer) eventDetailsContainer.innerHTML = `<p>${result.message || 'Não foi possível carregar os locais.'}</p>`;
            }
        } catch (error) {
            console.error("[mapa.js] Erro crítico ao buscar dados do mapa:", error);
            if (eventDetailsContainer) eventDetailsContainer.innerHTML = "<p>Erro ao carregar locais no mapa. Verifique o console para detalhes.</p>";
        }
    }

    function addMarkersToMap(locais) {
        allMarkersLayerGroup.clearLayers();
        if (!locais || locais.length === 0) {
            console.log('[mapa.js] Nenhum local para adicionar ao mapa.');
            return;
        }
        locais.forEach(local => {
            if (local.latitude && local.longitude && parseFloat(local.latitude) !== 0 && parseFloat(local.longitude) !== 0) {
                const marker = L.marker([local.latitude, local.longitude]);
                marker.localData = local;
                marker.on('click', () => {
                    showEventDetails(local); 
                    map.setView([local.latitude, local.longitude], 13);
                });
                allMarkersLayerGroup.addLayer(marker);
            }
        });
    }

    function showEventDetails(item) {
        if (!eventDetailsContainer || !eventListTitle) {
            console.error("[mapa.js] Elementos da sidebar de detalhes não encontrados.");
            return;
        }
        console.log('[mapa.js] Mostrando detalhes para:', item.nome);
        eventListTitle.textContent = item.nome;
        let detailsHTML = `<h3>${item.nome || 'Detalhes do Local'}</h3>
                           <p><strong>Tipo:</strong> ${item.tipo || 'N/D'}</p>
                           <p><strong>Descrição:</strong> ${item.descricao || 'Nenhuma descrição disponível.'}</p>`;
        if (item.item_tipo === 'ponto') {
            detailsHTML += `<p><strong>Endereço:</strong> ${item.endereco || 'N/D'}</p>`;
            if (item.horario_abertura_fmt && item.horario_fechamento_fmt) {
                detailsHTML += `<p><strong>Horário:</strong> Das ${item.horario_abertura_fmt} às ${item.horario_fechamento_fmt}</p>`;
            }
            if (item.taxaentrada !== null) {
                detailsHTML += `<p><strong>Entrada:</strong> ${parseFloat(item.taxaentrada) > 0 ? 'R$ ' + parseFloat(item.taxaentrada).toFixed(2).replace('.', ',') : 'Gratuita'}</p>`;
            }
        } else if (item.item_tipo === 'evento') {
            detailsHTML += `<p><strong>Local:</strong> ${item.local_evento || 'N/D'}</p>`;
            if (item.data_inicio_fmt) detailsHTML += `<p><strong>Início:</strong> ${item.data_inicio_fmt}</p>`;
            if (item.data_fim_fmt) detailsHTML += `<p><strong>Fim:</strong> ${item.data_fim_fmt}</p>`;
             if (item.taxaentrada !== null) {
                detailsHTML += `<p><strong>Entrada:</strong> ${parseFloat(item.taxaentrada) > 0 ? 'R$ ' + parseFloat(item.taxaentrada).toFixed(2).replace('.', ',') : 'Gratuita'}</p>`;
            }
        }
        detailsHTML += `<a href="../Eventos-Detalhes/detalhes.php?tipo=${item.item_tipo === 'ponto' ? 'ponto' : 'evento'}&id=${item.id}" class="view-details-link" target="_blank">Ver mais detalhes</a>`;
        eventDetailsContainer.innerHTML = detailsHTML;
        
        openMapSidebar(eventInfoSidebar); // Abre a sidebar de detalhes do evento e o overlay do mapa
    }

    const categoryItemsMap = document.querySelectorAll("#category-list .category-item");
    categoryItemsMap.forEach(item => {
        item.addEventListener("click", (e) => {
            e.stopPropagation();
            const selectedCategory = item.getAttribute("data-category-value");
            categoryItemsMap.forEach(catItem => catItem.classList.remove("active"));
            item.classList.add("active");
            filterMarkersByCategory(selectedCategory);
            closeMapSidebar(categoryFiltersSidebar);
        });
    });

    function filterMarkersByCategory(category) {
        console.log('[mapa.js] Filtrando por categoria:', category);
        const filteredLocais = allFetchedLocais.filter(local => {
            if (category === 'all' || !category) return true;
            return local.tipo && local.tipo.toLowerCase() === category.toLowerCase();
        });
        addMarkersToMap(filteredLocais);
    }
    
    initMap();
});