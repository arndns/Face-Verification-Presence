// Loader halaman pegawai dengan fallback jika event load tidak terpanggil cepat.
function hideLoader() {
    const loader = document.getElementById("loader");
    if (!loader) return;
    loader.style.opacity = "0";
    setTimeout(() => {
        loader.style.display = "none";
    }, 400);
}

document.addEventListener("DOMContentLoaded", () => {
    // Pastikan loader hilang setelah DOM siap.
    hideLoader();
    // Pasang fallback 3 detik jika ada resource berat.
    setTimeout(hideLoader, 3000);
});

window.addEventListener("load", hideLoader);




