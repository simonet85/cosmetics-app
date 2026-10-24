<?php

/*
 * Languages the storefront is offered in. French is the language the texts are written in (translation keys are
 * the French sentences); every other language is a lang/{code}.json file, filled by hand or by
 * `php artisan deepl:translate {code}` (simonet85/laravel-deepl), and — for products, categories and banners —
 * by the `translations:sync` command.
 */
return [
    'default' => 'fr',

    /*
     * code => [name shown in the switcher (in its own language), html/og locale, DeepL target]
     */
    'available' => [
        'fr' => ['name' => 'Français', 'og' => 'fr_FR'],
        'en' => ['name' => 'English', 'og' => 'en_GB'],
    ],

    // Cookie that remembers the visitor's choice
    'cookie' => 'locale',
];
