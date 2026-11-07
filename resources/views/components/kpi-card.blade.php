@props(['icono', 'valor', 'etiqueta', 'tono' => 'purple', 'nota' => null, 'notaColor' => 'var(--clr-sub)'])

@php
    $fondo = [
        'mint' => 'bg-pastel-mint',
        'peach' => 'bg-pastel-peach',
        'purple' => 'bg-pastel-purple',
        'yellow' => 'bg-pastel-yellow',
    ][$tono] ?? 'bg-pastel-purple';
@endphp

<div class="bg-brand-white rounded-2xl shadow-card p-5">
    <div class="w-10 h-10 rounded-xl {{ $fondo }} flex items-center justify-center text-lg mb-3">
        <i class="bi {{ $icono }}"></i>
    </div>
    <div class="text-2xl font-extrabold text-brand-text">{{ $valor }}</div>
    <div class="text-[12.5px] font-bold text-brand-sub">{{ $etiqueta }}</div>
    @if($nota)
        <div style="font-size:11.5px; font-weight:700; margin-top:4px; color:{{ $notaColor }};">{{ $nota }}</div>
    @endif
</div>
