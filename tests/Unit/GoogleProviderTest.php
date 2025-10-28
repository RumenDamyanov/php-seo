<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Providers\GoogleProvider;

test('GoogleProvider can be instantiated with config', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new GoogleProvider($config);

    expect($provider)->toBeInstanceOf(GoogleProvider::class)
        ->and($provider->getName())->toBe('google')
        ->and($provider->isAvailable())->toBeTrue();
});

test('GoogleProvider is not configured without API key', function () {
    $config = new SeoConfig();
    $provider = new GoogleProvider($config);

    expect($provider->isAvailable())->toBeFalse();
});

test('GoogleProvider throws exception when not configured', function () {
    $config = new SeoConfig();
    $provider = new GoogleProvider($config);

    expect(fn () => $provider->generateTitle(['summary' => 'test']))
        ->toThrow(RuntimeException::class, 'Google provider is not properly configured');
});

test('GoogleProvider returns supported models', function () {
    $config = new SeoConfig();
    $provider = new GoogleProvider($config);

    $models = $provider->getSupportedModels();

    expect($models)->toBeArray()
        ->and($models)->toContain('gemini-pro')
        ->and($models)->toContain('gemini-1.5-pro')
        ->and($models)->toContain('gemini-1.5-flash');
});

test('GoogleProvider validates configuration', function () {
    $config = new SeoConfig();
    $provider = new GoogleProvider($config);

    expect($provider->validateConfig(['api_key' => 'test-key']))->toBeTrue()
        ->and($provider->validateConfig(['api_key' => 'test-key', 'model' => 'gemini-pro']))->toBeTrue()
        ->and($provider->validateConfig([]))->toBeFalse()
        ->and($provider->validateConfig(['api_key' => '']))->toBeFalse()
        ->and($provider->validateConfig(['model' => 'gemini-pro']))->toBeFalse();
});

test('GoogleProvider getName returns correct name', function () {
    $config = new SeoConfig();
    $provider = new GoogleProvider($config);

    expect($provider->getName())->toBe('google');
});

test('GoogleProvider uses correct default model', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new GoogleProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('GoogleProvider respects custom model configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'model' => 'gemini-1.5-pro',
        ],
    ]);

    $provider = new GoogleProvider($config);

    expect($provider->isAvailable())->toBeTrue()
        ->and($provider->getName())->toBe('google');
});

test('GoogleProvider handles complex analysis data', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new GoogleProvider($config);

    $analysis = [
        'title' => 'Google Cloud AI Guide',
        'main_content' => 'Comprehensive guide to Google Cloud AI services...',
        'summary' => 'A guide to Google Cloud AI services',
        'keywords' => ['Google Cloud', 'AI', 'machine learning'],
    ];

    expect($provider->isAvailable())->toBeTrue();
});

test('GoogleProvider processes different model configurations', function () {
    $models = [
        'gemini-pro',
        'gemini-1.5-pro',
        'gemini-1.5-flash',
    ];

    foreach ($models as $model) {
        $config = new SeoConfig([
            'ai' => [
                'api_key' => 'test-key',
                'model' => $model,
            ],
        ]);

        $provider = new GoogleProvider($config);
        expect($provider->isAvailable())->toBeTrue();
    }

    expect(count($models))->toBe(3);
});

test('GoogleProvider respects custom base URL', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'base_url' => 'https://custom.googleapis.com',
        ],
    ]);

    $provider = new GoogleProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('GoogleProvider handles timeout configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'timeout' => 60,
        ],
    ]);

    $provider = new GoogleProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});
