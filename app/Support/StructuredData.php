<?php

namespace App\Support;

/**
 * schema.org data (JSON-LD) that the storefront pages put in their head, built only from facts the shop knows:
 * its name, address and logo, and the real path of the page. Nothing here is invented (no brand, no phone number).
 */
class StructuredData
{
    /** The shop itself: name, address, logo. */
    public static function organization(): array
    {
        return [
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('images/others/logo-black-02.png'),
        ];
    }

    /** The site, with the shop search a search engine may offer next to the result. */
    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'name' => config('app.name'),
            'url' => url('/'),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('shop.index').'?search={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * The trail of a page, from the home page to the page itself.
     *
     * @param  list<array{0: string, 1: string}>  $trail  [name, url] pairs, in order
     */
    public static function breadcrumbs(array $trail): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($trail)->values()->map(fn (array $step, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $step[0],
                'item' => $step[1],
            ])->all(),
        ];
    }

    /** One <script> with one or several nodes, ready for the head. */
    public static function script(array ...$nodes): string
    {
        $data = count($nodes) === 1 ? ['@context' => 'https://schema.org', ...$nodes[0]] : ['@context' => 'https://schema.org', '@graph' => array_values($nodes)];

        return '<script type="application/ld+json">'.json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG).'</script>';
    }
}
