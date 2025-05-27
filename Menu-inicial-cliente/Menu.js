// --- Date Formatting and Initial Date Set for general date inputs ---
function formatDateToDDMMYYYY(input) {
    const value = input.value;
    if (!value) return;
    const [year, month, day] = value.split("-");
    input.setAttribute("data-date", `${day}-${month}-${year}`);
    // input.valueAsDate = new Date(value); // This can cause issues with date formatting if not careful
}

const inputs = document.querySelectorAll(".custom-date");
inputs.forEach(input => {
    input.addEventListener("change", () => formatDateToDDMMYYYY(input));
    formatDateToDDMMYYYY(input); // Format existing values on load
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
    const date2Input = document.getElementById("date2");
    if (date2Input) { // Ensure element exists before setting attribute
        date2Input.setAttribute("min", todayStr);
    }
}
updateMinDate();
setInterval(updateMinDate, 86400000); // Update every 24 hours

function setInitialDates() {
    const today = getTodayDateString();
    const input1 = document.getElementById("date1");
    const input2 = document.getElementById("date2");

    if (input1) {
        input1.value = today;
        formatDateToDDMMYYYY(input1);
    }
    if (input2) {
        input2.value = today;
        formatDateToDDMMYYYY(input2);
    }
}
setInitialDates();

// --- Sidebar Toggles (there were two toggleSidebar functions, consolidated and renamed one) ---
function toggleSidebar() { // This one was duplicated, keeping one as it might be used elsewhere
    document.getElementById("dropdown").classList.toggle("show");
}

window.toggleClienteSidebar = function() { // Renamed for clarity as per your HTML
    const sidebar = document.getElementById("sidebar");
    if (sidebar) {
        sidebar.classList.toggle("visible");
    }
};

window.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar");
    const button = document.querySelector(".user-btn");
    if (sidebar && button && !sidebar.contains(event.target) && !button.contains(event.target)) {
      sidebar.classList.remove("visible");
    }
});

// --- Category Menu Toggles ---
const btnCategoria = document.getElementById('btnCategoria');
const menuCategoria = document.getElementById('menuCategoria');
const overlay = document.getElementById('overlay');

// Ensure elements exist before adding listeners
if (btnCategoria && menuCategoria && overlay) {
    btnCategoria.addEventListener('click', () => {
        menuCategoria.style.display = 'grid';
        overlay.style.display = 'block';
    });

    overlay.addEventListener('click', () => {
        menuCategoria.style.display = 'none';
        overlay.style.display = 'none';
    });
}


