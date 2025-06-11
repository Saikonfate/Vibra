document.addEventListener('DOMContentLoaded', () => {
    // Elementos da UI do Mapa
    const categoryFiltersSidebar = document.getElementById("category-filters-sidebar");
    const eventInfoSidebar = document.getElementById("event-info-sidebar");
    const overlayMap = document.getElementById("map-elements-overlay");

    // Botões
    const mapFiltersBtnJS = document.getElementById("map-filters-btn");
    const closeCategoryFiltersBtnJS = document.getElementById("close-category-filters");
    const closeEventInfoBtnJS = document.getElementById("close-event-info");

    const eventDetailsContainer = document.getElementById("event-info-content");
    const eventListTitle = document.getElementById("event-list-title");

    let map;
    let allMarkersLayerGroup;
    let allFetchedLocais = [];

    // Definição dos Ícones Personalizados
    const pontoIcon = new L.Icon({
        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
        iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });

    const eventoIcon = new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        iconRetinaUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // --- Lógica de Controle das Sidebars ---
    function openMapSidebar(sidebarElement) {
        if (sidebarElement === categoryFiltersSidebar && eventInfoSidebar.classList.contains('visible')) {
            eventInfoSidebar.classList.remove('visible');
        } else if (sidebarElement === eventInfoSidebar && categoryFiltersSidebar.classList.contains('visible')) {
            categoryFiltersSidebar.classList.remove('visible');
        }
        if (sidebarElement) sidebarElement.classList.add("visible");
        if (overlayMap) overlayMap.style.display = 'block';
        
        const userSidebarGlobal = document.getElementById('user-sidebar');
        if (userSidebarGlobal && userSidebarGlobal.classList.contains("visible")) {
            userSidebarGlobal.classList.remove("visible");
            const userOverlayGlobal = document.getElementById('user-sidebar-overlay');
            if (userOverlayGlobal) userOverlayGlobal.style.display = 'none';
        }
    }

    function closeMapSidebar(sidebarElement) {
        if (sidebarElement) sidebarElement.classList.remove("visible");
        const isAnyMapSidebarVisible = categoryFiltersSidebar?.classList.contains("visible") || eventInfoSidebar?.classList.contains("visible");
        if (!isAnyMapSidebarVisible && overlayMap) overlayMap.style.display = 'none';
    }

    if (closeCategoryFiltersBtnJS) closeCategoryFiltersBtnJS.addEventListener("click", () => closeMapSidebar(categoryFiltersSidebar));
    if (closeEventInfoBtnJS) closeEventInfoBtnJS.addEventListener("click", () => closeMapSidebar(eventInfoSidebar));
    if (overlayMap) overlayMap.addEventListener("click", () => {
        closeMapSidebar(categoryFiltersSidebar);
        closeMapSidebar(eventInfoSidebar);
    });

    // --- Lógica do Mapa Leaflet ---
    function initMap() {
        if (!document.getElementById('map')) {
            console.error("Elemento #map não encontrado.");
            return;
        }

        // --- INICIALIZAÇÃO DO MAPA COM BASE NA URL ---
        const urlParams = new URLSearchParams(window.location.search);
        const initialLat = urlParams.get('lat');
        const initialLon = urlParams.get('lon');

        if (initialLat && initialLon && !isNaN(initialLat) && !isNaN(initialLon)) {
            map = L.map('map').setView([parseFloat(initialLat), parseFloat(initialLon)], 15);
        } else {
            map = L.map('map').setView([-14.235004, -51.92528], 4);
        }

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        allMarkersLayerGroup = L.layerGroup().addTo(map);
        fetchMapData();
    }

    async function fetchMapData() {
        try {
            const response = await fetch('../buscar_locais_mapa.php');
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                allFetchedLocais = result.data;
                addMarkersToMap(allFetchedLocais);
            } else {
                console.error("Erro ao buscar dados do mapa:", result.message);
            }
        } catch (error) {
            console.error("Erro crítico ao buscar dados do mapa:", error);
        }
    }

    function addMarkersToMap(locais) {
        allMarkersLayerGroup.clearLayers();
        locais.forEach(local => {
            if (local.latitude && local.longitude && parseFloat(local.latitude) !== 0 && parseFloat(local.longitude) !== 0) {
                let markerIcon = (local.item_tipo === 'evento') ? eventoIcon : pontoIcon;
                const marker = L.marker([local.latitude, local.longitude], { icon: markerIcon });
                marker.localData = local;
                marker.on('click', () => {
                    showEventDetails(local);
                    map.setView([local.latitude, local.longitude], 15);
                });
                allMarkersLayerGroup.addLayer(marker);
            }
        });
    }

    function showEventDetails(item) {
        if (!eventDetailsContainer || !eventListTitle) return;
        eventListTitle.textContent = item.nome;
        let detailsHTML = `<h3>${item.nome || 'Detalhes do Local'}</h3>
                           <p><strong>Tipo:</strong> ${item.tipo || 'N/D'}</p>
                           <p><strong>Descrição:</strong> ${item.descricao || 'Nenhuma descrição disponível.'}</p>`;
        if (item.item_tipo === 'ponto') {
            detailsHTML += `<p><strong>Endereço:</strong> ${item.endereco || 'N/D'}</p>`;
        } else if (item.item_tipo === 'evento') {
            detailsHTML += `<p><strong>Local:</strong> ${item.local_evento || 'N/D'}</p>`;
        }
        detailsHTML += `<a href="../Eventos-Detalhes/detalhes.php?tipo=${item.item_tipo}&id=${item.id}" class="view-details-link" target="_blank">Ver mais detalhes</a>`;
        eventDetailsContainer.innerHTML = detailsHTML;
        openMapSidebar(eventInfoSidebar);
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
        const filteredLocais = allFetchedLocais.filter(local =>
            (category === 'all' || !category) ? true : (local.tipo && local.tipo.toLowerCase() === category.toLowerCase())
        );
        addMarkersToMap(filteredLocais);
    }
    
    initMap();
});