@props(['centered' => true])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Luciane') }}</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet"/>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-body">

    <div class="login-container">

        <div class="carousel-container">
            <div class="carousel-brand">
                <img src="{{ asset('storage/branding/logo.png') }}" alt="Luciane">
                <span class="carousel-brand-name">Luciane </span>
            </div>

            @php
                $slides = ['slide1.jpeg', 'slide2.jpeg', 'slide3.jpeg', 'slide4.jpeg', 'slide5.jpeg'];
            @endphp
            @foreach($slides as $slide)
                <div class="carousel-frame">
                    <img src="{{ asset('storage/branding/carrusel/' . $slide) }}" alt="">
                </div>
            @endforeach
        </div>

        <div class="login-card {{ $centered ? 'login-card-center' : '' }}">
            {{ $slot }}
        </div>
    </div>

</body>
</html>