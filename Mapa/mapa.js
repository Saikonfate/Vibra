function formatDateToDDMMYYYY(input) {
    const value = input.value;
    if (!value) return;
    const [year, month, day] = value.split("-");
    input.setAttribute("data-date", `${day}-${month}-${year}`);
    input.valueAsDate = new Date(value);
}

const inputs = document.querySelectorAll(".custom-date");
inputs.forEach(input => {
    input.addEventListener("change", () => formatDateToDDMMYYYY(input));
    formatDateToDDMMYYYY(input);
});

function getTodayDateString() {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function updateMinDate() {
    const todayStr = getTodayDateString();
    document.getElementById("date2").setAttribute("min", todayStr);
}
updateMinDate();
setInterval(updateMinDate, 86400000);

function setInitialDates() {
    const today = getTodayDateString();

    const input1 = document.getElementById("date1");
    const input2 = document.getElementById("date2");

    input1.value = today;
    input2.value = today;

    formatDateToDDMMYYYY(input1);
    formatDateToDDMMYYYY(input2);
}
setInitialDates();

function toggleSidebar() {
    document.getElementById("dropdown").classList.toggle("show");
}


function toggleSidebar() {
    document.getElementById("sidebar1").classList.toggle("visible");
}


window.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar1");
    const button = document.querySelector(".user-btn");
    if (!sidebar.contains(event.target) && !button.contains(event.target)) {
      sidebar.classList.remove("visible");
    }
});

const btnCategoria = document.getElementById('btnCategoria');
const menuCategoria = document.getElementById('menuCategoria');
const overlay = document.getElementById('overlay');

btnCategoria.addEventListener('click', () => {
  menuCategoria.style.display = 'grid';
  overlay.style.display = 'block';
});

overlay.addEventListener('click', () => {
  menuCategoria.style.display = 'none';
  overlay.style.display = 'none';
});

// Dados de lugares em Juazeiro do Norte
const places = [
    { id: 1, name: "Teleférico do Horto - Estação Monsenhor Murilo de Sá Barreto", category: "Passeios", lat: -7.1637, lng: -39.3189, 
      description: "Teleférico que leva ao Alto do Horto com vista panorâmica para toda a cidade e região do Cariri." },
    { id: 2, name: "Teatro Marquise Branca", category: "Teatros e Espetáculos", lat: -7.2104, lng: -39.3164, 
      description: "Teatro com programação diversificada de espetáculos e peças localizado no Centro Cultural do Cariri." },
    { id: 3, name: "Restaurante Sabor da Terra", category: "Restaurantes", lat: -7.2133, lng: -39.3158, 
      description: "Restaurante especializado em comida típica nordestina com ambiente aconchegante e clima familiar." },
    { id: 4, name: "Centro de Convenções do Cariri", category: "Congressos e Palestras", lat: -7.2063, lng: -39.3200, 
      description: "Espaço para eventos acadêmicos e empresariais com capacidade para mais de 1000 pessoas." },
    { id: 5, name: "Arena Romeirão", category: "Esportes", lat: -7.2199, lng: -39.3252, 
      description: "Estádio de futebol que recebe jogos regionais e nacionais, com capacidade para 17 mil torcedores." },
    { id: 6, name: "Parque de Exposições Pedro Felício Cavalcanti", category: "Festas e Shows", lat: -7.2361, lng: -39.3075, 
      description: "Local que abriga grandes shows, exposições e a tradicional ExpoCrato." },
    { id: 7, name: "Espaço Sesc Juazeiro", category: "Stand Up e Comédia", lat: -7.2115, lng: -39.3145, 
      description: "Espaço que recebe regularmente apresentações de stand up comedy e outros eventos culturais." },
    { id: 8, name: "Parque Ecológico das Timbaúbas", category: "Infantil", lat: -7.2021, lng: -39.3365, 
      description: "Parque com trilhas ecológicas, playground e diversas atividades para crianças em contato com a natureza." },
    { id: 9, name: "Memorial Padre Cícero", category: "Passeios", lat: -7.2101, lng: -39.3151, 
      description: "Museu que conta a história do Padre Cícero Romão Batista, fundador de Juazeiro do Norte." },
    { id: 10, name: "Basílica Santuário de Nossa Senhora das Dores", category: "Passeios", lat: -7.2103, lng: -39.3158, 
      description: "Principal igreja de Juazeiro do Norte, ponto central de romarias e visitação religiosa." },
    { id: 11, name: "Centro Cultural Banco do Nordeste", category: "Cursos e Workshop", lat: -7.2112, lng: -39.3143, 
      description: "Centro que oferece diversos cursos, oficinas e atividades culturais gratuitas." },
    { id: 12, name: "Estátua do Padre Cícero (Horto)", category: "Passeios", lat: -7.1696, lng: -39.3246, 
      description: "Estátua com 27 metros de altura, um dos principais pontos turísticos religiosos da região." },
    { id: 13, name: "Restaurante Bode Assado", category: "Restaurantes", lat: -7.2179, lng: -39.3225, 
      description: "Restaurante especializado em carne de bode e comida típica regional, muito frequentado por turistas e romeiros." },
    { id: 14, name: "Centro de Cultura Popular Mestre Noza", category: "Passeios", lat: -7.2103, lng: -39.3166, 
      description: "Espaço dedicado ao artesanato regional e exposições de arte popular nordestina." },
    { id: 15, name: "Ginásio Poliesportivo de Juazeiro", category: "Esportes", lat: -7.2126, lng: -39.3281, 
      description: "Complexo esportivo que abriga competições regionais de diversas modalidades." },
];

