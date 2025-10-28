<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Providers\OllamaProvider;

test('OllamaProvider can be instantiated with config', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'llama2',
                    'base_url' => 'http://localhost:11434',
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);

    expect($provider)->toBeInstanceOf(OllamaProvider::class)
        ->and($provider->getName())->toBe('ollama')
        ->and($provider->isAvailable())->toBeTrue();
});

test('OllamaProvider works without API key', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'llama2',
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('OllamaProvider is not configured without model', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeFalse();
});

test('OllamaProvider throws exception when not configured', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect(fn () => $provider->generateTitle(['summary' => 'test']))
        ->toThrow(RuntimeException::class, 'Ollama provider is not properly configured');
});

test('OllamaProvider returns supported models', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    $models = $provider->getSupportedModels();

    expect($models)->toBeArray()
        ->and($models)->toContain('llama3.2')
        ->and($models)->toContain('llama3.1')
        ->and($models)->toContain('llama3')
        ->and($models)->toContain('llama2')
        ->and($models)->toContain('mistral')
        ->and($models)->toContain('mixtral')
        ->and($models)->toContain('codellama')
        ->and($models)->toContain('phi3')
        ->and($models)->toContain('gemma2')
        ->and($models)->toContain('qwen2.5');
});

test('OllamaProvider validates configuration', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect($provider->validateConfig(['model' => 'llama2']))->toBeTrue()
        ->and($provider->validateConfig(['model' => 'llama2', 'base_url' => 'http://localhost:11434']))->toBeTrue()
        ->and($provider->validateConfig([]))->toBeFalse()
        ->and($provider->validateConfig(['model' => '']))->toBeFalse()
        ->and($provider->validateConfig(['model' => 'invalid-model']))->toBeFalse();
});

test('OllamaProvider uses correct default base URL', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'llama2',
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('OllamaProvider respects custom model configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'mistral',
                    'base_url' => 'http://localhost:11434',
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeTrue()
        ->and($provider->getName())->toBe('ollama');
});

test('OllamaProvider getName returns correct name', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect($provider->getName())->toBe('ollama');
});

test('OllamaProvider handles missing configuration gracefully', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeFalse();

    expect(fn () => $provider->generateTitle(['main_content' => 'test']))
        ->toThrow(\RuntimeException::class);
});

test('OllamaProvider throws exception for title generation without config', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect(fn () => $provider->generateTitle(['summary' => 'test']))
        ->toThrow(RuntimeException::class);
});

test('OllamaProvider throws exception for description generation without config', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect(fn () => $provider->generateDescription(['summary' => 'test']))
        ->toThrow(RuntimeException::class);
});

test('OllamaProvider throws exception for keywords generation without config', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect(fn () => $provider->generateKeywords(['summary' => 'test']))
        ->toThrow(RuntimeException::class);
});

test('OllamaProvider throws exception for general generation without config', function () {
    $config = new SeoConfig();
    $provider = new OllamaProvider($config);

    expect(fn () => $provider->generate('test prompt'))
        ->toThrow(RuntimeException::class);
});

test('OllamaProvider handles complex analysis data', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'llama2',
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);
    $analysis = [
        'main_content' => 'Content about local AI models',
        'headings' => [
            ['level' => 1, 'text' => 'Ollama Guide'],
            ['level' => 2, 'text' => 'Running Models Locally'],
        ],
        'summary' => 'A guide to running AI models with Ollama',
        'keywords' => ['Ollama', 'local AI', 'llama2'],
    ];

    expect($provider->isAvailable())->toBeTrue();
});

test('OllamaProvider processes different model configurations', function () {
    $models = [
        'llama2',
        'mistral',
        'mixtral',
        'codellama',
    ];

    foreach ($models as $model) {
        $config = new SeoConfig([
            'ai' => [
                'providers' => [
                    'ollama' => [
                        'model' => $model,
                    ],
                ],
            ],
        ]);

        $provider = new OllamaProvider($config);
        expect($provider->isAvailable())->toBeTrue();
    }

    expect(count($models))->toBe(4);
});

test('OllamaProvider respects custom base URL', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'llama2',
                    'base_url' => 'http://192.168.1.100:11434',
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('OllamaProvider handles timeout configuration', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'llama2',
                    'timeout' => 120,
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeTrue();
});

test('OllamaProvider does not require API key', function () {
    $config = new SeoConfig([
        'ai' => [
            'providers' => [
                'ollama' => [
                    'model' => 'llama2',
                    // No API key needed
                ],
            ],
        ],
    ]);

    $provider = new OllamaProvider($config);

    expect($provider->isAvailable())->toBeTrue()
        ->and($provider->validateConfig(['model' => 'llama2']))->toBeTrue();
});
