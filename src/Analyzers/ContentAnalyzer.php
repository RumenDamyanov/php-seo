<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Analyzers;

use Rumenx\PhpSeo\Cache\SeoCache;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\AnalyzerInterface;

/**
 * Content analyzer for extracting SEO-relevant information from page content.
 *
 * This analyzer processes HTML and text content to extract headings, images,
 * keywords, and other elements useful for SEO generation.
 */
class ContentAnalyzer implements AnalyzerInterface
{
    private SeoConfig $config;
    private ?ImageAnalyzer $imageAnalyzer = null;
    private ?SeoCache $cache = null;

    public function __construct(
        SeoConfig $config,
        ?ImageAnalyzer $imageAnalyzer = null,
        ?SeoCache $cache = null
    ) {
        $this->config = $config;
        $this->imageAnalyzer = $imageAnalyzer;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(string $content, array $metadata = []): array
    {
        // Try to get from cache
        $cacheKey = null;
        if ($this->cache !== null && $this->cache->isEnabled()) {
            $cacheKey = $this->cache->keyGenerator()->forContentAnalysis($content, $metadata);
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null && is_array($cached)) {
                return $cached;
            }
        }

        // Perform analysis
        $data = [
            'content' => $content,
            'metadata' => $metadata,
            'word_count' => str_word_count(strip_tags($content)),
            'character_count' => strlen(strip_tags($content)),
            'language' => $metadata['language'] ?? $this->detectLanguage($content),
            'content_type' => $this->detectContentType($content),
        ];

        if ($this->config->get('analysis.extract_headings', true)) {
            $data['headings'] = $this->extractHeadings($content);
        }

        if ($this->config->get('analysis.extract_images', true)) {
            $data['images'] = $this->extractImages($content, $metadata);
        }

        if ($this->config->get('analysis.extract_links', true)) {
            $data['links'] = $this->extractLinks($content);
        }

        if ($this->config->get('analysis.extract_keywords', true)) {
            $data['keywords'] = $this->extractKeywords($content);
        }

        // Extract main content
        $data['main_content'] = $this->extractMainContent($content);
        $data['summary'] = $this->generateSummary($data['main_content']);

        // Cache the result
        if ($cacheKey !== null && $this->cache !== null && $this->cache->isEnabled()) {
            $this->cache->set($cacheKey, $data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $contentType): bool
    {
        return in_array($contentType, ['text/html', 'text/plain', 'markdown'], true);
    }

    /**
     * Extract heading tags from HTML content.
     *
     * @param string $content
     * @return array<array<string, mixed>>
     */
    private function extractHeadings(string $content): array
    {
        $headings = [];

        if (preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $headings[] = [
                    'level' => (int)$match[1],
                    'text' => trim(strip_tags($match[2])),
                    'html' => $match[0],
                ];
            }
        }

        return $headings;
    }

    /**
     * Extract image tags from HTML content.
     *
     * @param string $content
     * @param array<string, mixed> $metadata
     * @return array<array<string, mixed>>
     */
    private function extractImages(string $content, array $metadata = []): array
    {
        // Use ImageAnalyzer if available for comprehensive analysis
        if ($this->imageAnalyzer !== null) {
            $analysis = $this->imageAnalyzer->analyze($content, $metadata);

            return $analysis['images'] ?? [];
        }

        // Fallback to basic extraction
        $images = [];

        if (preg_match_all('/<img[^>]*>/is', $content, $matches)) {
            foreach ($matches[0] as $imgTag) {
                $image = ['html' => $imgTag];

                // Extract src
                if (preg_match('/src=["\']([^"\']*)["\']/', $imgTag, $srcMatch)) {
                    $image['src'] = $srcMatch[1];
                }

                // Extract alt
                if (preg_match('/alt=["\']([^"\']*)["\']/', $imgTag, $altMatch)) {
                    $image['alt'] = $altMatch[1];
                } else {
                    $image['alt'] = '';
                }

                // Extract title
                if (preg_match('/title=["\']([^"\']*)["\']/', $imgTag, $titleMatch)) {
                    $image['title'] = $titleMatch[1];
                } else {
                    $image['title'] = '';
                }

                $images[] = $image;
            }
        }

        return $images;
    }

    /**
     * Extract links from HTML content.
     *
     * @param string $content
     * @return array<array<string, mixed>>
     */
    private function extractLinks(string $content): array
    {
        $links = [];

        if (preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $links[] = [
                    'url' => $match[1],
                    'text' => trim(strip_tags($match[2])),
                    'html' => $match[0],
                    'is_external' => $this->isExternalLink($match[1]),
                ];
            }
        }

        return $links;
    }

