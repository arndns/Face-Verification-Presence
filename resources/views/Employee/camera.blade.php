@extends('layout.employee')
@section('title', 'Camera')
@section('content')
    <div class="container-webcam">
        <h1 class="title-photo">Kamera Cermin</h1>
        <div class="webcam-content">
            <video id="webcamVideo" autoplay playsinline></video>
        </div>
        <div class="button-webcam">
            <button id="toggleCamButton">Mulai Kamera</button>
            <button id="captureButton" disabled>Ambil Gambar</button>
        </div>

        <div id="photo-webcam">
            <canvas id="canvas-webcam"></canvas>
            <h2 class="take-photo">Hasil Gambar</h2>
            <div id="photos-capture"></div>
        </div>
    </div>

@endsection
