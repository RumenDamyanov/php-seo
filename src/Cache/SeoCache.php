<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Cache;

use Psr\SimpleCache\CacheInterface;
use Rumenx\PhpSeo\Config\SeoConfig;

/**
 * SEO cache wrapper for managing cached SEO data.
 *
 * Provides a simple interface for caching SEO-related data with automatic
 * TTL management and cache key generation.
 */
class SeoCache
{
    private ?CacheInterface $cache;
    private CacheKeyGenerator $keyGenerator;
    /**
     * @phpstan-ignore-next-line
     */
    private SeoConfig $config;
    private bool $enabled;
    private int $ttl;

    /**
     * @param SeoConfig $config
     * @param CacheInterface|null $cache PSR-16 cache implementation (null disables caching)
     */
    public function __construct(SeoConfig $config, ?CacheInterface $cache = null)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->keyGenerator = new CacheKeyGenerator();
        $this->enabled = $config->get('cache_enabled', true) && $cache !== null;
        $this->ttl = $config->get('cache_ttl', 3600);
    }

    /**
     * Get a value from cache.
     *
     * @param string $key Cache key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->enabled || $this->cache === null) {
            return $default;
        }

        try {
            return $this->cache->get($key, $default);
        } catch (\Throwable $e) {
            // Fail silently, return default
            return $default;
        }
    }

    /**
     * Set a value in cache.
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Time to live (null uses default)
     * @return bool Success status
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        try {
            return $this->cache->set($key, $value, $ttl ?? $this->ttl);
        } catch (\Throwable $e) {
            // Fail silently
            return false;
        }
    }

    /**
     * Check if a key exists in cache.
     *
     * @param string $key Cache key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        try {
            return $this->cache->has($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Delete a key from cache.
     *
     * @param string $key Cache key
     * @return bool Success status
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        try {
            return $this->cache->delete($key);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Clear all cache entries.
     *
     * @return bool Success status
     */
    public function clear(): bool
    {
        if (!$this->enabled || $this->cache === null) {
            return false;
        }

        try {
            return $this->cache->clear();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get or set a cached value using a callback.
     *
     * @param string $key Cache key
     * @param callable $callback Callback to generate value if not cached
     * @param int|null $ttl Time to live (null uses default)
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();

        if ($value !== null) {
            $this->set($key, $value, $ttl);
        }

        return $value;
    }

    /**
     * Get the cache key generator.
     *
     * @return CacheKeyGenerator
     */
    public function keyGenerator(): CacheKeyGenerator
    {
        return $this->keyGenerator;
    }

    /**
     * Check if caching is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get the default TTL.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Invalidate cache for specific content.
     *
     * @param string $content Content to invalidate
     * @param array<string, mixed> $metadata Metadata
     * @return bool Success status
     */
    public function invalidateContent(string $content, array $metadata = []): bool
    {
        $key = $this->keyGenerator->forContentAnalysis($content, $metadata);

        return $this->delete($key);
    }

    /**
     * Invalidate all SEO cache entries (by prefix).
     *
     * Note: This is a simple implementation. For production use with
     * large caches, consider implementing tag-based invalidation.
     *
     * @return bool Success status
     */
    public function invalidateAll(): bool
    {
        return $this->clear();
    }
}
