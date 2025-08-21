am4core.ready(function () {
    // Themes begin
    am4core.useTheme(am4themes_animated);
    // Themes end

    var chart = am4core.create("chartdiv", am4charts.PieChart3D);
    chart.hiddenState.properties.opacity = 0; // this creates initial fade-in

    chart.legend = new am4charts.Legend();

    chart.data = [
        {
            status: "Hadir",
            value: 20,
        },
        {
            status: "Sakit",
            value: 2,
        },
        {
            status: "Izin",
            value: 1,
        },
        {
            status: "Terlambat",
            value: 3,
        },
    ];

    var series = chart.series.push(new am4charts.PieSeries3D());
    series.dataFields.value = "value";
    series.dataFields.category = "status";
    series.alignLabels = false;
    series.labels.template.text = "{value.percent.formatNumber('#.0')}%";
    series.labels.template.radius = am4core.percent(-40);
    series.labels.template.fill = am4core.color("white");
    series.colors.list = [
        am4core.color("#37db63"), // Hijau untuk Hadir
        am4core.color("#fca903"), // Kuning untuk Sakit
        am4core.color("#1171ba"), // Biru untuk Izin
        am4core.color("#ba113b"), // Merah untuk Terlambat
    ];
});

class WebcamApp {
    constructor() {
        this.video = document.getElementById("webcamVideo");
        this.canvas = document.getElementById("canvas");
        this.photos = document.getElementById("photos");
        this.toggleCamButton = document.getElementById("toggleCamButton");
        this.captureButton = document.getElementById("captureButton");

        this.stream = null; // Properti untuk menyimpan stream kamera

        // Binding 'this'
        this.toggleWebcam = this.toggleWebcam.bind(this);
        this.takePicture = this.takePicture.bind(this);

        this.addEventListeners();
    }

    addEventListeners() {
        this.toggleCamButton.addEventListener("click", this.toggleWebcam);
        this.captureButton.addEventListener("click", this.takePicture);
    }

    // Fungsi baru untuk menghidupkan/mematikan kamera
    toggleWebcam() {
        if (this.stream) {
            this.stopWebcam();
        } else {
            this.startWebcam();
        }
    }

    async startWebcam() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false,
            });
            this.stream = stream; // Simpan stream
            this.video.srcObject = stream;
            this.video.onloadedmetadata = () => {
                this.video.play();
                // Update UI
                this.captureButton.disabled = false;
                this.toggleCamButton.textContent = "Stop Kamera";
                this.toggleCamButton.classList.add("stop-button");
            };
        } catch (err) {
            console.error("Terjadi kesalahan saat mengakses kamera: ", err);
            alert(
                "Tidak dapat mengakses kamera. Pastikan Anda memberikan izin pada browser."
            );
        }
    }

    // Fungsi baru untuk menghentikan kamera
    stopWebcam() {
        if (!this.stream) return;

        // Hentikan setiap track video
        this.stream.getTracks().forEach((track) => track.stop());

        this.video.srcObject = null;
        this.stream = null;

        // Update UI
        this.captureButton.disabled = true;
        this.toggleCamButton.textContent = "Mulai Kamera";
        this.toggleCamButton.classList.remove("stop-button");
    }

    takePicture() {
        const context = this.canvas.getContext("2d");

        this.canvas.width = this.video.videoWidth;
        this.canvas.height = this.video.videoHeight;

        context.drawImage(
            this.video,
            0,
            0,
            this.video.videoWidth,
            this.video.videoHeight
        );

        const data = this.canvas.toDataURL("image/png");
        const img = document.createElement("img");
        img.setAttribute("src", data);

        this.photos.appendChild(img);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    new WebcamApp();
});
