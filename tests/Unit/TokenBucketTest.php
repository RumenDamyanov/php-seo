<?php

declare(strict_types=1);

use Rumenx\PhpSeo\RateLimiting\TokenBucket;

test('it can consume tokens', function () {
    $bucket = new TokenBucket(10, 1);

    $result = $bucket->consume(1);

    expect($result)->toBeTrue();
    expect($bucket->getAvailableTokens())->toBeGreaterThanOrEqual(9.0);
    expect($bucket->getAvailableTokens())->toBeLessThan(10.0);
});

test('it cannot consume more tokens than available', function () {
    $bucket = new TokenBucket(5, 1);

    $result = $bucket->consume(6);

    expect($result)->toBeFalse();
    expect($bucket->getAvailableTokens())->toBe(5.0);
});

test('it checks token availability', function () {
    $bucket = new TokenBucket(5, 1);

    expect($bucket->hasTokens(5))->toBeTrue();
    expect($bucket->hasTokens(6))->toBeFalse();
});

test('it refills tokens over time', function () {
    $bucket = new TokenBucket(10, 10); // 10 tokens per second

    $bucket->consume(5);
    $tokensAfterConsume = $bucket->getAvailableTokens();
    expect($tokensAfterConsume)->toBeLessThan(5.5); // Allow for small refill

    // Wait 100ms (0.1 seconds) = 1 token should be added
    usleep(100000);

    // Should have more tokens now
    expect($bucket->getAvailableTokens())->toBeGreaterThan($tokensAfterConsume);
});

test('it does not exceed capacity', function () {
    $bucket = new TokenBucket(10, 100); // High refill rate

    usleep(200000); // Wait to allow refill

    expect($bucket->getAvailableTokens())->toBeLessThanOrEqual(10.0);
});

test('it can be reset', function () {
    $bucket = new TokenBucket(10, 1);

    $bucket->consume(5);
    expect($bucket->getAvailableTokens())->toBeLessThan(5.5); // Allow for small refill

    $bucket->reset();
    expect($bucket->getAvailableTokens())->toBe(10.0);
});

test('it calculates time until next token', function () {
    $bucket = new TokenBucket(1, 1); // 1 token per second

    $bucket->consume(1);

    $waitTime = $bucket->getTimeUntilNextToken();
    expect($waitTime)->toBeGreaterThan(0.0);
    expect($waitTime)->toBeLessThanOrEqual(1.0);
});

test('it returns zero wait time when tokens are available', function () {
    $bucket = new TokenBucket(10, 1);

    $waitTime = $bucket->getTimeUntilNextToken();
    expect($waitTime)->toBe(0.0);
});

test('it has correct capacity', function () {
    $bucket = new TokenBucket(15, 1);

    expect($bucket->getCapacity())->toBe(15);
});

test('it has correct refill rate', function () {
    $bucket = new TokenBucket(10, 5);

    expect($bucket->getRefillRate())->toBe(5);
});

test('it can consume multiple tokens at once', function () {
    $bucket = new TokenBucket(10, 1);

    $result = $bucket->consume(3);

    expect($result)->toBeTrue();
    expect($bucket->getAvailableTokens())->toBeGreaterThanOrEqual(7.0);
    expect($bucket->getAvailableTokens())->toBeLessThan(10.0);
});

test('it handles fractional tokens correctly', function () {
    $bucket = new TokenBucket(10, 1);

    $bucket->consume(5);
    $initialTokens = $bucket->getAvailableTokens();

    // After some time, tokens should accumulate as fractions
    usleep(50000); // 0.05 seconds

    $tokens = $bucket->getAvailableTokens();
    expect($tokens)->toBeFloat();
    expect($tokens)->toBeGreaterThanOrEqual($initialTokens);
});

test('it starts with full capacity', function () {
    $bucket = new TokenBucket(20, 2);

    expect($bucket->getAvailableTokens())->toBe(20.0);
});