    /**
     * Extract keywords from content.
     *
     * @param string $content
     * @return array<string>
     */
    private function extractKeywords(string $content): array
    {
        $text = strtolower(strip_tags($content));
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = array_filter(explode(' ', $text), fn ($word) => strlen($word) > 3);

        // Remove common stop words
        $stopWords = [
            'this', 'that', 'with', 'have', 'will', 'from', 'they', 'know',
            'want', 'been', 'good', 'much', 'some', 'time', 'very', 'when',
            'come', 'here', 'just', 'like', 'long', 'make', 'many', 'over',
            'such', 'take', 'than', 'them', 'well', 'were', 'what', 'your',
        ];

        $words = array_diff($words, $stopWords);
        $wordCount = array_count_values($words);
        arsort($wordCount);

        return array_keys(array_slice($wordCount, 0, 20, true));
    }

    /**
     * Extract main content from HTML.
     *
     * @param string $content
     * @return string
     */
    private function extractMainContent(string $content): string
    {
        // Remove script and style tags
        $content = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $content);
        $content = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $content);

        // Try to find main content areas
        if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $content, $match)) {
            return trim(strip_tags($match[1]));
        }

        if (preg_match('/<article[^>]*>(.*?)<\/article>/is', $content, $match)) {
            return trim(strip_tags($match[1]));
        }

        // Fallback to body content
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $content, $match)) {
            return trim(strip_tags($match[1]));
        }

        return trim(strip_tags($content));
    }

    /**
     * Generate a summary of the content.
     *
     * @param string $content
     * @return string
     */
    private function generateSummary(string $content): string
    {
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentences = array_map('trim', $sentences);
        $sentences = array_filter($sentences, fn ($sentence) => strlen($sentence) > 20);

        return implode('. ', array_slice($sentences, 0, 3)) . '.';
    }

    /**
     * Detect the content type.
     *
     * @param string $content
     * @return string
     */
    private function detectContentType(string $content): string
    {
        if (preg_match('/<html|<head|<body|<div|<p|<h[1-6]>/i', $content)) {
            return 'text/html';
        }

        if (preg_match('/^#\s+|^\*\s+|^\d+\.\s+/m', $content)) {
            return 'markdown';
        }

        return 'text/plain';
    }

    /**
     * Detect the language of the content.
     *
     * @param string $content
     * @return string
     */
    private function detectLanguage(string $content): string
    {
        // Simple language detection based on common words
        $text = strtolower(strip_tags($content));

        $englishWords = [
            'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out',
            'day', 'get', 'has', 'him', 'his', 'how', 'its', 'may', 'new', 'now', 'old', 'see', 'two', 'way', 'who',
            'boy', 'did', 'man', 'end', 'few', 'got', 'let', 'put', 'say', 'she', 'too', 'use',
        ];

        $matches = 0;
        foreach ($englishWords as $word) {
            if (str_contains($text, ' ' . $word . ' ')) {
                $matches++;
            }
        }

        return $matches > 5 ? 'en' : 'unknown';
    }

    /**
     * Check if a link is external.
     *
     * @param string $url
     * @return bool
     */
    private function isExternalLink(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }
}
