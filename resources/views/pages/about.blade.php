@php
    $payments = \App\Support\PaymentMethods::acceptedLabels();
    $days = trim((string) getSetting('estimated_delivery_days', ''));
@endphp

<x-layouts.storefront :title="__('À propos')" :description="__('L\'histoire de Glowing Cosmetics et ce que nous vous garantissons concrètement : livraison, paiement, retours.')">
    <x-storefront.info-page :title="__('À propos de nous')">
        <section>
            <h2>{{ __('Notre histoire') }}</h2>
            <p>{!! __('Bienvenue chez <strong>GLOWING</strong>, votre destination beauté de confiance. Fondée avec la passion de révéler la beauté naturelle de chacun, nous nous engageons à offrir des produits cosmétiques de haute qualité qui respectent votre peau et l\'environnement.') !!}</p>
            <p>{{ __('Nous avons pour mission de démocratiser l\'accès à des produits de beauté premium. Chaque produit de notre collection est soigneusement sélectionné pour garantir efficacité, sécurité et plaisir d\'utilisation.') }}</p>
            <p>{{ __('Nous croyons que la beauté commence par prendre soin de soi, et c\'est pourquoi nous vous accompagnons dans votre routine avec des conseils et un service client à l\'écoute.') }}</p>
        </section>

        <section>
            <h2>{{ __('Ce que nous vous promettons') }}</h2>
            <ul>
                <li><strong>{{ __('Livraison :') }}</strong> {{ \App\Support\Shipping::promise() }}.@if ($days !== '') {{ __('Délai estimé : :days jours.', ['days' => $days]) }}@endif</li>
                @if ($payments)<li><strong>{{ __('Paiement :') }}</strong> {{ implode(', ', array_map('mb_strtolower', $payments)) }}.</li>@endif
                <li><strong>{{ __('Commande sans compte :') }}</strong> {{ __('vous pouvez commander sans créer de compte.') }}</li>
                <li><strong>{{ __('Retours :') }}</strong> {!! __('le délai et les conditions sont décrits dans nos <a href=":url">conditions générales</a>.', ['url' => route('terms').'#retours']) !!}</li>
            </ul>
        </section>

        <section>
            <h2>{{ __('Une question ?') }}</h2>
            <p>{!! __('Consultez la <a href=":faq">foire aux questions</a> ou <a href=":contact">écrivez-nous</a>.', ['faq' => route('pages.faq'), 'contact' => route('pages.contact')]) !!}</p>
            <p><x-ui.button :href="route('shop.index')">{{ __('Découvrir la boutique') }}</x-ui.button></p>
        </section>
    </x-storefront.info-page>
</x-layouts.storefront>
