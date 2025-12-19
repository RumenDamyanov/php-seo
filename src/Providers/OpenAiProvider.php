<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Providers;

use Rumenx\PhpSeo\Exceptions\ProviderException;

/**
 * OpenAI provider for AI-powered SEO generation.
 *
 * This provider integrates with OpenAI's API to generate SEO content
 * using GPT models for titles, descriptions, and meta tags.
 */
class OpenAiProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    public function generate(string $prompt, array $options = []): string
    {
        if (!$this->isAvailable()) {
            throw new \RuntimeException('OpenAI provider is not properly configured. Missing API key.');
        }

        try {
            $data = $this->formatRequest($prompt, $options);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/chat/completions', $data, $headers);

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
        return 'openai';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedModels(): array
    {
        return [
            // Latest models (Dec 2024)
            'gpt-4o',           // Latest GPT-4 Omni (May 2024) - Best quality
            'gpt-4o-mini',      // Mini version (July 2024) - Best cost/performance
            'o1-preview',       // Reasoning model (Sept 2024) - Complex tasks
            'o1-mini',          // Fast reasoning (Sept 2024) - Quick reasoning
            // Legacy models (still supported)
            'gpt-4-turbo',      // Previous generation
            'gpt-4',            // Original GPT-4
            'gpt-3.5-turbo',    // Budget option
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
        return 'gpt-4o-mini'; // Best cost/performance ratio as of Dec 2024
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultBaseUrl(): string
    {
        return 'https://api.openai.com/v1';
    }

    /**
     * {@inheritdoc}
     */
    protected function buildHeaders(): array
    {
        return [
            'Authorization: Bearer ' . $this->apiKey,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function formatRequest(string $prompt, array $options): array
    {
        return [
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
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function parseResponse(array $response): string
    {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw ProviderException::apiError(
                $this->getName(),
                'Invalid response structure: missing content'
            );
        }

        return trim($response['choices'][0]['message']['content']);
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
            $data = $this->formatRequest($prompt, [
                'system_message' => 'You are an SEO expert. Generate concise, compelling page titles.',
                'max_tokens' => 100,
                'temperature' => 0.7,
            ]);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/chat/completions', $data, $headers);

            return $this->parseResponse($response);
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
            $data = $this->formatRequest($prompt, [
                'system_message' => 'You are an SEO expert. Generate compelling meta descriptions.',
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/chat/completions', $data, $headers);

            return $this->parseResponse($response);
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
            $data = $this->formatRequest($prompt, [
                'system_message' => 'You are an SEO expert. Generate relevant keywords.',
                'max_tokens' => 100,
                'temperature' => 0.5,
            ]);
            $headers = $this->buildHeaders();
            $response = $this->makeHttpRequest('/chat/completions', $data, $headers);

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
