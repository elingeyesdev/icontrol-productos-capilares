@props(['periodos', 'periodo'])

<div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px;">
    @foreach($periodos as $clave => $etiqueta)
        <a href="{{ request()->url() }}?periodo={{ $clave }}"
           style="padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:700; text-decoration:none;
                  {{ $periodo === $clave ? 'background:var(--clr-active); color:#fff;' : 'background:var(--clr-card); color:var(--clr-sub);' }}">
            {{ $etiqueta }}
        </a>
    @endforeach
</div>
