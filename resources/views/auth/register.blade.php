<x-guest-layout :centered="false">
    <h1>Crea tu cuenta</h1>
    <p class="login-subtitle">Únete a Luciane</p>
    <hr class="form-divider">

    @if ($errors->any())
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="form-group">
            <label for="name">Nombre completo</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}"
                   placeholder="Tu nombre" required autofocus autocomplete="name">
        </div>

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   placeholder="tucorreo@ejemplo.com" required autocomplete="username">
        </div>

        <div class="form-group" x-data="{ show: false }">
            <label for="password">Contraseña</label>
            <div class="password-container">
                <input :type="show ? 'text' : 'password'" id="password" name="password"
                       placeholder="••••••••" required autocomplete="new-password">
                <button type="button" class="password-toggle" @click="show = !show">
                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
            <small>Mínimo 8 caracteres</small>
        </div>

        <div class="form-group" x-data="{ show: false }">
            <label for="password_confirmation">Confirmar contraseña</label>
            <div class="password-container">
                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation"
                       placeholder="••••••••" required autocomplete="new-password">
                <button type="button" class="password-toggle" @click="show = !show">
                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">Crear cuenta</button>
    </form>

    <p class="register-link">
        ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
    </p>
</x-guest-layout>