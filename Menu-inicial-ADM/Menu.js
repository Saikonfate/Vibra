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
    document.getElementById("sidebar").classList.toggle("visible");
}


window.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar");
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

document.addEventListener('DOMContentLoaded', function() {
    const navButtons = document.querySelectorAll('.nav-button');
    
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            const direction = this.textContent === '<' ? -1 : 1;
            const container = this.closest('.section').querySelector('.category-container, .event-container');
            
            
            container.scrollBy({
                left: direction * 300,
                behavior: 'smooth'
            });
        });
    });

    
    const categoryCards = document.querySelectorAll('.category-card');
    
    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            
            const categoryName = this.querySelector('.category-name').textContent;
            console.log(`Categoria selecionada: ${categoryName}`);
            
            
            categoryCards.forEach(c => c.style.backgroundColor = 'rgba(255, 255, 255, 0.7)');
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
        });
    });

    
    const eventCards = document.querySelectorAll('.event-card');
    
    eventCards.forEach(card => {
        card.addEventListener('click', function() {
            const eventTitle = this.querySelector('.event-title').textContent;
            console.log(`Evento selecionado: ${eventTitle}`);
            
        });
    });
});

function loadEventData() {
    console.log('Carregando dados de eventos...');
}

loadEventData();