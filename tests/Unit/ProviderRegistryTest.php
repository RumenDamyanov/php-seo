<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Exceptions\ProviderException;
use Rumenx\PhpSeo\Providers\AnthropicProvider;
use Rumenx\PhpSeo\Providers\OpenAiProvider;
use Rumenx\PhpSeo\Providers\ProviderFactory;
use Rumenx\PhpSeo\Providers\ProviderRegistry;

test('ProviderRegistry can be instantiated', function () {
    $config = new SeoConfig(['ai' => ['api_key' => 'test-key']]);
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);

    expect($registry)->toBeInstanceOf(ProviderRegistry::class);
});

test('ProviderRegistry can register and get provider', function () {
    $config = new SeoConfig(['ai' => ['api_key' => 'test-key']]);
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);
    $provider = new OpenAiProvider($config);
    $registry->register('openai', $provider);

    expect($registry->get('openai'))->toBe($provider);
});

test('ProviderRegistry getPrimary returns default provider', function () {
    $config = new SeoConfig(['ai' => ['api_key' => 'test-key']]);
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);

    $provider = $registry->getPrimary();

    expect($provider)->toBeInstanceOf(OpenAiProvider::class);
});

test('ProviderRegistry handles fallback chain', function () {
    $config = new SeoConfig(['ai' => ['api_key' => 'test-key', 'fallback_chain' => ['openai']]]);
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);

    expect($registry->getFallbackChain())->toBeArray();
});

test('ProviderRegistry get throws exception for unknown provider', function () {
    $config = new SeoConfig(['ai' => ['api_key' => 'test-key']]);
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);

    $registry->get('unknown_provider');
})->throws(ProviderException::class);

test('ProviderRegistry hasAvailableProvider checks if any provider is available', function () {
    $config = new SeoConfig(['ai' => ['api_key' => '']]);  // No API key = no available providers
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);

    // Initially false as no providers are available without API keys
    expect($registry->hasAvailableProvider())->toBeFalse();
});

test('ProviderRegistry getFallbackChainNames returns chain provider names', function () {
    $config = new SeoConfig(['ai' => ['api_key' => 'test-key']]);
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);

    $provider1 = new OpenAiProvider($config);
    $provider2 = new AnthropicProvider($config);

    $registry->register('openai', $provider1);
    $registry->register('anthropic', $provider2);
    $registry->setFallbackChain(['openai', 'anthropic']);

    $names = $registry->getFallbackChainNames();

    expect($names)->toBeArray()
        ->and($names)->toHaveCount(2)
        ->and($names[0])->toBe('openai')
        ->and($names[1])->toBe('anthropic');
});

test('ProviderRegistry getFallbackChain returns actual provider instances', function () {
    $config = new SeoConfig(['ai' => ['api_key' => 'test-key']]);
    $factory = new ProviderFactory($config);
    $registry = new ProviderRegistry($factory, $config);

    $provider1 = new OpenAiProvider($config);
    $provider2 = new AnthropicProvider($config);

    $registry->register('openai', $provider1);
    $registry->register('anthropic', $provider2);
    $registry->setFallbackChain(['openai', 'anthropic']);

    $chain = $registry->getFallbackChain();

    expect($chain)->toBeArray()
        ->and($chain)->toHaveCount(2)
        ->and($chain[0])->toBeInstanceOf(OpenAiProvider::class)
        ->and($chain[1])->toBeInstanceOf(AnthropicProvider::class);
});
