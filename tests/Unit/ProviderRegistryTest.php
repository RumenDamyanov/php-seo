<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
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
