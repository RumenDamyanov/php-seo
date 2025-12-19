<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Providers;

use Rumenx\PhpSeo\Cache\SeoCache;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\ProviderInterface;
use Rumenx\PhpSeo\Exceptions\ProviderException;
use Rumenx\PhpSeo\RateLimiting\RateLimiter;

/**
 * Abstract base class for AI providers.
 *
 * Provides common functionality for all AI providers including
 * HTTP client logic, retry mechanisms, error handling, and response validation.
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected SeoConfig $config;
    protected ?string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;
    protected int $maxRetries;
    protected ?SeoCache $cache = null;
    protected ?RateLimiter $rateLimiter = null;

    /**
     * Create a new provider instance.
     *
     * @param SeoConfig $config
     * @param SeoCache|null $cache Optional cache instance
     * @param RateLimiter|null $rateLimiter Optional rate limiter instance
     */
    public function __construct(
        SeoConfig $config,
        ?SeoCache $cache = null,
        ?RateLimiter $rateLimiter = null
    ) {
        $this->config = $config;
        $this->cache = $cache;
        $this->rateLimiter = $rateLimiter;
        $this->apiKey = $this->getConfigValue('api_key');
        $this->model = $this->getConfigValue('model', $this->getDefaultModel());
        $this->baseUrl = $this->getConfigValue('base_url', $this->getDefaultBaseUrl());
        $this->timeout = $this->getConfigValue('timeout', 30);
        $this->maxRetries = $this->getConfigValue('max_retries', 3);
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfig(array $config): bool
    {
        return !empty($config['api_key'] ?? null);
    }

    /**
     * Make an HTTP request to the provider API with retry logic and rate limiting.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data The request data
     * @param array<int, string> $headers Additional headers in "Header: value" format
     * @return array<string, mixed> The response data
     * @throws ProviderException
     */
    protected function makeHttpRequest(string $endpoint, array $data, array $headers = []): array
    {
        // Check rate limit before making request
        if ($this->rateLimiter !== null) {
            $this->rateLimiter->acquire($this->getName());
        }

        $attempt = 0;
        $lastError = null;

        while ($attempt < $this->maxRetries) {
            try {
                return $this->executeRequest($endpoint, $data, $headers);
            } catch (\Exception $e) {
                $lastError = $e;
                $attempt++;

                if ($attempt < $this->maxRetries) {
                    // Exponential backoff: 1s, 2s, 4s, etc.
                    $backoffSeconds = pow(2, $attempt - 1);
                    sleep($backoffSeconds);
                }
            }
        }

        throw ProviderException::communicationError(
            $this->getName(),
            $lastError !== null ? $lastError->getMessage() : 'Unknown error after retries'
        );
    }

    /**
     * Execute a single HTTP request.
     *
     * @param string $endpoint The API endpoint
     * @param array<string, mixed> $data The request data
     * @param array<int, string> $headers Additional headers in "Header: value" format
     * @return array<string, mixed> The response data
     * @throws ProviderException
     */
    protected function executeRequest(string $endpoint, array $data, array $headers = []): array
    {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error !== '') {
            throw ProviderException::communicationError($this->getName(), "cURL error: {$error}");
        }

        if ($response === false) {
            throw ProviderException::communicationError($this->getName(), 'Failed to get response');
        }

        $decodedResponse = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw ProviderException::communicationError(
                $this->getName(),
                'Invalid JSON response: ' . json_last_error_msg()
            );
        }

        if ($httpCode >= 400) {
            $errorMessage = $this->extractErrorMessage($decodedResponse);

            throw ProviderException::apiError($this->getName(), $errorMessage, $httpCode);
        }

        return $decodedResponse;
    }

    /**
     * Extract error message from API response.
     *
     * @param array<string, mixed> $response
     * @return string
     */
    protected function extractErrorMessage(array $response): string
    {
        // Common error message paths across different providers
        return $response['error']['message']
            ?? $response['error']
            ?? $response['message']
            ?? 'Unknown API error';
    }

    /**
     * Get a configuration value with optional default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfigValue(string $key, $default = null)
    {
        return $this->config->get("ai.{$key}", $default);
    }

    /**
     * Execute a generation with caching support.
     *
     * @param string $prompt The prompt to generate from
     * @param array<string, mixed> $options Generation options
     * @param callable $generator The generation callback
     * @return string The generated content
     */
    protected function generateWithCache(string $prompt, array $options, callable $generator): string
    {
        if ($this->cache === null || !$this->cache->isEnabled()) {
            return $generator();
        }

        $cacheKey = $this->cache->keyGenerator()->forProviderResponse(
            $this->getName(),
            $this->model,
            $prompt,
            $options
        );

        return $this->cache->remember($cacheKey, $generator);
    }

    /**
     * Get the default model for this provider.
     *
     * @return string
     */
    abstract protected function getDefaultModel(): string;

    /**
     * Get the default base URL for this provider.
     *
     * @return string
     */
    abstract protected function getDefaultBaseUrl(): string;

    /**
     * Build the request headers for this provider.
     *
     * @return array<int, string> Array of header strings in "Header: value" format
     */
    abstract protected function buildHeaders(): array;

    /**
     * Format the request data for this provider's API.
     *
     * @param string $prompt
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    abstract protected function formatRequest(string $prompt, array $options): array;

    /**
     * Parse the response from this provider's API.
     *
     * @param array<string, mixed> $response
     * @return string
     * @throws ProviderException
     */
    abstract protected function parseResponse(array $response): string;
}
