<x-guest-layout :centered="true">
    <h1>¿Olvidaste tu contraseña?</h1>
    <p class="login-subtitle">Te enviaremos un enlace para restablecerla</p>
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

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   placeholder="tucorreo@ejemplo.com" required autofocus>
        </div>

        <button type="submit" class="btn-login">Enviar enlace de recuperación</button>
    </form>

    <p class="register-link">
        <a href="{{ route('login') }}">← Volver a iniciar sesión</a>
    </p>
</x-guest-layout>