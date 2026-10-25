@props(['product'])

{{--
    The information tabs of a product page: Utilisation, Ingrédients (full list and key ingredients), Précautions d'emploi,
    Conditions de conservation. Only the tabs that have text are shown; none, no section.
    Tabs follow the ARIA pattern (see setupTabs() in resources/js/storefront.js). Without JavaScript every panel is shown
    one after the other, each with its own heading (CSS: html:not(.js) in storefront.css).
--}}
@php
    $text = fn (?string $value) => filled(trim((string) $value)) ? trim((string) $value) : null;

    // "Aloe vera : Hydratant / Nourrissant" => name and its benefits; a line without ":" is a name only
    $keyIngredients = collect(preg_split('/\r\n|\r|\n/', (string) $product->tr('key_ingredients')))
        ->map(fn ($line) => trim($line))->filter()
        ->map(function ($line) {
            [$name, $benefits] = array_pad(explode(':', $line, 2), 2, '');

            return [
                'name' => trim($name),
                'benefits' => collect(explode('/', $benefits))->map(fn ($b) => trim($b))->filter()->values()->all(),
            ];
        })->filter(fn ($item) => $item['name'] !== '')->values();

    $list = $text($product->tr('ingredients'));

    $tabs = collect([
        ['usage', __('Utilisation'), $text($product->tr('how_to_use'))],
        ['ingredients', __('Ingrédients'), ($list !== null || $keyIngredients->isNotEmpty()) ? true : null],
        ['precautions', __('Précautions d\'emploi'), $text($product->tr('precautions'))],
        ['storage', __('Conditions de conservation'), $text($product->tr('storage_conditions'))],
    ])->filter(fn ($tab) => $tab[2] !== null)->values();
@endphp

@if ($tabs->isNotEmpty())
    <section class="mt-12" aria-labelledby="info-title" data-tabs>
        <h2 id="info-title" class="sr-only">{{ __('Informations sur le produit') }}</h2>

        <div role="tablist" aria-label="{{ __('Informations sur le produit') }}" class="flex overflow-x-auto border-b border-ink">
            @foreach ($tabs as [$key, $label])
                <button type="button" role="tab" id="info-tab-{{ $key }}" aria-controls="info-panel-{{ $key }}"
                        aria-selected="{{ $loop->first ? 'true' : 'false' }}" @if (! $loop->first) tabindex="-1" @endif
                        class="label-caps min-h-12 shrink-0 cursor-pointer border-b-4 border-transparent px-4 text-ink-muted hover:bg-mint aria-selected:border-green aria-selected:text-ink sm:px-6">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @foreach ($tabs as [$key, $label, $content])
            <div role="tabpanel" id="info-panel-{{ $key }}" aria-labelledby="info-tab-{{ $key }}" tabindex="0" @if (! $loop->first) data-inactive @endif class="py-8">
                <h3 class="tab-heading mb-4 text-2xl">{{ $label }}</h3>

                @if ($key === 'ingredients')
                    <div class="grid gap-8 md:grid-cols-2 md:gap-12">
                        @if ($list !== null)
                            <p class="text-sm uppercase leading-relaxed tracking-wide text-ink-muted">{{ $list }}</p>
                        @endif

                        @if ($keyIngredients->isNotEmpty())
                            <div>
                                <p class="label-caps text-ink-muted">{{ __('Ingrédients clés') }}</p>
                                <ul class="mt-2 divide-y divide-line border-y border-line">
                                    @foreach ($keyIngredients as $item)
                                        <li>
                                            <details class="group">
                                                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 py-2 font-semibold">
                                                    {{ $item['name'] }}
                                                    <x-ui.icon name="plus" class="size-5 shrink-0 transition-transform group-open:rotate-45" />
                                                </summary>
                                                @if ($item['benefits'])
                                                    <p class="pb-3 text-ink-muted">{{ implode(' · ', $item['benefits']) }}</p>
                                                @endif
                                            </details>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="max-w-prose whitespace-pre-line">{{ $content }}</p>
                @endif
            </div>
        @endforeach
    </section>
@endif
