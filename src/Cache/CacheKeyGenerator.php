<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Cache;

/**
 * Cache key generator for creating consistent cache keys.
 *
 * Generates cache keys based on content hash and context to ensure
 * proper cache invalidation when content or configuration changes.
 */
class CacheKeyGenerator
{
    /**
     * Generate a cache key for content analysis.
     *
     * @param string $content The content to analyze
     * @param array<string, mixed> $metadata Additional metadata
     * @return string The generated cache key
     */
    public function forContentAnalysis(string $content, array $metadata = []): string
    {
        $hash = $this->hashContent($content);
        $metadataHash = $this->hashMetadata($metadata);

        return sprintf('seo:analysis:%s:%s', $hash, $metadataHash);
    }

    /**
     * Generate a cache key for title generation.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Generation options
     * @return string The generated cache key
     */
    public function forTitleGeneration(array $analysis, array $options = []): string
    {
        $analysisHash = $this->hashData($analysis);
        $optionsHash = $this->hashData($options);

        return sprintf('seo:title:%s:%s', $analysisHash, $optionsHash);
    }

    /**
     * Generate a cache key for description generation.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Generation options
     * @return string The generated cache key
     */
    public function forDescriptionGeneration(array $analysis, array $options = []): string
    {
        $analysisHash = $this->hashData($analysis);
        $optionsHash = $this->hashData($options);

        return sprintf('seo:description:%s:%s', $analysisHash, $optionsHash);
    }

    /**
     * Generate a cache key for keywords generation.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Generation options
     * @return string The generated cache key
     */
    public function forKeywordsGeneration(array $analysis, array $options = []): string
    {
        $analysisHash = $this->hashData($analysis);
        $optionsHash = $this->hashData($options);

        return sprintf('seo:keywords:%s:%s', $analysisHash, $optionsHash);
    }

    /**
     * Generate a cache key for meta tags generation.
     *
     * @param array<string, mixed> $pageData Page data
     * @param array<string, mixed> $overrides Override values
     * @return string The generated cache key
     */
    public function forMetaTagsGeneration(array $pageData, array $overrides = []): string
    {
        $pageDataHash = $this->hashData($pageData);
        $overridesHash = $this->hashData($overrides);

        return sprintf('seo:metatags:%s:%s', $pageDataHash, $overridesHash);
    }

    /**
     * Generate a cache key for image alt text generation.
     *
     * @param array<string, mixed> $image Image data
     * @param array<string, mixed> $pageData Page context data
     * @return string The generated cache key
     */
    public function forImageAltGeneration(array $image, array $pageData = []): string
    {
        $imageHash = $this->hashData($image);
        $contextHash = $this->hashData($pageData);

        return sprintf('seo:imagealt:%s:%s', $imageHash, $contextHash);
    }

    /**
     * Generate a cache key for AI provider response.
     *
     * @param string $provider Provider name
     * @param string $model Model name
     * @param string $prompt The prompt
     * @param array<string, mixed> $options Additional options
     * @return string The generated cache key
     */
    public function forProviderResponse(
        string $provider,
        string $model,
        string $prompt,
        array $options = []
    ): string {
        $promptHash = $this->hashContent($prompt);
        $optionsHash = $this->hashData($options);

        return sprintf('seo:provider:%s:%s:%s:%s', $provider, $model, $promptHash, $optionsHash);
    }

    /**
     * Hash content using SHA-256 (first 12 chars for brevity).
     *
     * @param string $content
     * @return string
     */
    private function hashContent(string $content): string
    {
        return substr(hash('sha256', $content), 0, 12);
    }

    /**
     * Hash metadata or data array.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function hashData(array $data): string
    {
        if (empty($data)) {
            return 'empty';
        }

        ksort($data);
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        return substr(hash('sha256', $json), 0, 12);
    }

    /**
     * Hash metadata.
     *
     * @param array<string, mixed> $metadata
     * @return string
     */
    private function hashMetadata(array $metadata): string
    {
        return $this->hashData($metadata);
    }
}
