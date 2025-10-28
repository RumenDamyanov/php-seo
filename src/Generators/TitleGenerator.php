<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Generators;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\GeneratorInterface;
use Rumenx\PhpSeo\Providers\ProviderRegistry;

/**
 * Title generator for creating SEO-optimized page titles.
 *
 * Generates titles using either manual patterns or AI-powered analysis
 * based on the configuration and page data.
 */
class TitleGenerator implements GeneratorInterface
{
    private SeoConfig $config;
    private ?ProviderRegistry $providerRegistry;

    public function __construct(SeoConfig $config, ?ProviderRegistry $providerRegistry = null)
    {
        $this->config = $config;
        $this->providerRegistry = $providerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $pageData): string
    {
        // Try to get title from page data first
        if (!empty($pageData['metadata']['title'])) {
            return $this->processTitle($pageData['metadata']['title'], $pageData);
        }

        // Extract title from headings
        if (!empty($pageData['headings'])) {
            $h1 = $this->findHeadingByLevel($pageData['headings'], 1);
            if ($h1 !== null) {
                return $this->processTitle($h1['text'], $pageData);
            }
        }

        // Use AI if enabled
        if ($this->config->isAiEnabled()) {
            return $this->generateWithAi($pageData);
        }

        // Generate from content summary
        if (!empty($pageData['summary'])) {
            $title = $this->extractTitleFromSummary($pageData['summary']);

            return $this->processTitle($title, $pageData);
        }

        // Fallback title
        return $this->processTitle('Untitled Page', $pageData);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCustom(mixed $customInput, array $pageData = []): string
    {
        if (!is_string($customInput)) {
            throw new \InvalidArgumentException('Custom title input must be a string');
        }

        return $this->processTitle($customInput, $pageData);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'title';
    }

    /**
     * Process and format the title according to configuration.
     *
     * @param string $title
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function processTitle(string $title, array $pageData): string
    {
        // Clean the title
        $title = trim(strip_tags($title));

        // Apply case formatting
        $title = $this->applyCase($title);

        // Apply pattern if configured
        $pattern = $this->config->get('title.pattern');
        if ($pattern) {
            $title = $this->applyPattern($pattern, $title, $pageData);
        }

        // Ensure title length constraints
        $maxLength = $this->config->get('title.max_length', 60);
        $minLength = $this->config->get('title.min_length', 10);

        if (strlen($title) > $maxLength) {
            $title = $this->truncateTitle($title, $maxLength);
        }

        if (strlen($title) < $minLength) {
            $title = $this->padTitle($title, $pageData);
        }

        return $title;
    }

    /**
     * Apply case formatting to the title.
     *
     * @param string $title
     * @return string
     */
    private function applyCase(string $title): string
    {
        $case = $this->config->get('title.case', 'title');

        return match ($case) {
            'title' => ucwords(strtolower($title)),
            'sentence' => ucfirst(strtolower($title)),
            'lower' => strtolower($title),
            'upper' => strtoupper($title),
            default => $title,
        };
    }

    /**
     * Apply title pattern with replacements.
     *
     * @param string $pattern
     * @param string $title
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function applyPattern(string $pattern, string $title, array $pageData): string
    {
        $replacements = [
            '{title}' => $title,
            '{site_name}' => $this->config->get('title.site_name', ''),
            '{separator}' => $this->config->get('title.separator', ' | '),
            '{page_type}' => $pageData['metadata']['type'] ?? '',
            '{category}' => $pageData['metadata']['category'] ?? '',
        ];

        $result = str_replace(array_keys($replacements), array_values($replacements), $pattern);

        // Clean up empty parts
        $separator = $this->config->get('title.separator', ' | ');
        $result = preg_replace('/\s*' . preg_quote($separator, '/') . '\s*' . preg_quote($separator, '/') . '\s*/', $separator, $result);
        $result = trim($result, $separator . ' ');

        return $result;
    }

    /**
     * Truncate title to fit max length while preserving words.
     *
     * @param string $title
     * @param int $maxLength
     * @return string
     */
    private function truncateTitle(string $title, int $maxLength): string
    {
        if (strlen($title) <= $maxLength) {
            return $title;
        }

        // Try to truncate at word boundaries
        $truncated = substr($title, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            return substr($truncated, 0, $lastSpace);
        }

        // Hard truncate with ellipsis
        return substr($title, 0, $maxLength - 3) . '...';
    }

    /**
     * Pad title if it's too short.
     *
     * @param string $title
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function padTitle(string $title, array $pageData): string
    {
        $siteName = $this->config->get('title.site_name', '');
        if ($siteName && !str_contains($title, $siteName)) {
            $separator = $this->config->get('title.separator', ' | ');

            return $title . $separator . $siteName;
        }

        return $title;
    }

    /**
     * Find heading by level.
     *
     * @param array<array<string, mixed>> $headings
     * @param int $level
     * @return array<string, mixed>|null
     */
    private function findHeadingByLevel(array $headings, int $level): ?array
    {
        foreach ($headings as $heading) {
            if ($heading['level'] === $level) {
                return $heading;
            }
        }

        return null;
    }

    /**
     * Extract title from content summary.
     *
     * @param string $summary
     * @return string
     */
    private function extractTitleFromSummary(string $summary): string
    {
        // Get the first sentence as potential title
        $sentences = preg_split('/[.!?]+/', $summary, -1, PREG_SPLIT_NO_EMPTY);
        if (!empty($sentences)) {
            $title = trim($sentences[0]);
            if (strlen($title) > 10 && strlen($title) < 80) {
                return $title;
            }
        }

        // Fallback to first few words
        $words = explode(' ', trim(strip_tags($summary)));

        return implode(' ', array_slice($words, 0, 8));
    }

    /**
     * Generate title using AI.
     *
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function generateWithAi(array $pageData): string
    {
        // Check if provider registry is available
        if ($this->providerRegistry === null || !$this->providerRegistry->hasAvailableProvider()) {
            // Fallback to manual generation
            return $this->generateFallbackTitle($pageData);
        }

        try {
            // Prepare analysis data for the provider
            $analysis = [
                'summary' => $pageData['summary'] ?? '',
                'main_content' => $pageData['main_content'] ?? '',
                'headings' => $pageData['headings'] ?? [],
                'keywords' => $pageData['keywords'] ?? [],
            ];

            // Use provider registry with fallback support
            $title = $this->providerRegistry->generateTitleWithFallback($analysis);

            return $this->processTitle($title, $pageData);
        } catch (\Exception $e) {
            // If AI generation fails, fallback to manual generation
            if ($this->config->get('ai.fallback_enabled', true)) {
                return $this->generateFallbackTitle($pageData);
            }

            throw $e;
        }
    }

    /**
     * Generate fallback title when AI is not available.
     *
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function generateFallbackTitle(array $pageData): string
    {
        $content = $pageData['main_content'] ?? $pageData['summary'] ?? '';
        $words = explode(' ', trim(strip_tags($content)));
        $title = implode(' ', array_slice($words, 0, 6));

        return $this->processTitle($title ?: 'Generated Title', $pageData);
    }
}
