<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Providers\AnthropicProvider;
use Rumenx\PhpSeo\Providers\GoogleProvider;
use Rumenx\PhpSeo\Providers\OllamaProvider;
use Rumenx\PhpSeo\Providers\OpenAiProvider;
use Rumenx\PhpSeo\Providers\ProviderFactory;
use Rumenx\PhpSeo\Providers\XaiProvider;

test('ProviderFactory can create OpenAI provider', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $factory = new ProviderFactory($config);
    $provider = $factory->create('openai');

    expect($provider)->toBeInstanceOf(OpenAiProvider::class)
        ->and($provider->getName())->toBe('openai');
});

test('ProviderFactory can create Anthropic provider', function () {
    $config = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $factory = new ProviderFactory($config);
    $provider = $factory->create('anthropic');

    expect($provider)->toBeInstanceOf(AnthropicProvider::class)
        ->and($provider->getName())->toBe('anthropic');
});

test('ProviderFactory can create Google provider', function () {
    $config = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $factory = new ProviderFactory($config);
    $provider = $factory->create('google');

    expect($provider)->toBeInstanceOf(GoogleProvider::class)
        ->and($provider->getName())->toBe('google');
});

test('ProviderFactory can create XAI provider', function () {
    $config = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $factory = new ProviderFactory($config);
    $provider = $factory->create('xai');

    expect($provider)->toBeInstanceOf(XaiProvider::class)
        ->and($provider->getName())->toBe('xai');
});

test('ProviderFactory can create Ollama provider', function () {
    $config = new SeoConfig([
        'ai' => ['model' => 'llama3.2'],
    ]);

    $factory = new ProviderFactory($config);
    $provider = $factory->create('ollama');

    expect($provider)->toBeInstanceOf(OllamaProvider::class)
        ->and($provider->getName())->toBe('ollama');
});

test('ProviderFactory throws exception for unknown provider', function () {
    $config = new SeoConfig();
    $factory = new ProviderFactory($config);

    expect(fn () => $factory->create('unknown'))
        ->toThrow(\Exception::class);
});

test('ProviderFactory createDefault returns OpenAI provider', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
        ],
    ]);

    $factory = new ProviderFactory($config);
    $provider = $factory->createDefault();

    expect($provider)->toBeInstanceOf(OpenAiProvider::class)
        ->and($provider->getName())->toBe('openai');
});

test('ProviderFactory createAll returns all providers', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'model' => 'llama3.2',
        ],
    ]);

    $factory = new ProviderFactory($config);
    $providers = $factory->createAll();

    expect($providers)->toBeArray()
        ->and(count($providers))->toBe(5);
});

test('ProviderFactory isProviderAvailable checks OpenAI availability', function () {
    $configAvailable = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $configUnavailable = new SeoConfig();

    $factoryAvailable = new ProviderFactory($configAvailable);
    $factoryUnavailable = new ProviderFactory($configUnavailable);

    expect($factoryAvailable->isProviderAvailable('openai'))->toBeTrue()
        ->and($factoryUnavailable->isProviderAvailable('openai'))->toBeFalse();
});

test('ProviderFactory isProviderAvailable checks Anthropic availability', function () {
    $configAvailable = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $configUnavailable = new SeoConfig();

    $factoryAvailable = new ProviderFactory($configAvailable);
    $factoryUnavailable = new ProviderFactory($configUnavailable);

    expect($factoryAvailable->isProviderAvailable('anthropic'))->toBeTrue()
        ->and($factoryUnavailable->isProviderAvailable('anthropic'))->toBeFalse();
});

test('ProviderFactory isProviderAvailable checks Google availability', function () {
    $configAvailable = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $configUnavailable = new SeoConfig();

    $factoryAvailable = new ProviderFactory($configAvailable);
    $factoryUnavailable = new ProviderFactory($configUnavailable);

    expect($factoryAvailable->isProviderAvailable('google'))->toBeTrue()
        ->and($factoryUnavailable->isProviderAvailable('google'))->toBeFalse();
});

