<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;

/** The invoice PDF, built the same way for the confirmation mail and the admin download. */
class InvoicePdf
{
    public function make(Order $order): DomPdf
    {
        $order->loadMissing('moneyFusionPayment');

        return Pdf::loadView('emails.invoice', ['order' => $order]);
    }
}
