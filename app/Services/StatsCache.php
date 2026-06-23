<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Phase 8 — a tiny, driver-agnostic cache layer for the dashboard / analytics
 * aggregates and the frequently-read dropdown lists.
 *
 * Strategy: a "version counter". Every cached value's key embeds the current
 * version integer. When the underlying data changes (a lead or timeline event
 * is written), {@see StatsCache::bump()} increments the version, which instantly
 * orphans every previously-cached key — the next read recomputes, and the stale
 * keys expire on their own TTL. This gives whole-namespace invalidation without
 * cache tags, so it works on the database / file / array stores the app uses
 * (tags require Redis/Memcached).
 *
 * Keep cached closures side-effect free: they may not run on a cache hit.
 */
class StatsCache
{
    /** Cache key holding the current namespace version. */
    private const VERSION_KEY = 'stats:version';

    /** Default TTL for computed stats (seconds). */
    public const STATS_TTL = 300;

    /** Default TTL for dropdown lists (seconds). */
    public const DROPDOWN_TTL = 3600;

    /**
     * Remember a computed value under the current stats version.
     *
     * @template T
     * @param  \Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, \Closure $callback, int $ttl = self::STATS_TTL): mixed
    {
        return Cache::remember(self::versionedKey($key), $ttl, $callback);
    }

    /**
     * Build the fully-qualified, version-stamped cache key.
     */
    public static function versionedKey(string $key): string
    {
        return 'stats:v'.self::version().':'.$key;
    }

    /**
     * Current namespace version (seeded to 1 on first use).
     */
    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    /**
     * Invalidate every cached stat by advancing the namespace version.
     *
     * Called from the Lead / LeadEvent model events whenever data that feeds the
     * dashboard or analytics changes. Cheap (a single increment) and safe to call
     * often. Falls back to a forever-write if the store has no atomic increment.
     */
    public static function bump(): void
    {
        // `increment` is a no-op (returns false) when the key is missing on some
        // stores, so make sure it exists first.
        if (Cache::get(self::VERSION_KEY) === null) {
            Cache::forever(self::VERSION_KEY, 1);
        }

        if (Cache::increment(self::VERSION_KEY) === false) {
            Cache::forever(self::VERSION_KEY, self::version() + 1);
        }
    }
}
