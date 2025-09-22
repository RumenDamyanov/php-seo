<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Providers;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\ProviderInterface;

/**
 * OpenAI provider for AI-powered SEO generation.
 *
 * This provider integrates with OpenAI's API to generate SEO content
 * using GPT models for titles, descriptions, and meta tags.
 */
class OpenAiProvider implements ProviderInterface
{
    private SeoConfig $config;
    private ?string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(SeoConfig $config)
    {
        $this->config = $config;
        $this->apiKey = $config->get('providers.openai.api_key');
        $this->model = $config->get('providers.openai.model', 'gpt-3.5-turbo');
        $this->baseUrl = $config->get('providers.openai.base_url', 'https://api.openai.com/v1');
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('OpenAI provider is not properly configured. Missing API key.');
        }

        try {
            $response = $this->makeRequest([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $options['system_message'] ?? 'You are an SEO expert. Generate high-quality, optimized content.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => $options['max_tokens'] ?? 150,
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

            return $this->extractTextFromResponse($response);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to generate content: {$e->getMessage()}", 0, $e);
        }
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
    public function getName(): string
    {
        return 'openai';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedModels(): array
    {
        return [
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
            'gpt-4o',
            'gpt-4o-mini',
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
    public function generateTitle(array $analysis, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('OpenAI provider is not properly configured. Missing API key.');
        }

        $prompt = $this->buildTitlePrompt($analysis, $options);

        try {
            $response = $this->makeRequest([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO expert. Generate concise, compelling page titles that are optimized for search engines. ' .
                                   'Follow best practices: keep titles under 60 characters, include relevant keywords, and make them engaging for users.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 100,
                'temperature' => 0.7,
            ]);

            return $this->extractTextFromResponse($response);
        } catch (\Exception $e) {
            if ($this->config->get('ai.fallback_enabled', true)) {
                return $this->generateFallbackTitle($analysis);
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateDescription(array $analysis, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('OpenAI provider is not properly configured. Missing API key.');
        }

        $prompt = $this->buildDescriptionPrompt($analysis, $options);

        try {
            $response = $this->makeRequest([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO expert. Generate compelling meta descriptions that are optimized for search engines. ' .
                                   'Follow best practices: keep descriptions between 150-160 characters, include relevant keywords naturally, ' .
                                   'and create engaging copy that encourages clicks.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);

            return $this->extractTextFromResponse($response);
        } catch (\Exception $e) {
            if ($this->config->get('ai.fallback_enabled', true)) {
                return $this->generateFallbackDescription($analysis);
            }

            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function generateKeywords(array $analysis, array $options = []): array
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('OpenAI provider is not properly configured. Missing API key.');
        }

        $prompt = $this->buildKeywordsPrompt($analysis, $options);

        try {
            $response = $this->makeRequest([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO expert. Generate relevant keywords for the given content. Return keywords as a comma-separated list. ' .
                                   'Focus on terms that users would search for to find this content.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => 100,
                'temperature' => 0.5,
            ]);

            $keywordsText = $this->extractTextFromResponse($response);

            return array_map('trim', explode(',', $keywordsText));
        } catch (\Exception $e) {
            if ($this->config->get('ai.fallback_enabled', true)) {
                return $analysis['keywords'] ?? [];
            }

            throw $e;
        }
    }

    /**
     * Build the prompt for title generation.
     *
     * @param array<string, mixed> $analysis
     * @param array<string, mixed> $options
     * @return string
     */
    private function buildTitlePrompt(array $analysis, array $options): string
    {
        $prompt = "Generate an SEO-optimized title for this content:\n\n";

        if (!empty($analysis['summary'])) {
            $prompt .= "Content Summary: {$analysis['summary']}\n";
        }

        if (!empty($analysis['headings'])) {
            $headings = array_slice($analysis['headings'], 0, 3);
            $headingTexts = array_map(fn ($h) => $h['text'], $headings);
            $prompt .= "Main Headings: " . implode(', ', $headingTexts) . "\n";
        }

        if (!empty($analysis['keywords'])) {
            $keywords = array_slice($analysis['keywords'], 0, 5);
            $prompt .= "Key Terms: " . implode(', ', $keywords) . "\n";
        }

        $maxLength = $options['max_length'] ?? $this->config->get('generation.title.max_length', 60);
        $prompt .= "\nGenerate a title that is compelling, descriptive, and under {$maxLength} characters.";

        return $prompt;
    }

    /**
     * Build the prompt for description generation.
     *
     * @param array<string, mixed> $analysis
     * @param array<string, mixed> $options
     * @return string
     */
    private function buildDescriptionPrompt(array $analysis, array $options): string
    {
        $prompt = "Generate an SEO-optimized meta description for this content:\n\n";

        if (!empty($analysis['summary'])) {
            $prompt .= "Content Summary: {$analysis['summary']}\n";
        }

        if (!empty($analysis['main_content'])) {
            $content = substr($analysis['main_content'], 0, 500);
            $prompt .= "Content Preview: {$content}...\n";
        }

        if (!empty($analysis['keywords'])) {
            $keywords = array_slice($analysis['keywords'], 0, 5);
            $prompt .= "Key Terms: " . implode(', ', $keywords) . "\n";
        }

        $maxLength = $options['max_length'] ?? $this->config->get('generation.description.max_length', 160);
        $prompt .= "\nGenerate a meta description that is engaging, informative, and between 150-{$maxLength} characters.";

        return $prompt;
    }

    /**
     * Build the prompt for keywords generation.
     *
     * @param array<string, mixed> $analysis
     * @param array<string, mixed> $options
     * @return string
     */
    private function buildKeywordsPrompt(array $analysis, array $options): string
    {
        $prompt = "Generate relevant SEO keywords for this content:\n\n";

        if (!empty($analysis['summary'])) {
            $prompt .= "Content Summary: {$analysis['summary']}\n";
        }

        if (!empty($analysis['main_content'])) {
            $content = substr($analysis['main_content'], 0, 300);
            $prompt .= "Content Preview: {$content}...\n";
        }

        $maxKeywords = $options['max_keywords'] ?? $this->config->get('generation.keywords.max_count', 10);
        $prompt .= "\nGenerate up to {$maxKeywords} relevant keywords as a comma-separated list.";

        return $prompt;
    }

    /**
     * Make a request to the OpenAI API.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws \RuntimeException
     */
    private function makeRequest(array $data): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . '/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => $this->config->get('providers.openai.timeout', 30),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error !== '') {
            throw new \RuntimeException("cURL error: {$error}");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP error {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from OpenAI API');
        }

        if (isset($decoded['error'])) {
            throw new \RuntimeException("OpenAI API error: {$decoded['error']['message']}");
        }

        return $decoded;
    }

    /**
     * Extract text from OpenAI API response.
     *
     * @param array<string, mixed> $response
     * @return string
     */
    private function extractTextFromResponse(array $response): string
    {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Invalid response format from OpenAI API');
        }

        return trim($response['choices'][0]['message']['content']);
    }

    /**
     * Generate a fallback title when AI fails.
     *
     * @param array<string, mixed> $analysis
     * @return string
     */
    private function generateFallbackTitle(array $analysis): string
    {
        if (!empty($analysis['headings'])) {
            return $analysis['headings'][0]['text'];
        }

        if (!empty($analysis['summary'])) {
            return substr($analysis['summary'], 0, 60);
        }

        return 'Untitled Page';
    }

    /**
     * Generate a fallback description when AI fails.
     *
     * @param array<string, mixed> $analysis
     * @return string
     */
    private function generateFallbackDescription(array $analysis): string
    {
        if (!empty($analysis['summary'])) {
            return substr($analysis['summary'], 0, 160);
        }

        if (!empty($analysis['main_content'])) {
            return substr($analysis['main_content'], 0, 160);
        }

        return 'No description available.';
    }
}
