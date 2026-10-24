@php
    $payments = \App\Support\PaymentMethods::acceptedLabels();
    $days = trim((string) getSetting('estimated_delivery_days', ''));
@endphp

<x-layouts.storefront title="À propos" description="L'histoire de Glowing Cosmetics et ce que nous vous garantissons concrètement : livraison, paiement, retours.">
    <x-storefront.info-page title="À propos de nous">
        <section>
            <h2>Notre histoire</h2>
            <p>Bienvenue chez <strong>GLOWING</strong>, votre destination beauté de confiance. Fondée avec la passion de révéler la beauté naturelle de chacun, nous nous engageons à offrir des produits cosmétiques de haute qualité qui respectent votre peau et l'environnement.</p>
            <p>Nous avons pour mission de démocratiser l'accès à des produits de beauté premium. Chaque produit de notre collection est soigneusement sélectionné pour garantir efficacité, sécurité et plaisir d'utilisation.</p>
            <p>Nous croyons que la beauté commence par prendre soin de soi, et c'est pourquoi nous vous accompagnons dans votre routine avec des conseils et un service client à l'écoute.</p>
        </section>

        <section>
            <h2>Ce que nous vous promettons</h2>
            <ul>
                <li><strong>Livraison :</strong> {{ \App\Support\Shipping::promise() }}.@if ($days !== '') Délai estimé : {{ $days }} jours.@endif</li>
                @if ($payments)<li><strong>Paiement :</strong> {{ implode(', ', array_map('mb_strtolower', $payments)) }}.</li>@endif
                <li><strong>Commande sans compte :</strong> vous pouvez commander sans créer de compte.</li>
                <li><strong>Retours :</strong> le délai et les conditions sont décrits dans nos <a href="{{ route('terms') }}#retours">conditions générales</a>.</li>
            </ul>
        </section>

        <section>
            <h2>Une question ?</h2>
            <p>Consultez la <a href="{{ route('pages.faq') }}">foire aux questions</a> ou <a href="{{ route('pages.contact') }}">écrivez-nous</a>.</p>
            <p><x-ui.button :href="route('shop.index')">Découvrir la boutique</x-ui.button></p>
        </section>
    </x-storefront.info-page>
</x-layouts.storefront>
