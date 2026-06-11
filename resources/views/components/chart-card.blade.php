@props(['id', 'title', 'type' => 'line', 'height' => 280])

<div class="glass-card p-5">
    <h3 class="mb-4 text-sm font-semibold ayp-heading">{{ $title }}</h3>
    <div id="{{ $id }}" style="min-height: {{ $height }}px"></div>
</div>
