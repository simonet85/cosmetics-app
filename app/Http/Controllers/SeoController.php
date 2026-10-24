<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /** Private or useless-to-index areas; everything else is open to crawlers. */
    private const DISALLOWED = ['/admin', '/account', '/cart', '/checkout', '/payment', '/wishlist', '/langue', '/auth', '/login', '/register', '/forgot-password', '/reset-password', '/api'];

    public function robots(): Response
    {
        $lines = ['User-agent: *'];
        foreach (self::DISALLOWED as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /** Public pages, active categories and sellable products. The language is chosen by cookie, so one URL per page. */
    public function sitemap(): Response
    {
        $urls = [];

        foreach (['home', 'shop.index', 'pages.about', 'pages.contact', 'pages.faq', 'privacy', 'terms'] as $route) {
            $urls[] = ['loc' => route($route), 'lastmod' => null];
        }

        foreach (Category::active()->get(['slug', 'updated_at']) as $category) {
            $urls[] = ['loc' => route('shop.category', $category->slug), 'lastmod' => $category->updated_at];
        }

        foreach (Product::active()->get(['slug', 'updated_at']) as $product) {
            $urls[] = ['loc' => route('products.show', $product->slug), 'lastmod' => $product->updated_at];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $url) {
            $xml .= '  <url><loc>'.e($url['loc']).'</loc>';
            if ($url['lastmod']) {
                $xml .= '<lastmod>'.$url['lastmod']->toAtomString().'</lastmod>';
            }
            $xml .= "</url>\n";
        }
        $xml .= "</urlset>\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }
}
