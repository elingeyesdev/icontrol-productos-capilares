<x-guest-layout :centered="true">
    <h1>Restablecer contraseña</h1>
    <p class="login-subtitle">Elige tu nueva contraseña</p>
    <hr class="form-divider">

    @if ($errors->any())
        <div class="alert alert-error">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="form-group">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   placeholder="tucorreo@ejemplo.com" required autofocus autocomplete="username">
        </div>

        <div class="form-group" x-data="{ show: false }">
            <label for="password">Nueva contraseña</label>
            <div class="password-container">
                <input :type="show ? 'text' : 'password'" id="password" name="password"
                       placeholder="••••••••" required autocomplete="new-password">
                <button type="button" class="password-toggle" @click="show = !show">
                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
        </div>

        <div class="form-group" x-data="{ show: false }">
            <label for="password_confirmation">Confirmar nueva contraseña</label>
            <div class="password-container">
                <input :type="show ? 'text' : 'password'" id="password_confirmation" name="password_confirmation"
                       placeholder="••••••••" required autocomplete="new-password">
                <button type="button" class="password-toggle" @click="show = !show">
                    <i class="bi" :class="show ? 'bi-eye-slash' : 'bi-eye'"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">Restablecer contraseña</button>
    </form>
</x-guest-layout>