// Eventos de exemplo em Juazeiro do Norte
const events = [
    { id: 101, name: "Romaria de Nossa Senhora das Candeias", place_id: 10, category: "Festas e Shows", date: "Sábado, 02 de Fev de 2026", 
      description: "Uma das principais romarias de Juazeiro do Norte com procissões, missas e shows religiosos." },
    { id: 102, name: "Tour pelo Circuito Religioso", place_id: 9, category: "Passeios", date: "Domingo, 18 de Mai de 2025", 
      description: "Passeio guiado pelos principais pontos religiosos de Juazeiro do Norte com guia local." },
    { id: 103, name: "Festival Gastronômico do Cariri", place_id: 3, category: "Restaurantes", date: "Sexta, 23 de Mai de 2025", 
      description: "Festival com os melhores pratos típicos da culinária caririense com chefs locais." },
    { id: 104, name: "Noite de Humor Cearense", place_id: 7, category: "Stand Up e Comédia", date: "Sábado, 24 de Mai de 2025", 
      description: "Show com os melhores humoristas cearenses e suas piadas regionais." },
    { id: 105, name: "Campeonato Caririense de Futebol", place_id: 5, category: "Esportes", date: "Domingo, 25 de Mai de 2025", 
      description: "Final do campeonato regional com os melhores times do Cariri." },
    { id: 106, name: "Workshop de Xilogravura", place_id: 11, category: "Cursos e Workshop", date: "Sábado, 17 de Mai de 2025", 
      description: "Aprenda a tradicional arte da xilogravura nordestina com mestres artesãos locais." },
    { id: 107, name: "Espetáculo: Paixão de Cristo do Cariri", place_id: 2, category: "Teatros e Espetáculos", date: "Quinta, 10 de Abr de 2025", 
      description: "Encenação da Paixão de Cristo com atores locais em grande produção regional." },
    { id: 108, name: "Dia das Crianças no Parque Ecológico", place_id: 8, category: "Infantil", date: "Domingo, 12 de Out de 2025", 
      description: "Evento especial para o Dia das Crianças com brincadeiras, oficinas e teatro infantil." },
    { id: 109, name: "Congresso de Desenvolvimento Regional do Cariri", place_id: 4, category: "Congressos e Palestras", date: "Sexta, 30 de Mai de 2025", 
      description: "Evento com palestras sobre desenvolvimento econômico e social da região do Cariri." },
    { id: 110, name: "Festa de São João da Matriz", place_id: 10, category: "Festas e Shows", date: "Terça, 24 de Jun de 2025", 
      description: "Tradicional festa junina com quadrilhas, comidas típicas e shows de forró pé de serra." },
    { id: 111, name: "ExpoCrato", place_id: 6, category: "Festas e Shows", date: "Sábado, 12 de Jul de 2025", 
      description: "Maior feira agropecuária da região com exposições, leilões e grandes shows nacionais." },
    { id: 112, name: "Festival Nordestino de Teatro", place_id: 2, category: "Teatros e Espetáculos", date: "Segunda, 07 de Jul de 2025", 
      description: "Festival que reúne grupos teatrais de todo o Nordeste com apresentações diárias." },
    { id: 113, name: "Passeio de Teleférico com Guia", place_id: 1, category: "Passeios", date: "Sábado, 31 de Mai de 2025", 
      description: "Passeio especial no teleférico com explicações históricas e culturais sobre a região." },
    { id: 114, name: "Semana do Padre Cícero", place_id: 12, category: "Passeios", date: "Segunda, 20 de Jul de 2025", 
      description: "Semana de eventos em celebração ao aniversário de falecimento do Padre Cícero." },
    { id: 115, name: "Oficina de Artesanato em Couro", place_id: 14, category: "Cursos e Workshop", date: "Domingo, 01 de Jun de 2025", 
      description: "Aprenda técnicas tradicionais de artesanato em couro com mestres artesãos locais." },
];

