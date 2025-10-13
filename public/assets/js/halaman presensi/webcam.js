

async function loadFaceAPI() {
    if (window.faceapi) return;
    await new Promise((resolve, reject) => {
        const s = document.createElement("script");
        s.src =
            "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js";
        s.onload = resolve;
        s.onerror = reject;
        document.head.appendChild(s);
    });
    console.log("face-api.js berhasil dimuat dari CDN");
}

async function loadFaceApiModels() {
    try {
        await Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri("/models"),
            faceapi.nets.faceLandmark68Net.loadFromUri("/models"),
            faceapi.nets.faceRecognitionNet.loadFromUri("/models"),
        ]);
        console.log("Face API models loaded successfully");
        return true;
    } catch (error) {
        console.error("Error loading face-api models:", error);
        return false;
    }
}
