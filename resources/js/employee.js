// loader halaman pegawai
window.addEventListener("load", function () {
    const loader = document.getElementById("loader");
    if (!loader) {
        return;
    }
    loader.style.opacity = "0";
    setTimeout(() => {
        loader.style.display = "none";
    }, 500);
});
// selesai