let activeCategory = "all";
let userLocation = null;
let mapMarkers = [];
let activeEvent = null;
let map = null;

const mapElement = document.getElementById('map');
const tabs = document.querySelectorAll('.tab');
const tabContents = document.querySelectorAll('.tab-content');
const categoryItems = document.querySelectorAll('.category-item');
const nearbyTab = document.getElementById('nearby');
const eventInfoTab = document.getElementById('event-info');


const categoryIcons = {
    'party': L.divIcon({
        className: 'custom-marker party-marker',
        html: '<i class="fas fa-music"></i>',
        iconSize: [30, 30]
    }),
    'tour': L.divIcon({
        className: 'custom-marker tour-marker',
        html: '<i class="fas fa-route"></i>',
        iconSize: [30, 30]
    }),
    'restaurant': L.divIcon({
        className: 'custom-marker restaurant-marker',
        html: '<i class="fas fa-utensils"></i>',
        iconSize: [30, 30]
    }),
    'comedy': L.divIcon({
        className: 'custom-marker comedy-marker',
        html: '<i class="fas fa-laugh"></i>',
        iconSize: [30, 30]
    }),
    'sports': L.divIcon({
        className: 'custom-marker sports-marker',
        html: '<i class="fas fa-football-ball"></i>',
        iconSize: [30, 30]
    }),
    'workshop': L.divIcon({
        className: 'custom-marker workshop-marker',
        html: '<i class="fas fa-chalkboard-teacher"></i>',
        iconSize: [30, 30]
    }),
    'theater': L.divIcon({
        className: 'custom-marker theater-marker',
        html: '<i class="fas fa-theater-masks"></i>',
        iconSize: [30, 30]
    }),
    'kids': L.divIcon({
        className: 'custom-marker kids-marker',
        html: '<i class="fas fa-child"></i>',
        iconSize: [30, 30]
    }),
    'congress': L.divIcon({
        className: 'custom-marker congress-marker',
        html: '<i class="fas fa-microphone"></i>',
        iconSize: [30, 30]
    })
};

function initMap() {
    map = L.map('map').setView([-7.2125, -39.3195], 14);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    addMarkersToMap();
    
    getUserLocation();
}

function addMarkersToMap() {
    clearMapMarkers();
    
    const filteredPlaces = activeCategory === "all" 
        ? places 
        : places.filter(place => {

            const categoryMapping = {
                "Festas e Shows": "party",
                "Passeios": "tour",
                "Restaurantes": "restaurant",
                "Stand Up e Comédia": "comedy",
                "Esportes": "sports",
                "Cursos e Workshop": "workshop",
                "Teatros e Espetáculos": "theater",
                "Infantil": "kids",
                "Congressos e Palestras": "congress"
            };
            
            return categoryMapping[place.category] === activeCategory;
        });
    
    filteredPlaces.forEach(place => {
        const categoryMapping = {
            "Festas e Shows": "party",
            "Passeios": "tour",
            "Restaurantes": "restaurant",
            "Stand Up e Comédia": "comedy",
            "Esportes": "sports",
            "Cursos e Workshop": "workshop",
            "Teatros e Espetáculos": "theater",
            "Infantil": "kids",
            "Congressos e Palestras": "congress"
        };
        
        const iconKey = categoryMapping[place.category] || "default";
        const icon = categoryIcons[iconKey] || L.divIcon({
            className: 'custom-marker default-marker',
            html: '<i class="fas fa-map-marker-alt"></i>',
            iconSize: [30, 30]
        });
        
        // Cria o marcador
        const marker = L.marker([place.lat, place.lng], { icon: icon })
            .addTo(map)
            .bindPopup(`
                <h3>${place.name}</h3>
                <p>${place.description}</p>
                <button class="view-events-btn" data-place-id="${place.id}">Ver Eventos</button>
            `);
        
        marker.on('popupopen', () => {
            setTimeout(() => {
                const btn = document.querySelector(`.view-events-btn[data-place-id="${place.id}"]`);
                if (btn) {
                    btn.addEventListener('click', () => showPlaceEvents(place.id));
                }
            }, 10);
        });
        
        mapMarkers.push(marker);
    });
}