test('ProviderFactory isProviderAvailable checks XAI availability', function () {
    $configAvailable = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $configUnavailable = new SeoConfig();

    $factoryAvailable = new ProviderFactory($configAvailable);
    $factoryUnavailable = new ProviderFactory($configUnavailable);

    expect($factoryAvailable->isProviderAvailable('xai'))->toBeTrue()
        ->and($factoryUnavailable->isProviderAvailable('xai'))->toBeFalse();
});

test('ProviderFactory isProviderAvailable checks Ollama availability', function () {
    $configAvailable = new SeoConfig([
        'ai' => ['model' => 'llama3.2'],
    ]);

    $configUnavailable = new SeoConfig([
        'ai' => ['model' => ''], // Explicitly empty model
    ]);

    $factoryAvailable = new ProviderFactory($configAvailable);
    $factoryUnavailable = new ProviderFactory($configUnavailable);

    expect($factoryAvailable->isProviderAvailable('ollama'))->toBeTrue()
        ->and($factoryUnavailable->isProviderAvailable('ollama'))->toBeFalse();
});

test('ProviderFactory returns false for unknown provider availability', function () {
    $config = new SeoConfig();
    $factory = new ProviderFactory($config);

    expect($factory->isProviderAvailable('unknown'))->toBeFalse();
});

test('ProviderFactory createAll only creates configured providers', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'model' => 'gpt-4o-mini', // Explicitly use OpenAI model
        ],
    ]);

    $factory = new ProviderFactory($config);
    $providers = $factory->createAll();

    expect($providers)->toBeArray()
        ->and(count($providers))->toBe(4); // OpenAI, Anthropic, Google, XAI (not Ollama with OpenAI model)
});

test('ProviderFactory handles mixed provider configurations', function () {
    $config = new SeoConfig([
        'ai' => [
            'api_key' => 'test-key',
            'model' => 'llama3.2',
        ],
    ]);

    $factory = new ProviderFactory($config);
    $providers = $factory->createAll();

    expect($providers)->toBeArray()
        ->and(count($providers))->toBe(5); // All providers available
});

test('ProviderFactory create method is case-insensitive', function () {
    $config = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $factory = new ProviderFactory($config);

    // Factory normalizes case to lowercase
    $provider1 = $factory->create('OpenAI');
    $provider2 = $factory->create('OPENAI');

    expect($provider1)->toBeInstanceOf(OpenAiProvider::class)
        ->and($provider2)->toBeInstanceOf(OpenAiProvider::class);
});

test('ProviderFactory isProviderAvailable is case-insensitive', function () {
    $config = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $factory = new ProviderFactory($config);

    // Factory normalizes case to lowercase
    expect($factory->isProviderAvailable('OpenAI'))->toBeTrue()
        ->and($factory->isProviderAvailable('OPENAI'))->toBeTrue()
        ->and($factory->isProviderAvailable('openai'))->toBeTrue();
});

test('ProviderFactory getSupportedProviders returns all provider names', function () {
    $config = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $factory = new ProviderFactory($config);
    $providers = $factory->getSupportedProviders();

    expect($providers)->toBeArray()
        ->and($providers)->toContain('openai')
        ->and($providers)->toContain('anthropic')
        ->and($providers)->toContain('google')
        ->and($providers)->toContain('xai')
        ->and($providers)->toContain('ollama');
});

test('ProviderFactory getAvailableProviders returns only available provider names', function () {
    $config = new SeoConfig([
        'ai' => ['api_key' => 'test-key'],
    ]);

    $factory = new ProviderFactory($config);
    $availableProviders = $factory->getAvailableProviders();

    expect($availableProviders)->toBeArray();

    // Each name in the available list should correspond to an available provider
    foreach ($availableProviders as $providerName) {
        expect($providerName)->toBeString()
            ->and($factory->isProviderAvailable($providerName))->toBeTrue();
    }
});
