<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Exceptions\ProviderException;
use Rumenx\PhpSeo\RateLimiting\RateLimiter;

test('it can acquire tokens', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 10,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    $result = $limiter->acquire('openai');

    expect($result)->toBeTrue();
});

test('it tracks tokens per provider', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 10,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    $limiter->acquire('openai');
    $limiter->acquire('anthropic');

    expect($limiter->getAvailableTokens('openai'))->toBeLessThan(5.0);
    expect($limiter->getAvailableTokens('anthropic'))->toBeLessThan(5.0);
});

test('it checks if acquisition is possible', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 10,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    expect($limiter->canAcquire('openai'))->toBeTrue();
});

test('it throws exception when rate limit exceeded', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 2,
                'block_on_limit' => true,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    // Consume all tokens
    $limiter->acquire('openai');

    // This should throw an exception
    $limiter->acquire('openai');
})->throws(ProviderException::class);

test('it returns false when rate limit exceeded and blocking disabled', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 2,
                'block_on_limit' => false,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    // Consume all tokens
    $limiter->acquire('openai');

    // This should return false
    $result = $limiter->acquire('openai');
    expect($result)->toBeFalse();
});

test('it can reset rate limits for a provider', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 10,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    $limiter->acquire('openai');
    $tokensBefore = $limiter->getAvailableTokens('openai');

    $limiter->reset('openai');
    $tokensAfter = $limiter->getAvailableTokens('openai');

    expect($tokensAfter)->toBeGreaterThan($tokensBefore);
});

test('it can reset all rate limits', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 10,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    $limiter->acquire('openai');
    $limiter->acquire('anthropic');

    $limiter->resetAll();

    expect($limiter->getAvailableTokens('openai'))->toBeGreaterThan(4.0);
    expect($limiter->getAvailableTokens('anthropic'))->toBeGreaterThan(4.0);
});

test('it reports correct wait time', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 2,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    // Consume all tokens
    $limiter->acquire('openai');

    $waitTime = $limiter->getWaitTime('openai');
    expect($waitTime)->toBeFloat();
});

test('it reports zero wait time when tokens available', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 10,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    $waitTime = $limiter->getWaitTime('openai');
    expect($waitTime)->toBe(0.0);
});

test('it respects enabled flag', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => false,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    expect($limiter->isEnabled())->toBeFalse();
    expect($limiter->acquire('openai'))->toBeTrue();
});

test('it returns infinite tokens when disabled', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => false,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    expect($limiter->getAvailableTokens('openai'))->toBe(PHP_FLOAT_MAX);
});

test('it waits for tokens when using waitAndAcquire', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 60, // 1 per second
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    // Consume all tokens
    $limiter->acquire('openai');
    $limiter->acquire('openai');
    $limiter->acquire('openai');
    $limiter->acquire('openai');
    $limiter->acquire('openai');

    // This should wait briefly then succeed
    $start = microtime(true);
    $result = $limiter->waitAndAcquire('openai', 2);
    $elapsed = microtime(true) - $start;

    expect($result)->toBeTrue();
    expect($elapsed)->toBeGreaterThan(0.0);
});

test('it creates separate buckets for different providers', function () {
    $config = new SeoConfig([
        'ai' => [
            'rate_limiting' => [
                'enabled' => true,
                'requests_per_minute' => 10,
            ],
        ],
    ]);
    $limiter = new RateLimiter($config);

    $limiter->acquire('openai');
    $limiter->acquire('openai');
    $limiter->acquire('openai');

    // Anthropic should still have full tokens
    expect($limiter->getAvailableTokens('anthropic'))->toBeGreaterThan(4.0);
});
