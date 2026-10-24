<?php

namespace App\Support;

use App\Models\Translation;
use Illuminate\Database\Eloquent\Model;

/**
 * Reads the translated text of catalogue models. One query per model type and language for the whole request
 * (the catalogue is small), instead of one per product on a listing. Registered as a singleton.
 */
class Translations
{
    /** @var array<string, array<string, Translation>> "type|locale" => ["id.field" => row] */
    private array $loaded = [];

    /** The text of $field in the current language, or the model's own (French) text when there is none or it is stale. */
    public function get(Model $model, string $field): ?string
    {
        $source = $model->getAttribute($field);
        $locale = app()->getLocale();

        if ($locale === config('locales.default') || blank($source)) {
            return $source;
        }

        $row = $this->forLocale($model->getMorphClass(), $locale)[$model->getKey().'.'.$field] ?? null;

        if ($row && ($row->source_hash === null || $row->source_hash === md5($source))) {
            return $row->value;
        }

        return $source;
    }

    public function flush(): void
    {
        $this->loaded = [];
    }

    /** @return array<string, Translation> */
    private function forLocale(string $type, string $locale): array
    {
        return $this->loaded["{$type}|{$locale}"] ??= Translation::where('translatable_type', $type)
            ->where('locale', $locale)->get()
            ->keyBy(fn (Translation $t) => $t->translatable_id.'.'.$t->field)->all();
    }
}
