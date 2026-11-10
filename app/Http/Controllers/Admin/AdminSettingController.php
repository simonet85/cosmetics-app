<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class AdminSettingController extends Controller
{
    private const SORTABLE_SUBSCRIBERS = ['created_at', 'email', 'subscribed_at', 'unsubscribed_at'];

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

        // Sort: only known columns, stable pagination
        $sortBy = in_array($request->get('sort_by'), self::SORTABLE_SUBSCRIBERS, true) ? $request->get('sort_by') : 'created_at';
        $sortOrder = strtolower((string) $request->get('sort_order')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder)->orderBy('id', $sortOrder);

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

    public function update(SettingsUpdateRequest $request)
    {
        if ($request->hasFile('site_logo')) {
            $oldLogo = DB::table('settings')->where('key', 'site_logo')->value('value');

            $image = Image::read($request->file('site_logo'));

            // Resize to professional logo dimensions (200x60px, maintaining aspect ratio)
            $image->scale(width: 200);

            // Ensure the logo directory exists
            $logoDir = public_path('images/logo');
            if (! is_dir($logoDir)) {
                mkdir($logoDir, 0755, true);
            }

            $path = 'images/logo/logo-'.Str::uuid().'.png';
            $image->save(public_path($path));

            // Update logo setting in database
            DB::table('settings')->updateOrInsert(
                ['key' => 'site_logo'],
                ['value' => $path, 'type' => 'text', 'group' => 'general', 'updated_at' => now()]
            );

            // Drop the previous file only once the new one is safely stored
            if ($oldLogo && $oldLogo !== $path && file_exists(public_path($oldLogo))) {
                unlink(public_path($oldLogo));
            }
        }

        // General settings
        $this->updateSetting('site_name', $request->input('site_name'), 'text', 'general');
        $this->updateSetting('site_description', $request->input('site_description'), 'text', 'general');
        $this->updateSetting('contact_email', $request->input('contact_email'), 'text', 'general');
        $this->updateSetting('phone', $request->input('phone'), 'text', 'general');

        // Store settings
        // The currency is not a setting: every price is in CFA francs (format_fcfa), so there is nothing to save
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

        // Page templates (validated by SettingsUpdateRequest: only the templates offered for the page)
        foreach (['home', 'shop'] as $page) {
            if ($request->filled("storefront_template_{$page}")) {
                $this->updateSetting("storefront_template_{$page}", $request->input("storefront_template_{$page}"), 'text', 'appearance');
            }
        }

        // Limited-time offer of the Glowing Beauty home page (an empty date switches the section off)
        foreach (['home_offer_ends_at', 'home_offer_title', 'home_offer_text'] as $key) {
            $this->updateSetting($key, $request->input($key), 'text', 'appearance');
        }

        // Payment methods settings
        $paymentSettings = [
            'payment_moneyfusion_enabled',
            'payment_credit_card_enabled',
            'payment_paypal_enabled',
            'payment_bank_transfer_enabled',
            'payment_cash_on_delivery_enabled',
        ];

        foreach ($paymentSettings as $setting) {
            $isEnabled = $request->input($setting) === '1' ? '1' : '0';
            $this->updateSetting($setting, $isEnabled, 'boolean', 'payment');
        }

        Cache::forget('settings');

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
                'updated_at' => now(),
            ]
        );
    }
}
