<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\RateLimiting;

/**
 * Token Bucket implementation for rate limiting.
 *
 * This class implements the token bucket algorithm, which allows bursts
 * of requests while maintaining an average rate over time.
 */
class TokenBucket
{
    private float $tokens;
    private float $lastRefillTime;
    private int $capacity;
    private int $refillRate;

    /**
     * @param int $capacity Maximum number of tokens (requests) that can be accumulated
     * @param int $refillRate Number of tokens added per second
     */
    public function __construct(int $capacity, int $refillRate)
    {
        $this->capacity = $capacity;
        $this->refillRate = $refillRate;
        $this->tokens = (float) $capacity;
        $this->lastRefillTime = microtime(true);
    }

    /**
     * Try to consume a token.
     *
     * @param int $tokens Number of tokens to consume (default 1)
     * @return bool True if tokens were available and consumed, false otherwise
     */
    public function consume(int $tokens = 1): bool
    {
        $this->refill();

        if ($this->tokens >= $tokens) {
            $this->tokens -= $tokens;

            return true;
        }

        return false;
    }

    /**
     * Check if tokens are available without consuming them.
     *
     * @param int $tokens Number of tokens to check for
     * @return bool True if tokens are available
     */
    public function hasTokens(int $tokens = 1): bool
    {
        $this->refill();

        return $this->tokens >= $tokens;
    }

    /**
     * Get the current number of available tokens.
     *
     * @return float Current token count
     */
    public function getAvailableTokens(): float
    {
        $this->refill();

        return $this->tokens;
    }

    /**
     * Get the time in seconds until the next token is available.
     *
     * @return float Seconds until next token, 0 if tokens are available
     */
    public function getTimeUntilNextToken(): float
    {
        $this->refill();

        if ($this->tokens >= 1.0) {
            return 0.0;
        }

        $tokensNeeded = 1.0 - $this->tokens;

        return $tokensNeeded / $this->refillRate;
    }

    /**
     * Reset the bucket to full capacity.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->tokens = (float) $this->capacity;
        $this->lastRefillTime = microtime(true);
    }

    /**
     * Refill tokens based on elapsed time.
     *
     * @return void
     */
    private function refill(): void
    {
        $now = microtime(true);
        $timePassed = $now - $this->lastRefillTime;
        $tokensToAdd = $timePassed * $this->refillRate;

        if ($tokensToAdd > 0) {
            $this->tokens = min($this->tokens + $tokensToAdd, (float) $this->capacity);
            $this->lastRefillTime = $now;
        }
    }

    /**
     * Get the bucket capacity.
     *
     * @return int
     */
    public function getCapacity(): int
    {
        return $this->capacity;
    }

    /**
     * Get the refill rate (tokens per second).
     *
     * @return int
     */
    public function getRefillRate(): int
    {
        return $this->refillRate;
    }
}
