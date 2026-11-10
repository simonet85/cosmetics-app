<?php

namespace App\Http\Requests\Admin;

use App\Support\StorefrontTemplates;
use Illuminate\Foundation\Http\FormRequest;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $rules = [
            'site_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'site_name' => ['nullable', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:500'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'enable_reviews' => ['nullable', 'in:0,1'],
            'enable_wishlist' => ['nullable', 'in:0,1'],
            'from_email' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'send_order_confirmation' => ['nullable', 'in:0,1'],
            'send_shipping_notification' => ['nullable', 'in:0,1'],
            'standard_shipping_cost' => ['nullable', 'numeric', 'min:0'],
            'free_shipping_threshold' => ['nullable', 'numeric', 'min:0'],
            'estimated_delivery_days' => ['nullable', 'string', 'max:50'],
            'home_offer_ends_at' => ['nullable', 'date'],
            'home_offer_title' => ['nullable', 'string', 'max:120'],
            'home_offer_text' => ['nullable', 'string', 'max:300'],
        ];

        foreach (['moneyfusion', 'credit_card', 'paypal', 'bank_transfer', 'cash_on_delivery'] as $method) {
            $rules["payment_{$method}_enabled"] = ['nullable', 'in:0,1'];
        }

        foreach (['home', 'shop'] as $page) {
            $rules["storefront_template_{$page}"] = ['nullable', 'in:'.implode(',', array_keys(StorefrontTemplates::options($page)))];
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'site_logo.image' => 'Le fichier doit être une image.',
            'site_logo.mimes' => 'Le logo doit être au format JPEG, PNG, JPG ou GIF.',
            'site_logo.max' => 'La taille du logo ne doit pas dépasser 2 Mo.',
            'contact_email.email' => 'L\'adresse e-mail de contact doit être valide.',
            'from_email.email' => 'L\'adresse e-mail d\'expédition doit être valide.',
            'tax_rate.numeric' => 'La TVA doit être un nombre.',
            'low_stock_threshold.integer' => 'Le seuil de stock doit être un nombre entier.',
            'standard_shipping_cost.numeric' => 'Les frais de livraison doivent être un nombre.',
            'free_shipping_threshold.numeric' => 'Le seuil de livraison gratuite doit être un nombre.',
            'home_offer_ends_at.date' => 'La date de fin de l\'offre n\'est pas valide.',
        ];

        foreach (['home', 'shop'] as $page) {
            $messages["storefront_template_{$page}.in"] = 'Ce modèle n\'existe pas pour cette page.';
        }

        return $messages;
    }
}
