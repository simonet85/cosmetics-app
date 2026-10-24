{{-- Data (navCategories, paymentLabels) comes from StorefrontComposer. No dead links: every link points to a real page. --}}
<footer class="on-dark mt-24 bg-ink text-white">
    <div class="wrap grid gap-10 py-14 md:grid-cols-2 lg:grid-cols-4">
        <div class="grid content-start gap-4">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center">
                <img src="{{ asset('images/others/logo-white-02.png') }}" alt="{{ config('app.name', 'Glowing Cosmetics') }}" width="105" height="28" class="h-7 w-auto">
            </a>
            <p class="max-w-xs text-white/80">{{ __('Boutique en ligne de cosmétiques. Commandez depuis votre téléphone, sans compte obligatoire.') }}</p>
        </div>

        <nav aria-label="{{ __('Boutique') }}">
            <h2 class="label-caps text-base text-white">{{ __('Boutique') }}</h2>
            <ul class="mt-3 grid gap-1">
                <li><a href="{{ route('shop.index') }}" class="inline-flex min-h-11 items-center text-white/80 hover:text-white">{{ __('Toute la boutique') }}</a></li>
                @foreach ($navCategories as $category)
                    <li><a href="{{ route('shop.category', $category->slug) }}" class="inline-flex min-h-11 items-center text-white/80 hover:text-white">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </nav>

        <nav aria-label="{{ __('Aide et informations') }}">
            <h2 class="label-caps text-base text-white">{{ __('Aide') }}</h2>
            <ul class="mt-3 grid gap-1">
                <li><a href="{{ route('account.orders') }}" class="inline-flex min-h-11 items-center text-white/80 hover:text-white">{{ __('Suivre ma commande') }}</a></li>
                <li><a href="{{ route('pages.faq') }}" class="inline-flex min-h-11 items-center text-white/80 hover:text-white">{{ __('Questions fréquentes') }}</a></li>
                <li><a href="{{ route('pages.contact') }}" class="inline-flex min-h-11 items-center text-white/80 hover:text-white">{{ __('Nous contacter') }}</a></li>
                <li><a href="{{ route('pages.about') }}" class="inline-flex min-h-11 items-center text-white/80 hover:text-white">{{ __('À propos') }}</a></li>
            </ul>
        </nav>

        <div>
            <h2 class="label-caps text-base text-white">{{ __('Nouveautés par e-mail') }}</h2>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="mt-3 grid gap-3">
                @csrf
                <div class="grid gap-1.5">
                    <label for="newsletter-email" class="text-sm font-semibold">{{ __('Votre adresse e-mail') }}</label>
                    <input id="newsletter-email" name="email" type="email" required autocomplete="email"
                           class="min-h-12 w-full rounded-sm border border-white/60 bg-ink px-3 text-base text-white placeholder:text-white/60">
                </div>
                <x-ui.button type="submit" variant="inverse">{{ __('S\'inscrire') }}</x-ui.button>
            </form>
        </div>
    </div>

    <div class="border-t border-white/20">
        <div class="wrap flex flex-col gap-3 py-5 text-sm text-white/80 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} {{ config('app.name', 'Glowing Cosmetics') }}</p>
            @if ($paymentLabels)
                <p>{{ __('Paiement accepté : :paymentlabels', ['paymentlabels' => implode(', ', array_map('mb_strtolower', $paymentLabels))]) }}</p>
            @endif
            <ul class="flex gap-4">
                <li><a href="{{ route('terms') }}" class="inline-flex min-h-11 items-center underline hover:text-white">{{ __('Conditions générales') }}</a></li>
                <li><a href="{{ route('privacy') }}" class="inline-flex min-h-11 items-center underline hover:text-white">{{ __('Confidentialité') }}</a></li>
            </ul>
            <x-storefront.language-switcher :dark="true" />
        </div>
    </div>
</footer>
