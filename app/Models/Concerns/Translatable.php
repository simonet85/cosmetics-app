<?php

namespace App\Models\Concerns;

use App\Models\Translation;
use App\Support\Translations;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Catalogue text with a translation per language. The model keeps the French text; list the fields in
 * $translatable and read them on the storefront with $model->tr('name'). The admin keeps using the plain attributes.
 */
trait Translatable
{
    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function tr(string $field): ?string
    {
        return app(Translations::class)->get($this, $field);
    }

    /** @return list<string> */
    public static function translatableFields(): array
    {
        return (new static)->translatable;
    }
}
