@php
    $payments = \App\Support\PaymentMethods::acceptedLabels();
    $days = trim((string) getSetting('estimated_delivery_days', ''));
    $shippingCost = (float) getSetting('standard_shipping_cost', '0');
    $threshold = \App\Support\Shipping::freeThreshold();
    $shipMail = getSetting('send_shipping_notification', '1') === '1';

    // Every answer states what the shop actually does: settings and real behaviour, no template text.
    // Answers are HTML (links, emphasis); the values put inside them are escaped here.
    $groups = [
        __('Commande et livraison') => [
            [__('Comment suivre ma commande ?'), __('Connectez-vous et ouvrez <strong>Mon compte &gt; Mes commandes</strong> : vous y voyez l\'état de la commande et, une fois le colis expédié, son numéro de suivi.').($shipMail ? ' '.__('Un e-mail vous prévient aussi quand l\'état change.') : '').' '.__('Sans compte, gardez la page de confirmation affichée après la commande : son adresse permet de revoir la commande.')],
            [__('Quels sont les frais et les délais de livraison ?'), ($shippingCost > 0
                ? __('La livraison coûte :cost', ['cost' => format_fcfa($shippingCost)]).($threshold !== null ? ' '.__('et elle est gratuite dès :amount d\'achats.', ['amount' => format_fcfa($threshold)]) : '.')
                : __('La livraison est gratuite.')).($days !== '' ? ' '.__('Le délai estimé est de :days jours.', ['days' => e($days)]) : '')],
            [__('Puis-je annuler ma commande ?'), __('Oui, tant qu\'elle est « En attente » : ouvrez la commande dans <strong>Mon compte &gt; Mes commandes</strong> et choisissez « Annuler cette commande ». Les articles retournent en stock. Ensuite, <a href=":contact">contactez-nous</a>.', ['contact' => route('pages.contact')])],
        ],
        __('Paiement') => [
            [__('Quels modes de paiement acceptez-vous ?'), $payments ? __('Vous pouvez régler par : :methods.', ['methods' => e(implode(', ', array_map('mb_strtolower', $payments)))]) : __('Les modes de paiement disponibles sont affichés à l\'étape de paiement.')],
            [__('Mon paiement mobile est « en attente », que faire ?'), __('La page de suivi se met à jour toute seule dès que MoneyFusion confirme le paiement : vous n\'avez rien à faire. Si le paiement n\'a pas abouti, vous pouvez le relancer depuis la commande dans Mon compte.')],
            [__('Mes données de paiement sont-elles conservées ?'), __('Non. Le paiement mobile est traité par MoneyFusion et nous ne stockons aucune donnée bancaire. Voir notre <a href=":privacy">politique de confidentialité</a>.', ['privacy' => route('privacy')])],
        ],
        __('Compte') => [
            [__('Ai-je besoin d\'un compte pour commander ?'), __('Non, vous pouvez commander sans compte. Un compte permet de retrouver vos commandes et votre panier sur tous vos appareils.')],
            [__('J\'ai oublié mon mot de passe'), __('Utilisez le lien <a href=":reset">Mot de passe oublié</a> : nous vous envoyons un lien pour en choisir un nouveau.', ['reset' => route('password.request')])],
        ],
        __('Retours') => [
            [__('Puis-je retourner un produit ?'), __('Les délais, conditions et exceptions sont détaillés dans la section « Retours et remboursements » de nos <a href=":terms">conditions générales</a>. Pour lancer un retour, <a href=":contact">contactez-nous</a> avec votre numéro de commande.', ['terms' => route('terms').'#retours', 'contact' => route('pages.contact')])],
        ],
    ];
@endphp

<x-layouts.storefront :title="__('Questions fréquentes')" :description="__('Livraison, paiement, compte, retours : les réponses aux questions les plus fréquentes.')">
    <x-storefront.info-page :title="__('Questions fréquentes')">
        <p class="text-lg">{{ __('Une réponse rapide avant de nous écrire.') }}</p>

        @foreach ($groups as $heading => $items)
            <section class="mt-8" aria-labelledby="faq-{{ $loop->index }}">
                <h2 id="faq-{{ $loop->index }}">{{ $heading }}</h2>
                <div class="border-y border-ink">
                    @foreach ($items as [$question, $answer])
                        <details class="group border-b border-line last:border-b-0">
                            <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-3 font-semibold">
                                {{ $question }}
                                <x-ui.icon name="plus" class="size-5 shrink-0 group-open:hidden" />
                                <x-ui.icon name="minus" class="hidden size-5 shrink-0 group-open:block" />
                            </summary>
                            <div class="pb-4 [&>a]:underline">{!! $answer !!}</div>
                        </details>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="mt-10">
            <h2>{{ __('Vous ne trouvez pas votre réponse ?') }}</h2>
            <p><x-ui.button :href="route('pages.contact')">{{ __('Nous écrire') }}</x-ui.button></p>
        </section>
    </x-storefront.info-page>
</x-layouts.storefront>
