@props(['label' => 'À la une', 'count' => 1])

{{--
    A sequence of slides (1 -> 2 -> 3) built on native horizontal scrolling with snap points:
    swipe, trackpad and keyboard scrolling work by themselves, the script only adds arrows, dots and a pausable
    auto-advance. Without JavaScript the slides are simply stacked. The slides come in the slot, one
    <div class="slider-slide" data-slide> each. See setupSlider() in resources/js/storefront.js.
--}}
<section {{ $attributes->merge(['class' => 'relative']) }} data-slider aria-roledescription="carousel" aria-label="{{ $label }}">
    <div class="slider-track" data-slider-track>
        {{ $slot }}
    </div>

    @if ($count > 1)
        <div class="slider-controls mt-3 items-center justify-between gap-3" data-slider-controls>
            <div class="flex items-center">
                <button type="button" data-slider-prev class="inline-flex size-11 cursor-pointer items-center justify-center border border-ink hover:bg-mint">
                    <x-ui.icon name="arrow-right" class="size-5 rotate-180" />
                    <span class="sr-only">Diapositive précédente</span>
                </button>
                <button type="button" data-slider-next class="-ml-px inline-flex size-11 cursor-pointer items-center justify-center border border-ink hover:bg-mint">
                    <x-ui.icon name="arrow-right" class="size-5" />
                    <span class="sr-only">Diapositive suivante</span>
                </button>
            </div>

            <ol class="flex items-center" aria-label="Choisir une diapositive">
                @for ($i = 1; $i <= $count; $i++)
                    <li>
                        <button type="button" data-slider-dot="{{ $i - 1 }}" aria-label="Diapositive {{ $i }} sur {{ $count }}"
                                class="group inline-flex size-11 cursor-pointer items-center justify-center">
                            <span class="block h-2.5 w-8 border border-ink bg-white group-aria-[current=true]:bg-ink" aria-hidden="true"></span>
                        </button>
                    </li>
                @endfor
            </ol>

            <button type="button" data-slider-toggle hidden aria-pressed="false"
                    class="inline-flex min-h-11 cursor-pointer items-center gap-2 border border-ink px-3 text-sm font-semibold hover:bg-mint">
                <span data-slider-toggle-label>Pause</span>
            </button>
        </div>
    @endif

    <p class="sr-only" role="status" aria-live="polite" data-slider-status></p>
</section>
