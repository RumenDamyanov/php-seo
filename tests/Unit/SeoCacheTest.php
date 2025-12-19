<?php

declare(strict_types=1);

use Psr\SimpleCache\CacheInterface;
use Rumenx\PhpSeo\Cache\SeoCache;
use Rumenx\PhpSeo\Config\SeoConfig;

beforeEach(function () {
    $this->cacheImplementation = Mockery::mock(CacheInterface::class);
    $this->config = new SeoConfig(['cache_enabled' => true, 'cache_ttl' => 3600]);
    $this->cache = new SeoCache($this->config, $this->cacheImplementation);
});

afterEach(function () {
    Mockery::close();
});

test('it can get values from cache', function () {
    $this->cacheImplementation
        ->shouldReceive('get')
        ->once()
        ->with('test_key', null)
        ->andReturn('cached_value');

    $result = $this->cache->get('test_key');

    expect($result)->toBe('cached_value');
});

test('it can set values in cache', function () {
    $this->cacheImplementation
        ->shouldReceive('set')
        ->once()
        ->with('test_key', 'test_value', 3600)
        ->andReturn(true);

    $result = $this->cache->set('test_key', 'test_value');

    expect($result)->toBeTrue();
});

test('it can check if key exists', function () {
    $this->cacheImplementation
        ->shouldReceive('has')
        ->once()
        ->with('test_key')
        ->andReturn(true);

    $result = $this->cache->has('test_key');

    expect($result)->toBeTrue();
});

test('it can delete keys from cache', function () {
    $this->cacheImplementation
        ->shouldReceive('delete')
        ->once()
        ->with('test_key')
        ->andReturn(true);

    $result = $this->cache->delete('test_key');

    expect($result)->toBeTrue();
});

test('it can clear all cache', function () {
    $this->cacheImplementation
        ->shouldReceive('clear')
        ->once()
        ->andReturn(true);

    $result = $this->cache->clear();

    expect($result)->toBeTrue();
});

test('it remembers values using callback', function () {
    $this->cacheImplementation
        ->shouldReceive('get')
        ->once()
        ->with('test_key', null)
        ->andReturn(null);

    $this->cacheImplementation
        ->shouldReceive('set')
        ->once()
        ->with('test_key', 'generated_value', 3600)
        ->andReturn(true);

    $result = $this->cache->remember('test_key', fn () => 'generated_value');

    expect($result)->toBe('generated_value');
});

test('it returns cached value if available in remember', function () {
    $this->cacheImplementation
        ->shouldReceive('get')
        ->once()
        ->with('test_key', null)
        ->andReturn('cached_value');

    $result = $this->cache->remember('test_key', fn () => 'generated_value');

    expect($result)->toBe('cached_value');
});

test('it fails gracefully when cache throws exception', function () {
    $this->cacheImplementation
        ->shouldReceive('get')
        ->once()
        ->andThrow(new \Exception('Cache error'));

    $result = $this->cache->get('test_key', 'default');

    expect($result)->toBe('default');
});

test('it returns false when setting fails', function () {
    $this->cacheImplementation
        ->shouldReceive('set')
        ->once()
        ->andThrow(new \Exception('Cache error'));

    $result = $this->cache->set('test_key', 'value');

    expect($result)->toBeFalse();
});

test('it can check if caching is enabled', function () {
    expect($this->cache->isEnabled())->toBeTrue();
});

test('it returns null when cache is disabled', function () {
    $config = new SeoConfig(['cache_enabled' => false]);
    $cache = new SeoCache($config, $this->cacheImplementation);

    $result = $cache->get('test_key');

    expect($result)->toBeNull();
    expect($cache->isEnabled())->toBeFalse();
});

test('it returns false when setting with disabled cache', function () {
    $config = new SeoConfig(['cache_enabled' => false]);
    $cache = new SeoCache($config, $this->cacheImplementation);

    $result = $cache->set('test_key', 'value');

    expect($result)->toBeFalse();
});

test('it executes callback when cache is disabled', function () {
    $config = new SeoConfig(['cache_enabled' => false]);
    $cache = new SeoCache($config, $this->cacheImplementation);

    $result = $cache->remember('test_key', fn () => 'generated');

    expect($result)->toBe('generated');
});

test('it has correct TTL', function () {
    expect($this->cache->getTtl())->toBe(3600);
});

test('it can invalidate content cache', function () {
    $this->cacheImplementation
        ->shouldReceive('delete')
        ->once()
        ->andReturn(true);

    $result = $this->cache->invalidateContent('test content');

    expect($result)->toBeTrue();
});

test('it provides key generator', function () {
    $keyGenerator = $this->cache->keyGenerator();

    expect($keyGenerator)->toBeInstanceOf(\Rumenx\PhpSeo\Cache\CacheKeyGenerator::class);
});

test('it can invalidate all cache', function () {
    $this->cacheImplementation
        ->shouldReceive('clear')
        ->once()
        ->andReturn(true);

    $result = $this->cache->invalidateAll();

    expect($result)->toBeTrue();
});

test('it works when cache implementation is null', function () {
    $cache = new SeoCache($this->config, null);

    expect($cache->isEnabled())->toBeFalse();
    expect($cache->get('key'))->toBeNull();
    expect($cache->set('key', 'value'))->toBeFalse();
    expect($cache->has('key'))->toBeFalse();
    expect($cache->delete('key'))->toBeFalse();
});
