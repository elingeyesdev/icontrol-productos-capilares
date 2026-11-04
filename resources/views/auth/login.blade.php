<x-guest-layout :centered="true">
    <h1>Bienvenid@ de nuevo</h1>
    <p class="login-subtitle">Inicia sesión para continuar</p>
    <hr class="form-divider">

    @if (session('status'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   placeholder="tucorreo@ejemplo.com" required autofocus autocomplete="username">
        </div>

        <div class="form-group" x-data="{ show: false }">
            <label for="password">Contraseña</label>
            <div class="password-container">
                <input :type="show ? 'text' : 'password'" id="password" name="password"
                       placeholder="••••••••" required autocomplete="current-password">
                <button type="button" class="password-toggle" @click="show = !show">
                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
        </div>

        <div class="form-group" style="display:flex; align-items:center; justify-content:space-between;">
            <label style="display:flex; align-items:center; gap:6px; margin-bottom:0; font-weight:400;">
                <input type="checkbox" name="remember" style="width:auto;">
                Recordarme
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" style="font-size:12px; color:#e8c6e8;">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <button type="submit" class="btn-login">Iniciar sesión</button>
    </form>

    <p class="register-link">
        ¿No tienes cuenta? <a href="{{ route('register') }}">Regístrate aquí</a>
    </p>
</x-guest-layout>