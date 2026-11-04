@props(['options', 'sort' => null, 'dir' => 'desc'])

@php
    $current = ($sort ?? array_key_first($options)) . '|' . $dir;
@endphp

<div class="usr-sort-select">
    <i class="bi bi-sort-down-alt"></i>
    <select aria-label="Ordenar por" onchange="
        const [f, d] = this.value.split('|');
        const form = this.closest('form');
        form.querySelector('[data-sort-field]').value = f;
        form.querySelector('[data-sort-dir]').value = d;
        form.submit();
    ">
        @foreach($options as $value => $label)
            <option value="{{ $value }}" @selected($value === $current)>{{ $label }}</option>
        @endforeach
    </select>
    <input type="hidden" name="sort" value="{{ $sort }}" data-sort-field>
    <input type="hidden" name="dir" value="{{ $dir }}" data-sort-dir>
</div>
