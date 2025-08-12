//sembunyikan dan buka password
document.addEventListener('DOMContentLoaded', function () {
  // Semua variabel dan logika sekarang ada di dalam satu lingkup (scope) khusus.
  const togglePasswordButton = document.querySelector("#togglePassword");
  const passwordInput = document.querySelector("#password");

  // Pemeriksaan untuk memastikan elemen ada sebelum menambahkan event listener
  if (togglePasswordButton && passwordInput) {
    const eyeIcon = togglePasswordButton.querySelector("i");

    togglePasswordButton.addEventListener("click", function () {
      // Cek apakah tipe input saat ini adalah 'password'
      const isPassword = passwordInput.type === "password";
      
      // Ganti tipe input menggunakan ternary operator
      passwordInput.type = isPassword ? "text" : "password";

      // Pastikan ikon ada sebelum mengubah kelasnya
      if (eyeIcon) {
        // Ganti ikon mata
        eyeIcon.classList.toggle("fa-eye");
        eyeIcon.classList.toggle("fa-eye-slash");
      }
    });
  }
});


