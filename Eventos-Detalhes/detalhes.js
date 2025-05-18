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
const eventImages = [
  "img1.jpg",
  "img2.jpg",
  "img3.jpg",
  "img4.jpg",
  "img5.jpg"
];

let currentImageIndex = 0;
const eventImage = document.getElementById("eventImage");
const indicatorsContainer = document.getElementById("carouselIndicators");

function updateImage() {
  eventImage.src = eventImages[currentImageIndex];
  updateIndicators();
}

function prevImage() {
  currentImageIndex = (currentImageIndex - 1 + eventImages.length) % eventImages.length;
  updateImage();
}

function nextImage() {
  currentImageIndex = (currentImageIndex + 1) % eventImages.length;
  updateImage();
}

function updateIndicators() {
  const dots = document.querySelectorAll('.carousel-indicators .dot');
  dots.forEach((dot, index) => {
    dot.classList.toggle('active', index === currentImageIndex);
  });
}

function createIndicators() {
  indicatorsContainer.innerHTML = ""; // limpa se já existir
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

// Executar quando a página carregar
document.addEventListener("DOMContentLoaded", () => {
  updateImage();
  createIndicators();
});