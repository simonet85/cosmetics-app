<?php

namespace App\Support;

/** French wording of order and payment states, so raw enum values ("pending") never reach a customer. */
class OrderLabels
{
    private const STATUS = [
        'pending' => 'En attente',
        'processing' => 'En préparation',
        'shipped' => 'Expédiée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];

    private const PAYMENT = [
        'pending' => 'En attente',
        'paid' => 'Payé',
        'failed' => 'Échoué',
        'refunded' => 'Remboursé',
    ];

    /** @return array<string, string> value => label, for the admin selects */
    public static function statuses(): array
    {
        return array_map(fn ($label) => __($label), self::STATUS);
    }

    /** @return array<string, string> value => label, for the admin selects */
    public static function payments(): array
    {
        return array_map(fn ($label) => __($label), self::PAYMENT);
    }

    public static function status(?string $status): string
    {
        return isset(self::STATUS[$status]) ? __(self::STATUS[$status]) : (string) $status;
    }

    public static function payment(?string $status): string
    {
        return isset(self::PAYMENT[$status]) ? __(self::PAYMENT[$status]) : (string) $status;
    }
}