// --- All DOMContentLoaded dependent code consolidated here ---
document.addEventListener('DOMContentLoaded', function() {
    // --- Category and Event Scrolling Navigations ---
    const navButtons = document.querySelectorAll('.nav-button');
    navButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Updated to use window.scrollItemContainer as it's defined globally in your PHP file's script tag
            const containerId = this.closest('.section').querySelector('.category-container, .event-container').id;
            const direction = this.textContent.includes('<') ? -1 : 1;
            // The scrollItemContainer function is defined in the <script> tag in Menu.php
            // So, calling it directly from global scope or ensuring it's available via window is fine.
            if (typeof scrollItemContainer === 'function') {
                scrollItemContainer(containerId, direction);
            }
        });
    });

    // --- Category Card Click Handlers ---
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        card.addEventListener('click', function() {
            const categoryName = this.querySelector('.category-name').textContent;
            console.log(`Categoria selecionada: ${categoryName}`);
            categoryCards.forEach(c => c.style.backgroundColor = 'rgba(255, 255, 255, 0.7)');
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
        });
    });

    // --- Event Card Click Handlers ---
    const eventCards = document.querySelectorAll('.event-card');
    eventCards.forEach(card => {
        card.addEventListener('click', function() {
            const eventTitle = this.querySelector('.event-title').textContent;
            console.log(`Evento selecionado: ${eventTitle}`);
        });
    });

    // Simulação de conteúdo dinâmico - poderia ser substituído por uma API real
    // This function was originally inside DOMContentLoaded but then moved outside
    // Keeping it accessible but not calling it automatically here as it might be called elsewhere
    // function loadEventData() { console.log('Carregando dados de eventos...'); }

    // --- Calendar Functionality ---
    const dateInput = document.getElementById('event-date');
    const calendarIcon = document.getElementById('calendar-icon');
    const calendarPopup = document.getElementById('calendar-popup');
    const monthYearDisplay = document.getElementById('month-year');
    const daysGridContainer = document.getElementById('days-grid');
    // prevMonthBtn and nextMonthBtn are queried inside calendarPopup.
    // If calendarPopup is null, these will fail. Check for null before using.
    let prevMonthBtn = null;
    let nextMonthBtn = null;
    if (calendarPopup) {
        prevMonthBtn = calendarPopup.querySelector('.nav-btn:first-child');
        nextMonthBtn = calendarPopup.querySelector('.nav-btn:last-child');
    }


    let currentCalendarDate = new Date();

    const formatSelectedDateForInput = (date) => {
        const options = { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' };
        const formattedDate = new Intl.DateTimeFormat('pt-BR', options).format(date);
        return formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1).replace(/De\s/g, 'de ');
    };

    const parseInputDate = (dateString) => {
        try {
            const match = dateString.match(/(\w+),\s(\d+)\sde\s(\w+)\sde\s(\d{4})/i);
            if (match) {
                const day = parseInt(match[2]);
                const monthName = match[3].toLowerCase();
                const year = parseInt(match[4]);
                const monthsMap = {
                    'janeiro': 0, 'fevereiro': 1, 'março': 2, 'abril': 3, 'maio': 4, 'junho': 5,
                    'julho': 6, 'agosto': 7, 'setembro': 8, 'outubro': 9, 'novembro': 10, 'dezembro': 11
                };
                const monthIndex = monthsMap[monthName];
                if (monthIndex !== undefined) {
                    return new Date(year, monthIndex, day);
                }
            }
        } catch (e) {
            console.error("Error parsing input date string:", dateString, e);
        }
        return null;
    };

    function renderCalendar() {
        if (!daysGridContainer || !monthYearDisplay) { // Check if elements exist
            console.error("Calendar elements not found for rendering.");
            return;
        }

        daysGridContainer.innerHTML = '';

        const year = currentCalendarDate.getFullYear();
        const month = currentCalendarDate.getMonth();

        const monthNameHeader = new Intl.DateTimeFormat('pt-BR', { month: 'long' }).format(currentCalendarDate);
        monthYearDisplay.textContent = `${monthNameHeader.charAt(0).toUpperCase() + monthNameHeader.slice(1)} ${year}`;

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        let selectedDateFromInput = parseInputDate(dateInput.value);
        if (selectedDateFromInput) {
            selectedDateFromInput.setHours(0, 0, 0, 0);
        }

        for (let i = 0; i < firstDayOfMonth; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('day', 'other-month');
            dayDiv.textContent = daysInPrevMonth - firstDayOfMonth + i + 1;
            daysGridContainer.appendChild(dayDiv);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('day');
            dayDiv.textContent = i;

            const currentDayDate = new Date(year, month, i);
            currentDayDate.setHours(0, 0, 0, 0);

            if (currentDayDate.getTime() === today.getTime()) {
                dayDiv.classList.add('today');
            }

            if (selectedDateFromInput && currentDayDate.getTime() === selectedDateFromInput.getTime()) {
                dayDiv.classList.add('selected');
            }

            dayDiv.addEventListener('click', () => {
                const selectedDay = new Date(year, month, i);
                dateInput.value = formatSelectedDateForInput(selectedDay);
                if (calendarPopup) calendarPopup.classList.remove('show');

                document.querySelectorAll('.days-grid .day').forEach(d => d.classList.remove('selected'));
                dayDiv.classList.add('selected');
            });
            daysGridContainer.appendChild(dayDiv);
        }

        const totalCellsRendered = daysGridContainer.children.length;
        const cellsToFill = (7 - (totalCellsRendered % 7)) % 7;
        const remainingForSixRows = (42 - totalCellsRendered);
        const finalCellsToAdd = Math.max(cellsToFill, remainingForSixRows);

        for (let i = 1; i <= finalCellsToAdd; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('day', 'other-month');
            dayDiv.textContent = i;
            daysGridContainer.appendChild(dayDiv);
        }
    }

    // --- Global functions for HTML onclick attributes ---
    // These need to be attached to `window` if directly called from HTML `onclick`
    window.toggleCalendar = function() {
        if (!calendarPopup) {
            console.error("Calendar popup element not found!");
            return;
        }
        calendarPopup.classList.toggle('show');
        if (calendarPopup.classList.contains('show')) {
            const parsedDate = parseInputDate(dateInput.value);
            if (parsedDate) {
                currentCalendarDate = parsedDate;
            } else {
                currentCalendarDate = new Date();
            }
            renderCalendar();
        }
    };

    window.changeMonth = function(direction) {
        currentCalendarDate.setMonth(currentCalendarDate.getMonth() + direction);
        renderCalendar();
    };

    // --- Calendar Event Listeners ---
    if (dateInput) {
        dateInput.addEventListener('click', (event) => {
            event.stopPropagation();
            window.toggleCalendar();
        });
    }
    if (calendarIcon) {
        calendarIcon.addEventListener('click', (event) => {
            event.stopPropagation();
            window.toggleCalendar();
        });
    }

    document.addEventListener('click', (event) => {
        const target = event.target;
        if (calendarPopup && dateInput && calendarIcon &&
            !calendarPopup.contains(target) && target !== dateInput && target !== calendarIcon) {
            calendarPopup.classList.remove('show');
        }
    });

    // --- Initial Setup for Calendar on Page Load ---
    if (dateInput && !dateInput.value) {
        const today = new Date();
        dateInput.placeholder = formatSelectedDateForInput(today);
    }

    // --- Other Filter Dropdown Listeners ---
    document.querySelectorAll('.filter-select').forEach(select => {
        select.addEventListener('change', (event) => {
            console.log(`Filter changed: ${event.target.id} - Value: ${event.target.value}`);
        });
    });
}); // End of the single DOMContentLoaded block
