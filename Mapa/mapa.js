document.addEventListener('DOMContentLoaded', () => {
    // Elementos da UI do Mapa
    const userSidebar = document.getElementById("user-sidebar");
    const categoryFiltersSidebar = document.getElementById("category-filters-sidebar");
    const eventInfoSidebar = document.getElementById("event-info-sidebar");
    const overlayMap = document.getElementById("overlay");

    const userBtnMap = document.querySelector(".barra .user-btn");
    const mapFiltersBtnJS = document.getElementById("map-filters-btn");
    const closeCategoryFiltersBtnJS = document.getElementById("close-category-filters");
    const closeEventInfoBtnJS = document.getElementById("close-event-info");

    const eventDetailsContainer = document.getElementById("event-info-content");
    const eventListTitle = document.getElementById("event-list-title");

    // Variáveis do Mapa Leaflet
    let map;
    let allMarkersLayerGroup;
    let allFetchedLocais = [];

    // --- Funções de Controle das Sidebars e Overlay ---
    function openSidebar(sidebarElement) {
        closeAllSidebars(sidebarElement);
        if (sidebarElement) sidebarElement.classList.add("visible");
        if (overlayMap) overlayMap.classList.add("visible");
    }

    function closeSidebar(sidebarElement) {
        if (sidebarElement) sidebarElement.classList.remove("visible");
        const isAnySidebarVisible = userSidebar?.classList.contains("visible") ||
                                   categoryFiltersSidebar?.classList.contains("visible") ||
                                   eventInfoSidebar?.classList.contains("visible");
        if (!isAnySidebarVisible && overlayMap) {
            overlayMap.classList.remove("visible");
        }
    }

    function closeAllSidebars(excludeSidebar = null) {
        [userSidebar, categoryFiltersSidebar, eventInfoSidebar].forEach(sb => {
            if (sb && sb !== excludeSidebar) {
                sb.classList.remove("visible");
            }
        });
        const isAnySidebarVisibleAfterExclusion = [userSidebar, categoryFiltersSidebar, eventInfoSidebar]
            .some(sb => sb && sb !== excludeSidebar && sb.classList.contains("visible"));

        if (!isAnySidebarVisibleAfterExclusion && (!excludeSidebar || !excludeSidebar.classList.contains("visible"))) {
             if (overlayMap) overlayMap.classList.remove("visible");
        }
    }

    // Event Listeners para Sidebars
    if (userBtnMap && userSidebar) {
        userBtnMap.addEventListener("click", (e) => {
            e.stopPropagation();
            userSidebar.classList.toggle("visible");
            if (userSidebar.classList.contains("visible")) openSidebar(userSidebar);
            else closeSidebar(userSidebar);
        });
    }

    if (mapFiltersBtnJS && categoryFiltersSidebar) {
        mapFiltersBtnJS.addEventListener("click", (e) => {
            e.stopPropagation();
            categoryFiltersSidebar.classList.toggle("visible");
            if (categoryFiltersSidebar.classList.contains("visible")) openSidebar(categoryFiltersSidebar);
            else closeSidebar(categoryFiltersSidebar);
        });
    }

    if (closeCategoryFiltersBtnJS) {
        closeCategoryFiltersBtnJS.addEventListener("click", () => closeSidebar(categoryFiltersSidebar));
    }

    if (closeEventInfoBtnJS) {
        closeEventInfoBtnJS.addEventListener("click", () => closeSidebar(eventInfoSidebar));
    }

    if (overlayMap) {
        overlayMap.addEventListener("click", () => closeAllSidebars());
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
            const response = await fetch('../buscar_locais_mapa.php'); // Ajuste o caminho se necessário
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status}, Response: ${errorText}`);
            }
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                allFetchedLocais = result.data;
                addMarkersToMap(allFetchedLocais);
                 if (allFetchedLocais.length === 0 && eventDetailsContainer) {
                    eventDetailsContainer.innerHTML = `<p>${result.message || 'Nenhum local encontrado.'}</p>`;
                }
            } else {
                console.error("Erro ao buscar dados ou formato de dados inesperado:", result.message || "Resposta não contém 'success: true' ou 'data' não é um array.");
                allFetchedLocais = [];
                addMarkersToMap([]); // Limpa o mapa
                if (eventDetailsContainer) eventDetailsContainer.innerHTML = `<p>${result.message || 'Não foi possível carregar os locais.'}</p>`;
            }
        } catch (error) {
            console.error("Erro crítico ao buscar dados do mapa:", error);
            if (eventDetailsContainer) eventDetailsContainer.innerHTML = "<p>Erro ao carregar locais no mapa. Verifique o console do navegador.</p>";
        }
    }

    function addMarkersToMap(locais) {
        allMarkersLayerGroup.clearLayers();
        if (!locais || locais.length === 0) {
            // console.warn("Nenhum local para adicionar ao mapa nesta filtragem.");
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
            console.error("Elementos da sidebar de detalhes não encontrados.");
            return;
        }
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
             if (item.taxaentrada !== null) { // Adicionado taxa para eventos
                detailsHTML += `<p><strong>Entrada:</strong> ${parseFloat(item.taxaentrada) > 0 ? 'R$ ' + parseFloat(item.taxaentrada).toFixed(2).replace('.', ',') : 'Gratuita'}</p>`;
            }
        }
        detailsHTML += `<a href="../Eventos-Detalhes/detalhes.php?tipo=${item.item_tipo}&id=${item.id}" class="view-details-link" target="_blank">Ver mais detalhes</a>`;
        eventDetailsContainer.innerHTML = detailsHTML;
        openSidebar(eventInfoSidebar);
    }

    // Filtro de Categoria
    const categoryItemsMap = document.querySelectorAll("#category-list .category-item");
    categoryItemsMap.forEach(item => {
        item.addEventListener("click", () => {
            const selectedCategory = item.getAttribute("data-category-value");
            categoryItemsMap.forEach(catItem => catItem.classList.remove("active"));
            item.classList.add("active");
            filterMarkersByCategory(selectedCategory);
            closeSidebar(categoryFiltersSidebar);
        });
    });

    function filterMarkersByCategory(category) {
        const filteredLocais = allFetchedLocais.filter(local => {
            if (category === 'all' || !category) return true;
            return local.tipo && local.tipo.toLowerCase() === category.toLowerCase();
        });
        addMarkersToMap(filteredLocais);
    }

    // Barra de Pesquisa do Mapa
    const mapSearchBar = document.getElementById('map-search-bar');
    if (mapSearchBar) {
        mapSearchBar.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const filteredLocais = allFetchedLocais.filter(local => {
                return (local.nome && local.nome.toLowerCase().includes(searchTerm)) ||
                       (local.descricao && local.descricao.toLowerCase().includes(searchTerm)) ||
                       (local.tipo && local.tipo.toLowerCase().includes(searchTerm)) ||
                       (local.endereco && local.endereco.toLowerCase().includes(searchTerm)) || // Para pontos
                       (local.local_evento && local.local_evento.toLowerCase().includes(searchTerm)); // Para eventos
            });
            addMarkersToMap(filteredLocais);
            if (filteredLocais.length === 0 && searchTerm.length > 0) {
                 if(eventDetailsContainer) eventDetailsContainer.innerHTML = "<p>Nenhum local encontrado para sua busca.</p>";
                 closeSidebar(eventInfoSidebar); // Fecha se estava aberta e não há resultados
            } else if (filteredLocais.length === 1 && searchTerm.length > 0) { // Apenas se houver termo de busca
                showEventDetails(filteredLocais[0]);
                map.setView([filteredLocais[0].latitude, filteredLocais[0].longitude], 13);
            } else if (searchTerm.length === 0) { // Se limpou a busca, restaura mensagem padrão
                 if(eventDetailsContainer && !eventInfoSidebar.classList.contains("visible")) { // Só se a sidebar não estiver já mostrando um item clicado
                    eventDetailsContainer.innerHTML = "<p>Clique em um marcador para ver os detalhes.</p>";
                 }
            }
        });
    }

    // Inicializa o mapa
    initMap();
});