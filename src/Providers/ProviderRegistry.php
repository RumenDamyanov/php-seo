<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Providers;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\ProviderInterface;
use Rumenx\PhpSeo\Exceptions\ProviderException;

/**
 * Registry for managing multiple AI providers with fallback support.
 *
 * This class maintains a collection of providers and implements fallback logic
 * to ensure content generation succeeds even if the primary provider fails.
 */
class ProviderRegistry
{
    private ProviderFactory $factory;
    private SeoConfig $config;

    /**
     * @var array<string, ProviderInterface>
     */
    private array $providers = [];

    /**
     * @var array<string>
     */
    private array $fallbackChain = [];

    public function __construct(ProviderFactory $factory, SeoConfig $config)
    {
        $this->factory = $factory;
        $this->config = $config;
        $this->initializeFallbackChain();
    }

    /**
     * Initialize the fallback chain from configuration.
     */
    private function initializeFallbackChain(): void
    {
        // Get fallback chain from config, default to ['openai']
        $this->fallbackChain = $this->config->get('ai.fallback_chain', ['openai']);
    }

    /**
     * Register a provider instance.
     *
     * @param string $name The provider name
     * @param ProviderInterface $provider The provider instance
     */
    public function register(string $name, ProviderInterface $provider): void
    {
        $this->providers[strtolower($name)] = $provider;
    }

    /**
     * Get a provider by name, creating it if necessary.
     *
     * @param string $name The provider name
     * @return ProviderInterface
     * @throws ProviderException
     */
    public function get(string $name): ProviderInterface
    {
        $name = strtolower($name);

        if (!isset($this->providers[$name])) {
            $this->providers[$name] = $this->factory->create($name);
        }

        return $this->providers[$name];
    }

    /**
     * Get the primary (default) provider.
     *
     * @return ProviderInterface
     * @throws ProviderException
     */
    public function getPrimary(): ProviderInterface
    {
        $primaryName = $this->config->get('ai.default_provider', 'openai');

        return $this->get($primaryName);
    }

    /**
     * Get all available providers in the fallback chain.
     *
     * @return array<ProviderInterface>
     */
    public function getFallbackChain(): array
    {
        $providers = [];

        foreach ($this->fallbackChain as $name) {
            try {
                $provider = $this->get($name);
                if ($provider->isAvailable()) {
                    $providers[] = $provider;
                }
            } catch (\Exception $e) {
                // Skip unavailable providers
                continue;
            }
        }

        return $providers;
    }

    /**
     * Generate content using providers with fallback support.
     *
     * Tries each provider in the fallback chain until one succeeds.
     *
     * @param string $prompt The generation prompt
     * @param array<string, mixed> $options Generation options
     * @return string The generated content
     * @throws \RuntimeException If all providers fail
     */
    public function generateWithFallback(string $prompt, array $options = []): string
    {
        $providers = $this->getFallbackChain();
        $errors = [];

        foreach ($providers as $provider) {
            try {
                return $provider->generate($prompt, $options);
            } catch (\Exception $e) {
                $errors[$provider->getName()] = $e->getMessage();

                continue;
            }
        }

        // All providers failed
        $errorMessage = "All providers failed:\n";
        foreach ($errors as $providerName => $error) {
            $errorMessage .= "- {$providerName}: {$error}\n";
        }

        throw new \RuntimeException($errorMessage);
    }

    /**
     * Generate a title using providers with fallback support.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Generation options
     * @return string The generated title
     * @throws \RuntimeException If all providers fail
     */
    public function generateTitleWithFallback(array $analysis, array $options = []): string
    {
        $providers = $this->getFallbackChain();
        $errors = [];

        foreach ($providers as $provider) {
            try {
                return $provider->generateTitle($analysis, $options);
            } catch (\Exception $e) {
                $errors[$provider->getName()] = $e->getMessage();

                continue;
            }
        }

        throw new \RuntimeException("All providers failed to generate title");
    }

    /**
     * Generate a description using providers with fallback support.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Generation options
     * @return string The generated description
     * @throws \RuntimeException If all providers fail
     */
    public function generateDescriptionWithFallback(array $analysis, array $options = []): string
    {
        $providers = $this->getFallbackChain();
        $errors = [];

        foreach ($providers as $provider) {
            try {
                return $provider->generateDescription($analysis, $options);
            } catch (\Exception $e) {
                $errors[$provider->getName()] = $e->getMessage();

                continue;
            }
        }

        throw new \RuntimeException("All providers failed to generate description");
    }

    /**
     * Generate keywords using providers with fallback support.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Generation options
     * @return array<string> The generated keywords
     * @throws \RuntimeException If all providers fail
     */
    public function generateKeywordsWithFallback(array $analysis, array $options = []): array
    {
        $providers = $this->getFallbackChain();
        $errors = [];

        foreach ($providers as $provider) {
            try {
                return $provider->generateKeywords($analysis, $options);
            } catch (\Exception $e) {
                $errors[$provider->getName()] = $e->getMessage();

                continue;
            }
        }

        throw new \RuntimeException("All providers failed to generate keywords");
    }

    /**
     * Set the fallback chain order.
     *
     * @param array<string> $chain Array of provider names in order of preference
     */
    public function setFallbackChain(array $chain): void
    {
        $this->fallbackChain = array_map('strtolower', $chain);
    }

    /**
     * Get the current fallback chain.
     *
     * @return array<string>
     */
    public function getFallbackChainNames(): array
    {
        return $this->fallbackChain;
    }

    /**
     * Check if any provider in the fallback chain is available.
     *
     * @return bool
     */
    public function hasAvailableProvider(): bool
    {
        return count($this->getFallbackChain()) > 0;
    }
}
