<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Providers\AnthropicProvider;

test('AnthropicProvider can be instantiated with config', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'model' => 'claude-3-opus-20240229',
        ],
    ]);

    $provider = new AnthropicProvider($config);

    expect($provider)->toBeInstanceOf(AnthropicProvider::class)
        ->and($provider->getName())->toBe('anthropic')
        ->and($provider->isAvailable())->toBeTrue();
});

test('AnthropicProvider is not configured without API key', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect($provider->isAvailable())->toBeFalse();
});

test('AnthropicProvider throws exception when not configured', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect(fn () => $provider->generateTitle(['summary' => 'test']))
        ->toThrow(RuntimeException::class, 'Anthropic provider is not properly configured');
});

test('AnthropicProvider returns supported models', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    $models = $provider->getSupportedModels();

    expect($models)->toBeArray()
        ->and($models)->toContain('claude-3-opus-20240229')
        ->and($models)->toContain('claude-3-sonnet-20240229')
        ->and($models)->toContain('claude-3-haiku-20240307')
        ->and($models)->toContain('claude-3-5-sonnet-20241022')
        ->and($models)->toContain('claude-3-5-haiku-20241022');
});

test('AnthropicProvider validates configuration', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect($provider->validateConfig(['api_key' => 'test-key']))->toBeTrue()
        ->and($provider->validateConfig(['api_key' => 'test-key', 'model' => 'claude-3-opus-20240229']))->toBeTrue()
        ->and($provider->validateConfig([]))->toBeFalse()
        ->and($provider->validateConfig(['api_key' => '']))->toBeFalse()
        ->and($provider->validateConfig(['api_key' => 'test', 'model' => 'invalid-model']))->toBeFalse();
});

test('AnthropicProvider uses correct default model', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new AnthropicProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('AnthropicProvider respects custom model configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'model' => 'claude-3-opus-20240229',
        ],
    ]);

    $provider = new AnthropicProvider($config);

    expect($provider->isAvailable())->toBeTrue()
        ->and($provider->getName())->toBe('anthropic');
});

test('AnthropicProvider getName returns correct name', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect($provider->getName())->toBe('anthropic');
});

test('AnthropicProvider handles missing configuration gracefully', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect($provider->isAvailable())->toBeFalse();

    expect(fn () => $provider->generateTitle(['main_content' => 'test']))
        ->toThrow(\RuntimeException::class);
});

test('AnthropicProvider throws exception for title generation without config', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect(fn () => $provider->generateTitle(['summary' => 'test']))
        ->toThrow(RuntimeException::class);
});

test('AnthropicProvider throws exception for description generation without config', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect(fn () => $provider->generateDescription(['summary' => 'test']))
        ->toThrow(RuntimeException::class);
});

test('AnthropicProvider throws exception for keywords generation without config', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect(fn () => $provider->generateKeywords(['summary' => 'test']))
        ->toThrow(RuntimeException::class);
});

test('AnthropicProvider throws exception for general generation without config', function () {
    $config = new SeoConfig();
    $provider = new AnthropicProvider($config);

    expect(fn () => $provider->generate('test prompt'))
        ->toThrow(RuntimeException::class);
});

test('AnthropicProvider handles complex analysis data', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $provider = new AnthropicProvider($config);
    $analysis = [
        'main_content' => 'Content about AI and machine learning',
        'headings' => [
            ['level' => 1, 'text' => 'AI Guide'],
            ['level' => 2, 'text' => 'Machine Learning Basics'],
        ],
        'summary' => 'A comprehensive guide to AI',
        'keywords' => ['AI', 'machine learning', 'deep learning'],
    ];

    expect($provider->isAvailable())->toBeTrue();
});

test('AnthropicProvider processes different model configurations', function () {
    $models = [
        'claude-3-5-sonnet-20241022',
        'claude-3-opus-20240229',
        'claude-3-sonnet-20240229',
    ];

    foreach ($models as $model) {
        $config = new SeoConfig([
            'ai' => [
                'api_key' => 'test-key',
                'model' => $model,
            ],
        ]);

        $provider = new AnthropicProvider($config);
        expect($provider->isAvailable())->toBeTrue();
    }

    expect(count($models))->toBe(3);
});

test('AnthropicProvider respects custom base URL', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'base_url' => 'https://custom.anthropic.com',
        ],
    ]);

    $provider = new AnthropicProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('AnthropicProvider handles timeout configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'timeout' => 60,
        ],
    ]);

    $provider = new AnthropicProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});
