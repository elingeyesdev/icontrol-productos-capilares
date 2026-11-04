@props(['field', 'sort' => null, 'dir' => 'asc', 'align' => 'left'])

@php
    $isActive = $sort === $field;
    $nextDir = $isActive && $dir === 'asc' ? 'desc' : 'asc';
    $icon = $isActive
        ? ($dir === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill')
        : 'bi-arrow-down-up';
@endphp

<th style="text-align:{{ $align }};">
    <a href="{{ request()->fullUrlWithQuery(['sort' => $field, 'dir' => $nextDir]) }}"
       class="th-sort-link {{ $isActive ? 'th-sort-link--active' : '' }}"
       style="justify-content:{{ $align === 'right' ? 'flex-end' : ($align === 'center' ? 'center' : 'flex-start') }};">
        <span>{{ $slot }}</span>
        <i class="bi {{ $icon }} th-sort-icon"></i>
    </a>
</th>
