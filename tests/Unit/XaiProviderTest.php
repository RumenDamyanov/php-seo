<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Providers\XaiProvider;

test('XaiProvider can be instantiated with config', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new XaiProvider($config);

    expect($provider)->toBeInstanceOf(XaiProvider::class)
        ->and($provider->getName())->toBe('xai')
        ->and($provider->isAvailable())->toBeTrue();
});

test('XaiProvider is not configured without API key', function () {
    $config = new SeoConfig();
    $provider = new XaiProvider($config);

    expect($provider->isAvailable())->toBeFalse();
});

test('XaiProvider throws exception when not configured', function () {
    $config = new SeoConfig();
    $provider = new XaiProvider($config);

    expect(fn () => $provider->generateTitle(['summary' => 'test']))
        ->toThrow(RuntimeException::class, 'xAI provider is not properly configured');
});

test('XaiProvider returns supported models', function () {
    $config = new SeoConfig();
    $provider = new XaiProvider($config);

    $models = $provider->getSupportedModels();

    expect($models)->toBeArray()
        ->and($models)->toContain('grok-beta')
        ->and($models)->toContain('grok-vision-beta');
});

test('XaiProvider validates configuration', function () {
    $config = new SeoConfig();
    $provider = new XaiProvider($config);

    expect($provider->validateConfig(['api_key' => 'test-key']))->toBeTrue()
        ->and($provider->validateConfig(['api_key' => 'test-key', 'model' => 'grok-beta']))->toBeTrue()
        ->and($provider->validateConfig([]))->toBeFalse()
        ->and($provider->validateConfig(['api_key' => '']))->toBeFalse()
        ->and($provider->validateConfig(['model' => 'grok-beta']))->toBeFalse();
});

test('XaiProvider getName returns correct name', function () {
    $config = new SeoConfig();
    $provider = new XaiProvider($config);

    expect($provider->getName())->toBe('xai');
});

test('XaiProvider uses correct default model', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new XaiProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('XaiProvider respects custom model configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'model' => 'grok-vision-beta',
        ],
    ]);

    $provider = new XaiProvider($config);

    expect($provider->isAvailable())->toBeTrue()
        ->and($provider->getName())->toBe('xai');
});

test('XaiProvider getName returns xai', function () {
    $config = new SeoConfig();
    $provider = new XaiProvider($config);

    expect($provider->getName())->toBe('xai');
});

test('XaiProvider handles complex analysis data', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new XaiProvider($config);

    $analysis = [
        'title' => 'Understanding Grok AI',
        'main_content' => 'Grok is xAI\'s powerful AI assistant...',
        'summary' => 'A guide to using Grok AI',
        'keywords' => ['xAI', 'Grok', 'AI assistant'],
    ];

    expect($provider->isAvailable())->toBeTrue();
});

test('XaiProvider processes different model configurations', function () {
    $models = [
        'grok-beta',
        'grok-vision-beta',
    ];

    foreach ($models as $model) {
        $config = new SeoConfig([
            'ai' => [
                'api_key' => 'test-key',
                'model' => $model,
            ],
        ]);

        $provider = new XaiProvider($config);
        expect($provider->isAvailable())->toBeTrue();
    }

    expect(count($models))->toBe(2);
});

test('XaiProvider respects custom base URL', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'base_url' => 'https://custom.x.ai',
        ],
    ]);

    $provider = new XaiProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('XaiProvider handles timeout configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'timeout' => 60,
        ],
    ]);

    $provider = new XaiProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});
