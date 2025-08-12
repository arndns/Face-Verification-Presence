document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const sidebarCollapse = document.getElementById("sidebarCollapse");
    const sidebarClose = document.getElementById("sidebarClose");
    const backdrop = document.querySelector(".sidebar-backdrop");

    if (sidebar && sidebarCollapse && sidebarClose && backdrop) {
        // Selalu tutup sidebar saat halaman dimuat
        sidebar.classList.add("collapsed");

        // Logika untuk tombol BUKA
        sidebarCollapse.addEventListener("click", function () {
            sidebar.classList.remove("collapsed");
        });

        // Logika untuk tombol TUTUP
        sidebarClose.addEventListener("click", function () {
            sidebar.classList.add("collapsed");
        });

        // Logika untuk backdrop
        backdrop.addEventListener("click", function () {
            sidebar.classList.add("collapsed");
        });
    }
});


