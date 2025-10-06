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

// page camera

// header camera
document.querySelector(".goBack").addEventListener("click", () => {
    // A common practice for a "back" button in web apps
    window.history.back();
});

// camera open
Webcam.set({
    height: 520,
    width: 720,
    image_format: "jpeg",
    jpeg_quality: 80,
});
Webcam.attach(".camera-capture");

// input lokasi
var location = document.getElementById("location");
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(successCallback, errorCallback);
}

function successCallback(position) {
    location.value = position.coords.latitude + "," + position.coords.longitude;
}

function errorCallback() {}
