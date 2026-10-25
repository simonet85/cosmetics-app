<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Translation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Simonet85\LaravelDeepL\Facades\DeepL;

class SyncTranslations extends Command
{
    protected $signature = 'translations:sync {locale=en : Language to translate into} {--dry-run : List what would be translated, send nothing} {--force : Translate again even when up to date}';

    protected $description = 'Translate the catalogue text (products, categories, banners, tags) with DeepL. Hand-typed translations are kept.';

    /** @var list<class-string<Model>> */
    private const MODELS = [Product::class, Category::class, Banner::class, Tag::class];

    /** International ingredient names are written in capitals ("AQUA (WATER), GLYCERIN, PARFUM"). */
    public static function isInciList(string $text): bool
    {
        return mb_strtoupper($text) === $text && preg_match('/\p{Lu}{3}/u', $text) === 1;
    }

    public function handle(): int
    {
        $locale = (string) $this->argument('locale');
        $default = config('locales.default');

        if ($locale === $default || ! array_key_exists($locale, config('locales.available'))) {
            $this->error("«{$locale}» is not a translation language of this shop.");

            return self::FAILURE;
        }

        // [type, id, field, hash] => French text
        $todo = [];
        foreach (self::MODELS as $class) {
            foreach ($class::all() as $model) {
                foreach ($class::translatableFields() as $field) {
                    $source = $model->getAttribute($field);
                    if (blank($source)) {
                        continue;
                    }

                    // An INCI list (AQUA, ALOE BARBADENSIS LEAF JUICE…) is the same in every language: never sent to DeepL
                    if ($field === 'ingredients' && self::isInciList((string) $source)) {
                        continue;
                    }

                    $existing = Translation::where([
                        'translatable_type' => $model->getMorphClass(), 'translatable_id' => $model->getKey(),
                        'field' => $field, 'locale' => $locale,
                    ])->first();

                    // Typed by hand, or already made from this very text
                    if ($existing && ($existing->source_hash === null || ($existing->source_hash === md5($source) && ! $this->option('force')))) {
                        continue;
                    }

                    $todo[] = ['type' => $model->getMorphClass(), 'id' => $model->getKey(), 'field' => $field, 'source' => $source];
                }
            }
        }

        $characters = array_sum(array_map(fn ($t) => mb_strlen($t['source']), $todo));
        $this->info(count($todo)." text(s) to translate into {$locale} ({$characters} characters).");

        if ($this->option('dry-run') || $todo === []) {
            return self::SUCCESS;
        }

        $translated = DeepL::translate(array_column($todo, 'source'), $locale, $default);

        foreach ($todo as $i => $item) {
            Translation::updateOrCreate(
                ['translatable_type' => $item['type'], 'translatable_id' => $item['id'], 'field' => $item['field'], 'locale' => $locale],
                ['value' => $translated[$i], 'source_hash' => md5($item['source'])],
            );
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
