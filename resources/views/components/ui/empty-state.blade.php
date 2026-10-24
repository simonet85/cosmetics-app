@props(['title', 'text' => null])

{{-- Nothing to show: say why and give the next step (put the action in the slot). --}}
<div {{ $attributes->merge(['class' => 'border border-line bg-mint/40 px-6 py-12 text-center']) }}>
    <h2 class="text-3xl">{{ $title }}</h2>
    @if ($text)
        <p class="mx-auto mt-2 max-w-md text-ink-muted">{{ $text }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-6 flex justify-center">{{ $slot }}</div>
    @endif
</div>
