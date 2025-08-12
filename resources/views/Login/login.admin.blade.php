{{-- <div class="login-container">
        <div class="login-header">
            <h1>Login System</h1>
            <p>Masuk ke akun Anda</p>
        </div>

        @if (session('message'))
            <div class="success-message">
                {{ session('message') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            
            <div class="form-group">
                <label for="identifier">Email atau NIK</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    value="{{ old('username') }}"
                    class="{{ $errors->has('username') ? 'error' : '' }}"
                    placeholder="Masukkan email atau NIK"
                    required
                    autocomplete="username"
                >
                @if ($errors->has('username'))
                    <div class="error-message">{{ $errors->first('identifier') }}</div>
                @endif
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password"
                    class="{{ $errors->has('password') ? 'error' : '' }}"
                    placeholder="Masukkan password"
                    required
                    autocomplete="current-password"
                >
                @if ($errors->has('password'))
                    <div class="error-message">{{ $errors->first('password') }}</div>
                @endif
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember" value="1">
                <label for="remember">Ingat saya</label>
            </div>

            <button type="submit" class="login-btn">
                Masuk
            </button>
        </form>

        <div class="login-info">
            <h4>Informasi Login:</h4>
            <ul>
                <li><strong>Admin/Owner:</strong> Gunakan email untuk login</li>
                <li><strong>Karyawan:</strong> Gunakan NIK untuk login</li>
                <li>Satu form untuk semua role</li>
            </ul>
        </div>
    </div> --}}