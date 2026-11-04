@props(['titulo', 'subtitulo' => null])

<div class="reporte-banner">
    <div class="reporte-banner-logo">
        <x-application-logo />
    </div>
    <div>
        <h1>{{ config('app.name') }} — {{ $titulo }}</h1>
        @if($subtitulo)
            <p>{{ $subtitulo }}</p>
        @endif
    </div>
</div>
