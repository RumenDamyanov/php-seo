<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Providers;

use Rumenx\PhpSeo\Cache\SeoCache;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\ProviderInterface;
use Rumenx\PhpSeo\Exceptions\ProviderException;
use Rumenx\PhpSeo\RateLimiting\RateLimiter;

/**
 * Factory for creating AI provider instances.
 *
 * This factory manages the creation and configuration of different AI providers
 * based on the application configuration.
 */
class ProviderFactory
{
    /**
     * Map of provider names to their class names.
     *
     * @var array<string, class-string<ProviderInterface>>
     */
    private const PROVIDER_MAP = [
        'openai' => OpenAiProvider::class,
        'anthropic' => AnthropicProvider::class,
        'google' => GoogleProvider::class,
        'xai' => XaiProvider::class,
        'ollama' => OllamaProvider::class,
    ];

    private SeoConfig $config;
    private ?SeoCache $cache = null;
    private ?RateLimiter $rateLimiter = null;

    public function __construct(
        SeoConfig $config,
        ?SeoCache $cache = null,
        ?RateLimiter $rateLimiter = null
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->rateLimiter = $rateLimiter ?? new RateLimiter($config);
    }

    /**
     * Create a provider instance by name.
     *
     * @param string $name The provider name (e.g., 'openai', 'anthropic')
     * @return ProviderInterface
     * @throws ProviderException
     */
    public function create(string $name): ProviderInterface
    {
        $name = strtolower($name);

        if (!isset(self::PROVIDER_MAP[$name])) {
            throw ProviderException::configurationError(
                $name,
                "Unknown provider: {$name}. Available providers: " . implode(', ', array_keys(self::PROVIDER_MAP))
            );
        }

        $className = self::PROVIDER_MAP[$name];

        return new $className($this->config, $this->cache, $this->rateLimiter);
    }

    /**
     * Create the default provider based on configuration.
     *
     * @return ProviderInterface
     * @throws ProviderException
     */
    public function createDefault(): ProviderInterface
    {
        $defaultProvider = $this->config->get('ai.default_provider', 'openai');

        return $this->create($defaultProvider);
    }

    /**
     * Create all available providers that are properly configured.
     *
     * @return array<string, ProviderInterface>
     */
    public function createAll(): array
    {
        $providers = [];

        foreach (array_keys(self::PROVIDER_MAP) as $name) {
            try {
                $provider = $this->create($name);
                if ($provider->isAvailable()) {
                    $providers[$name] = $provider;
                }
            } catch (\Exception $e) {
                // Skip providers that fail to instantiate or are not configured
                continue;
            }
        }

        return $providers;
    }

    /**
     * Check if a provider is available.
     *
     * @param string $name The provider name
     * @return bool
     */
    public function isProviderAvailable(string $name): bool
    {
        try {
            $provider = $this->create($name);

            return $provider->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get list of all supported provider names.
     *
     * @return array<string>
     */
    public function getSupportedProviders(): array
    {
        return array_keys(self::PROVIDER_MAP);
    }

    /**
     * Get list of all available (configured and ready) provider names.
     *
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->createAll());
    }
}
