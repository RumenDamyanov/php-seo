<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Providers;

use Rumenx\PhpSeo\Exceptions\ProviderException;

/**
 * Anthropic (Claude) provider for AI-powered SEO generation.
 *
 * This provider integrates with Anthropic's Claude API to generate SEO content
 * using Claude models for titles, descriptions, and meta tags.
 */
class AnthropicProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    public function generate(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Anthropic provider is not properly configured. Missing API key.');
        }

        try {
            $data = $this->formatRequest($prompt, $options);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/messages', $data, $headers);

            return $this->parseResponse($response);
        } catch (ProviderException $e) {
            throw new \RuntimeException("Failed to generate content: {$e->getMessage()}", 0, $e);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to generate content: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'anthropic';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedModels(): array
    {
        return [
            // Latest models (Dec 2024)
            'claude-3-7-sonnet-20250219',  // LATEST Claude 3.7 (Dec 2024) - Best overall
            'claude-3-5-sonnet-20241022',  // Claude 3.5 Sonnet (Oct 2024) - Very capable
            'claude-3-5-haiku-20241022',   // Claude 3.5 Haiku (Oct 2024) - Fast & efficient
            // Legacy models (still supported)
            'claude-3-opus-20240229',      // Claude 3 Opus (Feb 2024) - Most capable 3.0
            'claude-3-sonnet-20240229',    // Claude 3 Sonnet (Feb 2024)
            'claude-3-haiku-20240307',     // Claude 3 Haiku (Mar 2024) - Fastest 3.0
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfig(array $config): bool
    {
        if (!isset($config['api_key']) || empty($config['api_key'])) {
            return false;
        }

        if (isset($config['model']) && !in_array($config['model'], $this->getSupportedModels(), true)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultModel(): string
    {
        return 'claude-3-7-sonnet-20250219'; // Latest Claude 3.7 as of Dec 2024
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBaseUrl(): string
    {
        return 'https://api.anthropic.com/v1';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildHeaders(): array
    {
        return [
            'x-api-key: ' . $this->apiKey,
            'anthropic-version: 2023-06-01',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function formatRequest(string $prompt, array $options): array
    {
        return [
            'model' => $this->model,
            'max_tokens' => $options['max_tokens'] ?? 1024,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'system' => $options['system_message'] ?? 'You are an SEO expert. Generate high-quality, optimized content.',
            'temperature' => $options['temperature'] ?? 0.7,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function parseResponse(array $response): string
    {
        if (!isset($response['content'][0]['text'])) {
            throw ProviderException::apiError(
                $this->getName(),
                'Invalid response structure: missing content text'
            );
        }

        return trim($response['content'][0]['text']);
    }

    /**
     * Generate an SEO-optimized title using AI.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Additional options
     * @return string The generated title
     * @throws \RuntimeException
     */
    public function generateTitle(array $analysis, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Anthropic provider is not properly configured. Missing API key.');
        }

        $prompt = $this->buildTitlePrompt($analysis, $options);

        try {
            $data = $this->formatRequest($prompt, [
                'system_message' => 'You are an SEO expert. Generate concise, compelling page titles '
                    . 'that are optimized for search engines and user engagement.',
                'max_tokens' => 100,
                'temperature' => 0.7,
            ]);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/messages', $data, $headers);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            if ($this->config->get('ai.fallback_enabled', true)) {
                return $this->generateFallbackTitle($analysis);
            }

            throw $e;
        }
    }

    /**
     * Generate an SEO-optimized description using AI.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Additional options
     * @return string The generated description
     * @throws \RuntimeException
     */
    public function generateDescription(array $analysis, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Anthropic provider is not properly configured. Missing API key.');
        }

        $prompt = $this->buildDescriptionPrompt($analysis, $options);

        try {
            $data = $this->formatRequest($prompt, [
                'system_message' => 'You are an SEO expert. Generate compelling meta descriptions '
                    . 'that drive clicks and improve search visibility.',
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/messages', $data, $headers);

            return $this->parseResponse($response);
        } catch (\Exception $e) {
            if ($this->config->get('ai.fallback_enabled', true)) {
                return $this->generateFallbackDescription($analysis);
            }

            throw $e;
        }
    }

    /**
     * Generate SEO keywords using AI.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Additional options
     * @return array<string> The generated keywords
     * @throws \RuntimeException
     */
    public function generateKeywords(array $analysis, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('Anthropic provider is not properly configured. Missing API key.');
        }

        $prompt = $this->buildKeywordsPrompt($analysis, $options);

        try {
            $data = $this->formatRequest($prompt, [
                'system_message' => 'You are an SEO expert. Generate relevant, high-impact keywords.',
                'max_tokens' => 100,
                'temperature' => 0.5,
            ]);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/messages', $data, $headers);

            $content = $this->parseResponse($response);
            $keywords = array_map('trim', explode(',', $content));

            return array_filter($keywords);
        } catch (\Exception $e) {
            if ($this->config->get('ai.fallback_enabled', true)) {
                return $analysis['keywords'] ?? [];
            }

            throw $e;
        }
    }

    /**
     * Build the prompt for title generation.
     */
    private function buildTitlePrompt(array $analysis, array $options): string
    {
        $prompt = "Generate an SEO-optimized page title for this content:\n\n";

        if (!empty($analysis['summary'])) {
            $prompt .= "Summary: {$analysis['summary']}\n";
        }

        if (!empty($analysis['main_content'])) {
            $prompt .= "Content: " . substr($analysis['main_content'], 0, 300) . "...\n";
        }

        $maxLength = $options['max_length'] ?? $this->config->get('title.max_length', 60);
        $prompt .= "\nKeep it under {$maxLength} characters. Return only the title, nothing else.";

        return $prompt;
    }

    /**
     * Build the prompt for description generation.
     */
    private function buildDescriptionPrompt(array $analysis, array $options): string
    {
        $prompt = "Generate an SEO-optimized meta description:\n\n";

        if (!empty($analysis['summary'])) {
            $prompt .= "Summary: {$analysis['summary']}\n";
        }

        if (!empty($analysis['main_content'])) {
            $prompt .= "Content: " . substr($analysis['main_content'], 0, 300) . "...\n";
        }

        $maxLength = $options['max_length'] ?? $this->config->get('description.max_length', 160);
        $prompt .= "\nBetween 120-{$maxLength} characters. Return only the description, nothing else.";

        return $prompt;
    }

    /**
     * Build the prompt for keywords generation.
     */
    private function buildKeywordsPrompt(array $analysis, array $options): string
    {
        $prompt = "Generate SEO keywords as comma-separated list:\n\n";

        if (!empty($analysis['summary'])) {
            $prompt .= "Summary: {$analysis['summary']}\n";
        }

        $maxKeywords = $options['max_keywords'] ?? $this->config->get('meta_tags.keywords_max', 10);
        $prompt .= "\nMax {$maxKeywords} keywords. Return only the comma-separated list, nothing else.";

        return $prompt;
    }

    /**
     * Generate fallback title.
     */
    private function generateFallbackTitle(array $analysis): string
    {
        if (!empty($analysis['headings']['h1'][0])) {
            return $analysis['headings']['h1'][0];
        }

        if (!empty($analysis['summary'])) {
            $words = explode(' ', $analysis['summary']);

            return implode(' ', array_slice($words, 0, 10));
        }

        return 'Page Title';
    }

    /**
     * Generate fallback description.
     */
    private function generateFallbackDescription(array $analysis): string
    {
        if (!empty($analysis['summary'])) {
            return substr($analysis['summary'], 0, 160);
        }

        if (!empty($analysis['main_content'])) {
            return substr(strip_tags($analysis['main_content']), 0, 160);
        }

        return 'Page description';
    }
}
