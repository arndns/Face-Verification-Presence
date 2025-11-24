document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const sidebarCollapse = document.getElementById("sidebarCollapse");
    const sidebarClose = document.getElementById("sidebarClose");
    const backdrop = document.querySelector(".sidebar-backdrop");
    const sidebarStateKey = "adminSidebarCollapsed";

    if (sidebar && sidebarCollapse && sidebarClose && backdrop) {
        const savedState = localStorage.getItem(sidebarStateKey);
        const shouldCollapse = savedState === "true";
        sidebar.classList.toggle("collapsed", shouldCollapse);

        sidebarCollapse.addEventListener("click", function () {
            sidebar.classList.remove("collapsed");
            localStorage.setItem(sidebarStateKey, "false");
        });

        sidebarClose.addEventListener("click", function () {
            sidebar.classList.add("collapsed");
            localStorage.setItem(sidebarStateKey, "true");
        });

        backdrop.addEventListener("click", function () {
            sidebar.classList.add("collapsed");
            localStorage.setItem(sidebarStateKey, "true");
        });
    }
});

// modal peringatan
document.addEventListener("DOMContentLoaded", function () {
    var errorModalElement = document.getElementById("errorModal");
    var errorModal = new bootstrap.Modal(errorModalElement);
    errorModal.show();
});
