<?php

namespace App\Support;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Master Settings helper.
 *
 * Reads defaults from config('zmc.master_settings') and merges with persisted
 * values stored in system_configs under key: master_settings.
 *
 * Performance: results are cached both in-process (per-request) and in the
 * application cache (5 min TTL), so the per-request global gatekeeper does
 * not hit the database on every page load. The cache is invalidated on set().
 */
class MasterSettings
{
    private const CACHE_KEY = 'master_settings.all.v1';
    private const CACHE_TTL = 300; // 5 minutes
    private static ?array $memo = null;

    public static function defaults(): array
    {
        return (array) config('zmc.master_settings', []);
    }

    public static function all(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        $defaults = static::defaults();

        try {
            $saved = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                if (!Schema::hasTable('system_configs')) {
                    return [];
                }
                return (array) SystemConfig::getValue('master_settings', []);
            });
        } catch (\Throwable $e) {
            $saved = [];
        }

        return self::$memo = static::mergeRecursiveDistinct($defaults, (array) $saved);
    }

    public static function get(string $path, $default = null)
    {
        $data = static::all();
        foreach (explode('.', $path) as $seg) {
            if (!is_array($data) || !array_key_exists($seg, $data)) {
                return $default;
            }
            $data = $data[$seg];
        }
        return $data;
    }

    public static function set(array $value): void
    {
        SystemConfig::setValue('master_settings', $value);
        self::flush();
    }

    public static function flush(): void
    {
        self::$memo = null;
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private static function mergeRecursiveDistinct(array $base, array $replacements): array
    {
        foreach ($replacements as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = static::mergeRecursiveDistinct($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
}
