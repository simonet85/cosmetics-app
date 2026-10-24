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

    public static function status(?string $status): string
    {
        return isset(self::STATUS[$status]) ? __(self::STATUS[$status]) : (string) $status;
    }

    public static function payment(?string $status): string
    {
        return isset(self::PAYMENT[$status]) ? __(self::PAYMENT[$status]) : (string) $status;
    }
}
