<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\RateLimiting;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Exceptions\ProviderException;

/**
 * Rate limiter for AI provider requests.
 *
 * Manages rate limiting per provider using token bucket algorithm
 * to prevent exceeding API rate limits.
 */
class RateLimiter
{
    /**
     * @var array<string, TokenBucket>
     */
    private array $buckets = [];

    private SeoConfig $config;
    private bool $enabled;

    public function __construct(SeoConfig $config)
    {
        $this->config = $config;
        $this->enabled = $config->get('ai.rate_limiting.enabled', true);
    }

    /**
     * Attempt to acquire a token for a request.
     *
     * @param string $provider Provider name
     * @return bool True if token was acquired, false otherwise
     * @throws ProviderException If rate limit is exceeded and blocking is enabled
     */
    public function acquire(string $provider): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $bucket = $this->getBucket($provider);

        if ($bucket->consume()) {
            return true;
        }

        // Rate limit exceeded
        $waitTime = $bucket->getTimeUntilNextToken();

        if ($this->config->get('ai.rate_limiting.block_on_limit', true)) {
            throw ProviderException::rateLimitExceeded($provider, $waitTime);
        }

        return false;
    }

    /**
     * Check if a request can be made without consuming a token.
     *
     * @param string $provider Provider name
     * @return bool True if request can be made
     */
    public function canAcquire(string $provider): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $bucket = $this->getBucket($provider);

        return $bucket->hasTokens();
    }

    /**
     * Wait until a token becomes available and acquire it.
     *
     * @param string $provider Provider name
     * @param int $maxWaitSeconds Maximum time to wait (default 30)
     * @return bool True if token was acquired
     */
    public function waitAndAcquire(string $provider, int $maxWaitSeconds = 30): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $bucket = $this->getBucket($provider);
        $waited = 0;

        while (!$bucket->hasTokens() && $waited < $maxWaitSeconds) {
            $waitTime = min($bucket->getTimeUntilNextToken(), (float) ($maxWaitSeconds - $waited));

            if ($waitTime <= 0) {
                break;
            }

            usleep((int) ($waitTime * 1000000));
            $waited += (int) ceil($waitTime);
        }

        return $bucket->consume();
    }

    /**
     * Get the time until next token is available.
     *
     * @param string $provider Provider name
     * @return float Seconds until next token, 0 if available
     */
    public function getWaitTime(string $provider): float
    {
        if (!$this->enabled) {
            return 0.0;
        }

        $bucket = $this->getBucket($provider);

        return $bucket->getTimeUntilNextToken();
    }

    /**
     * Get the number of available tokens for a provider.
     *
     * @param string $provider Provider name
     * @return float Available tokens
     */
    public function getAvailableTokens(string $provider): float
    {
        if (!$this->enabled) {
            return PHP_FLOAT_MAX;
        }

        $bucket = $this->getBucket($provider);

        return $bucket->getAvailableTokens();
    }

    /**
     * Reset rate limits for a provider.
     *
     * @param string $provider Provider name
     * @return void
     */
    public function reset(string $provider): void
    {
        if (isset($this->buckets[$provider])) {
            $this->buckets[$provider]->reset();
        }
    }

    /**
     * Reset all rate limits.
     *
     * @return void
     */
    public function resetAll(): void
    {
        foreach ($this->buckets as $bucket) {
            $bucket->reset();
        }
    }

    /**
     * Check if rate limiting is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get or create a token bucket for a provider.
     *
     * @param string $provider Provider name
     * @return TokenBucket
     */
    private function getBucket(string $provider): TokenBucket
    {
        if (!isset($this->buckets[$provider])) {
            $requestsPerMinute = $this->config->get('ai.rate_limiting.requests_per_minute', 10);

            // Convert requests per minute to capacity and refill rate
            // Capacity allows bursts, refill rate maintains average
            $capacity = max(1, (int) ($requestsPerMinute / 2)); // Allow burst of half the per-minute limit
            $refillRate = $requestsPerMinute / 60.0; // Tokens per second

            $this->buckets[$provider] = new TokenBucket(
                $capacity,
                (int) ceil($refillRate)
            );
        }

        return $this->buckets[$provider];
    }
}
