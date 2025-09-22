<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Config;

/**
 * Main configuration class for the PHP SEO package.
 *
 * This class holds all configuration options for SEO generation,
 * AI providers, and framework-specific settings.
 */
class SeoConfig
{
    /**
     * @var array<string, mixed>
     */
    private array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
    }

    /**
     * Get the default configuration.
     *
     * @return array<string, mixed>
     */
    private function getDefaultConfig(): array
    {
        return [
            // Global settings
            'enabled' => true,
            'mode' => 'manual', // 'ai', 'manual', 'hybrid'
            'cache_enabled' => true,
            'cache_ttl' => 3600, // 1 hour

            // AI Provider settings
            'ai' => [
                'provider' => 'openai', // 'openai', 'anthropic', 'google', 'xai', 'ollama'
                'model' => 'gpt-4-turbo-preview',
                'api_key' => null,
                'api_url' => null,
                'timeout' => 30,
                'max_retries' => 3,
                'fallback_providers' => [],
                'cost_tracking' => false,
                'rate_limiting' => [
                    'enabled' => true,
                    'requests_per_minute' => 10,
                ],
            ],

            // Title generation settings
            'title' => [
                'max_length' => 60,
                'min_length' => 10,
                'pattern' => '{title} | {site_name}',
                'site_name' => '',
                'separator' => ' | ',
                'case' => 'title', // 'title', 'sentence', 'lower', 'upper'
                'ai_prompt' => 'Generate an SEO-optimized title for this content. Keep it under 60 characters and make it compelling for search results.',
            ],

            // Description generation settings
            'description' => [
                'max_length' => 160,
                'min_length' => 120,
                'pattern' => null,
                'ai_prompt' => 'Generate an SEO-optimized meta description for this content. Keep it between 120-160 characters and make it compelling for search results.',
            ],

            // Meta tags settings
            'meta_tags' => [
                'default_tags' => [
                    'viewport' => 'width=device-width, initial-scale=1',
                    'charset' => 'utf-8',
                ],
                'open_graph' => [
                    'enabled' => true,
                    'site_name' => '',
                    'type' => 'website',
                    'locale' => 'en_US',
                ],
                'twitter' => [
                    'enabled' => true,
                    'card' => 'summary_large_image',
                    'site' => '',
                    'creator' => '',
                ],
                'robots' => [
                    'index' => true,
                    'follow' => true,
                    'archive' => true,
                    'snippet' => true,
                    'imageindex' => true,
                ],
            ],

            // Image optimization settings
            'images' => [
                'alt_text' => [
                    'enabled' => true,
                    'ai_vision' => false,
                    'pattern' => null,
                    'ai_prompt' => 'Generate descriptive alt text for this image based on its context.',
                ],
                'title_text' => [
                    'enabled' => true,
                    'pattern' => null,
                ],
            ],

            // Content analysis settings
            'analysis' => [
                'extract_headings' => true,
                'extract_images' => true,
                'extract_links' => true,
                'extract_keywords' => true,
                'min_content_length' => 100,
                'language_detection' => true,
            ],

            // Framework-specific settings
            'laravel' => [
                'middleware' => [
                    'enabled' => false,
                    'routes' => ['web'],
                ],
                'blade_directives' => true,
                'config_cache' => true,
            ],

            'symfony' => [
                'twig_extensions' => true,
                'event_listeners' => true,
                'cache_pool' => 'cache.app',
            ],

            // Logging settings
            'logging' => [
                'enabled' => false,
                'level' => 'info',
                'channels' => ['php-seo'],
            ],

            // Performance settings
            'performance' => [
                'lazy_loading' => true,
                'compression' => true,
                'minify_output' => false,
            ],
        ];
    }

    /**
     * Get a configuration value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key
     * @param mixed $value
     * @return self
     */
    public function set(string $key, mixed $value): self
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $segment) {
            if (!isset($config[$segment]) || !is_array($config[$segment])) {
                $config[$segment] = [];
            }
            $config = &$config[$segment];
        }

        $config = $value;

        return $this;
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }
            $value = $value[$segment];
        }

        return true;
    }

    /**
     * Get all configuration.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Merge configuration with existing values.
     *
     * @param array<string, mixed> $config
     * @return self
     */
    public function merge(array $config): self
    {
        $this->config = array_merge_recursive($this->config, $config);

        return $this;
    }

    /**
     * Check if AI mode is enabled.
     *
     * @return bool
     */
    public function isAiEnabled(): bool
    {
        $mode = $this->get('mode', 'manual');

        return in_array($mode, ['ai', 'hybrid'], true) && $this->get('enabled', true);
    }

    /**
     * Check if manual mode is enabled.
     *
     * @return bool
     */
    public function isManualEnabled(): bool
    {
        $mode = $this->get('mode', 'manual');

        return in_array($mode, ['manual', 'hybrid'], true) && $this->get('enabled', true);
    }

    /**
     * Get the current AI provider configuration.
     *
     * @return array<string, mixed>
     */
    public function getAiConfig(): array
    {
        return $this->get('ai', []);
    }

    /**
     * Create a new instance with merged configuration.
     *
     * @param array<string, mixed> $config
     * @return self
     */
    public function with(array $config): self
    {
        return new self(array_merge($this->config, $config));
    }
}
