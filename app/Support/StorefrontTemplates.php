<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * The page templates the shop owner can switch in the admin (Réglages > Apparence).
 *
 * A template changes the layout and the look of one page (the home page, the shop list), never its data, its routes
 * or the cart and checkout. "classic" is the original design; "beauty" is the Glowing Beauty layout.
 * A signed-in admin can preview a template without saving it with ?template=beauty.
 */
class StorefrontTemplates
{
    public const DEFAULT = 'classic';

    /** @var array<string, array<string, string>> page => template key => label shown in the admin */
    private const AVAILABLE = [
        'home' => ['classic' => 'Classique'],
        'shop' => ['classic' => 'Classique', 'beauty' => 'Glowing Beauty'],
    ];

    /** @return array<string, string> template key => label, for one page */
    public static function options(string $page): array
    {
        return self::AVAILABLE[$page] ?? [];
    }

    /** The template of a page: the admin preview, else the saved setting, else the classic one. */
    public static function for(string $page, ?Request $request = null): string
    {
        $options = self::options($page);

        $preview = $request?->query('template');
        if ($preview !== null && $request->user()?->isAdmin() && array_key_exists($preview, $options)) {
            return $preview;
        }

        $saved = (string) getSetting("storefront_template_{$page}", self::DEFAULT);

        return array_key_exists($saved, $options) ? $saved : self::DEFAULT;
    }
}
