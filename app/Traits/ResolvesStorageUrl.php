<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait ResolvesStorageUrl
{
    /**
     * Ensure a given storage/media path or url becomes a fully qualified URL.
     * - If $value is already an absolute URL (http/https) it is returned as-is.
     * - If it starts with '/storage' it is prefixed with app url.
     * - Otherwise it's assumed to be a storage path and prefixed with '/storage/'.
     *
     * @param string|null $value
     * @return string|null
     */
    protected function resolveStorageUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        $appUrl = rtrim(config('app.url') ?? env('APP_URL', ''), '/');

        // If value already contains the storage prefix
        if (Str::startsWith($value, '/storage')) {
            return $appUrl ? $appUrl . $value : $value;
        }

        // If value looks like a storage path (e.g., brands/xxx.png)
        return $appUrl ? $appUrl . '/storage/' . ltrim($value, '/') : '/storage/' . ltrim($value, '/');
    }
}
