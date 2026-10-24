@props(['title', 'lead' => null, 'level' => 'h2'])

{{-- A section title that carries its own weight: no label above it, no numbering. --}}
<div {{ $attributes->merge(['class' => 'max-w-2xl']) }}>
    <{{ $level }} class="text-4xl sm:text-5xl">{{ $title }}</{{ $level }}>
    @if ($lead)
        <p class="mt-3 text-lg text-ink-muted">{{ $lead }}</p>
    @endif
</div>
