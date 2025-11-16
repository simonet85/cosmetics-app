<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class AdminSettingController extends Controller
{
    public function newsletter(Request $request)
    {
        $query = NewsletterSubscriber::query();

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'subscribed') {
                $query->whereNull('unsubscribed_at');
            } else {
                $query->whereNotNull('unsubscribed_at');
            }
        }

        // Search by email
        if ($request->filled('search')) {
            $query->where('email', 'like', "%{$request->search}%");
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $subscribers = $query->paginate(20)->withQueryString();

        // Statistics
        $stats = [
            'total' => NewsletterSubscriber::count(),
            'subscribed' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
        ];

        return view('admin.newsletter.index', compact('subscribers', 'stats'));
    }

    public function index()
    {
        return view('admin.settings.index');
    }

    public function update(Request $request)
    {
        // Validate logo upload
        if ($request->hasFile('site_logo')) {
            $request->validate([
                'site_logo' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
            ], [
                'site_logo.image' => 'Le fichier doit être une image.',
                'site_logo.mimes' => 'Le logo doit être au format JPEG, PNG, JPG ou GIF.',
                'site_logo.max' => 'La taille du logo ne doit pas dépasser 2 Mo.'
            ]);

            // Delete old logo if exists
            $oldLogo = DB::table('settings')->where('key', 'site_logo')->value('value');
            if ($oldLogo && file_exists(public_path($oldLogo))) {
                unlink(public_path($oldLogo));
            }

            // Process and save new logo
            $image = Image::read($request->file('site_logo'));

            // Resize to professional logo dimensions (200x60px, maintaining aspect ratio)
            $image->scale(width: 200);

            // Ensure the logo directory exists
            $logoDir = public_path('images/logo');
            if (!file_exists($logoDir)) {
                mkdir($logoDir, 0755, true);
            }

            // Generate unique filename
            $filename = 'logo-' . time() . '.png';
            $path = 'images/logo/' . $filename;

            // Save the image
            $image->save(public_path($path));

            // Update logo setting in database
            DB::table('settings')->updateOrInsert(
                ['key' => 'site_logo'],
                [
                    'value' => $path,
                    'type' => 'text',
                    'group' => 'general',
                    'updated_at' => now()
                ]
            );
        }

        // General settings
        $this->updateSetting('site_name', $request->input('site_name'), 'text', 'general');
        $this->updateSetting('site_description', $request->input('site_description'), 'text', 'general');
        $this->updateSetting('contact_email', $request->input('contact_email'), 'text', 'general');
        $this->updateSetting('phone', $request->input('phone'), 'text', 'general');

        // Store settings
        $this->updateSetting('currency', $request->input('currency'), 'text', 'store');
        $this->updateSetting('tax_rate', $request->input('tax_rate'), 'number', 'store');
        $this->updateSetting('low_stock_threshold', $request->input('low_stock_threshold'), 'number', 'store');
        $this->updateSetting('enable_reviews', $request->input('enable_reviews') ? '1' : '0', 'boolean', 'store');
        $this->updateSetting('enable_wishlist', $request->input('enable_wishlist') ? '1' : '0', 'boolean', 'store');

        // Email settings
        $this->updateSetting('from_email', $request->input('from_email'), 'text', 'email');
        $this->updateSetting('from_name', $request->input('from_name'), 'text', 'email');
        $this->updateSetting('send_order_confirmation', $request->input('send_order_confirmation') ? '1' : '0', 'boolean', 'email');
        $this->updateSetting('send_shipping_notification', $request->input('send_shipping_notification') ? '1' : '0', 'boolean', 'email');

        // Shipping settings
        $this->updateSetting('standard_shipping_cost', $request->input('standard_shipping_cost'), 'number', 'shipping');
        $this->updateSetting('free_shipping_threshold', $request->input('free_shipping_threshold'), 'number', 'shipping');
        $this->updateSetting('estimated_delivery_days', $request->input('estimated_delivery_days'), 'text', 'shipping');

        // Payment methods settings
        $paymentSettings = [
            'payment_credit_card_enabled',
            'payment_paypal_enabled',
            'payment_bank_transfer_enabled',
            'payment_cash_on_delivery_enabled'
        ];

        foreach ($paymentSettings as $setting) {
            $isEnabled = $request->input($setting) === '1' ? '1' : '0';
            $this->updateSetting($setting, $isEnabled, 'boolean', 'payment');
        }

        return back()->with('success', 'Paramètres mis à jour avec succès.');
    }

    /**
     * Helper method to update a setting
     */
    private function updateSetting(string $key, $value, string $type, string $group): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'updated_at' => now()
            ]
        );
    }
}
