<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Order addresses are JSON objects. Orders placed before this cast existed were stored
 * json_encode()d under Laravel's "array" cast, i.e. double-encoded (a JSON string containing
 * JSON). Reading unwraps both forms, so old and new rows both come back as arrays.
 *
 * @implements CastsAttributes<array<string, mixed>|null, array<string, mixed>|string|null>
 */
class AddressCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = json_decode($value, true) ?? [];
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
