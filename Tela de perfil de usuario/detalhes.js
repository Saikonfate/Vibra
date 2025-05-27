document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const sidebarToggleBtn = document.querySelector(".user-btn");

    window.toggleSidebar = function() {
        if (sidebar) {
            const isVisible = sidebar.classList.toggle("visible");
            sidebarToggleBtn.setAttribute('aria-expanded', isVisible);
            if (overlay) {
                overlay.style.display = isVisible ? "block" : "none";
            }
        }
    };

    if (sidebarToggleBtn) {
        // O evento onclick já está no HTML, mas se quisesse adicionar aqui:
        // sidebarToggleBtn.addEventListener('click', window.toggleSidebar);
    }

    function closeSidebar() {
        if (sidebar && sidebar.classList.contains("visible")) {
            sidebar.classList.remove("visible");
            sidebarToggleBtn.setAttribute('aria-expanded', 'false');
            if (overlay) {
                overlay.style.display = "none";
            }
        }
    }

    if (overlay) {
        overlay.addEventListener("click", closeSidebar);
    }

    window.addEventListener("click", function (event) {
        if (sidebar && sidebar.classList.contains("visible") &&
            !sidebar.contains(event.target) &&
            !sidebarToggleBtn.contains(event.target)) {
            closeSidebar();
        }
    });
}); // Removida a chave } extra daqui
