@extends('layout.employee')
@section('title', 'Dashboard')
@section('content')
    <div class="page active" id="home-page">
        <div class="user-section">
            <div class="user-detail">
                <div class="user-identity">
                    <div class="avatar">
                        <img src="{{ asset('assets/image/profil-picture.png') }}" alt="avatar"
                            class="imaged w64 rounded-circle">
                    </div>
                    <div class="user-info">
                        <h2 id="user-name">{{ $user->nama }}</h2>
                        <span id="user-role">{{ $user->jabatan }}</span>
                    </div>
                </div>
                <div class="logout-button">
                    <form action="{{ route('logout') }}" method="POST" id="logout-form">
                        @csrf
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i
                                class="fa-solid fa-sign-out-alt"></i></a>
                    </form>
                </div>
            </div>
        </div>

        <div class="menu-section-container">
            <div class="card shadow-sm rounded-3">
                <div class="card-body text-center py-4">
                    <div class="list-menu">
                        <div class="item-menu">
                            <a href="#" class="text-green"><i class="fa-solid fa-user"></i></a>
                            <div class="menu-name"><span>Profil</span></div>
                        </div>
                        <div class="item-menu">
                            <a href="#" class="text-danger"><i class="fa-solid fa-calendar-alt"></i></a>
                            <div class="menu-name"><span>Cuti</span></div>
                        </div>
                        <div class="item-menu">
                            <a href="#" class="text-warning"><i class="fa-solid fa-history"></i></a>
                            <div class="menu-name"><span>Histori</span></div>
                        </div>
                        <div class="item-menu">
                            <a href="#" class="text-orange"><i class="fa-solid fa-map-marker-alt"></i></a>
                            <div class="menu-name">Lokasi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="presence-section-container">
            <div class="today-presence">
                <div class="row g-2">
                    <div class="col">
                        <div class="card bg-green-presence">
                            <div class="card-body">
                                <div class="presence-content">
                                    <div class="icon-presence"><i class="fa-solid fa-camera"></i></div>
                                    <div class="presence-detail">
                                        <h4 class="presence-title">Masuk</h4><span>07:00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card bg-red-presence">
                            <div class="card-body">
                                <div class="presence-content">
                                    <div class="icon-presence"><i class="fa-solid fa-camera"></i></div>
                                    <div class="presence-detail">
                                        <h4 class="presence-title">Pulang</h4><span>12:00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
