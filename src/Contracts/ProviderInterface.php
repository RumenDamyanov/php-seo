<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Contracts;

/**
 * Interface for AI providers.
 *
 * Providers handle communication with different AI services
 * for generating SEO content automatically.
 */
interface ProviderInterface
{
    /**
     * Generate content using the AI provider.
     *
     * @param string $prompt The prompt to send to the AI
     * @param array<string, mixed> $options Additional options for the request
     * @return string The generated content
     * @throws \Rumenx\PhpSeo\Exceptions\ProviderException
     */
    public function generate(string $prompt, array $options = []): string;

    /**
     * Check if the provider is available and configured.
     *
     * @return bool
     */
    public function isAvailable(): bool;

    /**
     * Get the provider name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the supported models for this provider.
     *
     * @return array<string>
     */
    public function getSupportedModels(): array;

    /**
     * Validate the provider configuration.
     *
     * @param array<string, mixed> $config
     * @return bool
     */
    public function validateConfig(array $config): bool;
}