function clearMapMarkers() {
    mapMarkers.forEach(marker => map.removeLayer(marker));
    mapMarkers = [];
}

function showPlaceEvents(placeId) {
    const placeEvents = events.filter(event => event.place_id === placeId);
    
    switchTab('event-info');
    
    const place = places.find(p => p.id === placeId);
    
    let eventsHTML = '';
    if (placeEvents.length > 0) {
        eventsHTML = '<h3>Eventos neste local:</h3><ul>';
        placeEvents.forEach(event => {
            eventsHTML += `
                <li class="event-item" data-event-id="${event.id}">
                    <h4>${event.name}</h4>
                    <p class="event-date">${event.date}</p>
                    <p>${event.description}</p>
                </li>
            `;
        });
        eventsHTML += '</ul>';
    } else {
        eventsHTML = '<p>Nenhum evento programado para este local.</p>';
    }
    
    eventInfoTab.innerHTML = `
        <div class="place-details">
            <h2>${place.name}</h2>
            <p class="place-category">Categoria: ${place.category}</p>
            <p>${place.description}</p>
            <div class="events-list">
                ${eventsHTML}
            </div>
            <button id="back-to-map">Voltar ao Mapa</button>
        </div>
    `;
    
    document.getElementById('back-to-map').addEventListener('click', () => {
        switchTab('categories');
    });
}

function switchTab(tabId) {
    tabs.forEach(tab => tab.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));
    
    document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
    document.getElementById(tabId).classList.add('active');
}

function getUserLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                const userMarker = L.marker([userLocation.lat, userLocation.lng], {
                    icon: L.divIcon({
                        className: 'custom-marker user-marker',
                        html: '<i class="fas fa-user"></i>',
                        iconSize: [30, 30]
                    })
                }).addTo(map).bindPopup('Sua localização atual');
                
                updateNearbyPlaces();
            },
            (error) => {
                console.error('Erro ao obter localização:', error);
                alert('Não foi possível obter sua localização. Verifique as permissões.');
            }
        );
    } else {
        alert('Seu navegador não suporta geolocalização.');
    }
}

function updateNearbyPlaces() {
    if (!userLocation) return;

    const placesWithDistance = places.map(place => {
        const distance = calculateDistance(
            userLocation.lat, userLocation.lng,
            place.lat, place.lng
        );
        return { ...place, distance };
    });

    const nearbyPlaces = placesWithDistance
        .sort((a, b) => a.distance - b.distance)
        .slice(0, 5);

    let nearbyHTML = '<h3>Lugares próximos a você:</h3><ul class="nearby-list">';

    nearbyPlaces.forEach(place => {
        nearbyHTML += `
            <li class="nearby-item" data-place-id="${place.id}">
                <h4>${place.name}</h4>
                <p class="nearby-category">${place.category}</p>
                <p class="nearby-distance">${place.distance.toFixed(2)} km</p>
                <button class="view-events-btn" data-place-id="${place.id}">Ver Eventos</button>
            </li>
        `;
    });

    nearbyHTML += '</ul>';

    nearbyTab.innerHTML = nearbyHTML;

    document.querySelectorAll('.nearby-item .view-events-btn').forEach(button => {
        button.addEventListener('click', () => {
            const placeId = parseInt(button.getAttribute('data-place-id'));
            showPlaceEvents(placeId);
        });
    });
}

function calculateDistance(lat1, lng1, lat2, lng2) {
    const toRad = x => x * Math.PI / 180;
    const R = 6371;

    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);

    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

categoryItems.forEach(item => {
    item.addEventListener('click', () => {
        categoryItems.forEach(i => i.classList.remove('active'));
        item.classList.add('active');
        
        activeCategory = item.getAttribute('data-category');
        
        addMarkersToMap();
    });
});

document.addEventListener('DOMContentLoaded', initMap);