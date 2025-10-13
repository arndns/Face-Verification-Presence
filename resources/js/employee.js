// modal peringatan
document.addEventListener("DOMContentLoaded", function () {
    var errorModalElement = document.getElementById("errorModal");
    var errorModal = new bootstrap.Modal(errorModalElement);
    errorModal.show();
});

// loader halaman pegawai
window.addEventListener("load", function () {
    const loader = document.getElementById("loader");
    loader.style.opacity = "0";
    setTimeout(() => {
        loader.style.display = "none";
    }, 500);
});
// selesai


// header camera
document.querySelector(".goBack").addEventListener("click", () => {
    // A common practice for a "back" button in web apps
    window.history.back();
});



