<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\AI;

use Rumenx\PhpSeo\Config\SeoConfig;

/**
 * Response validator for AI-generated content.
 *
 * Validates and sanitizes responses from AI providers to ensure
 * they meet SEO requirements and quality standards.
 */
class ResponseValidator
{
    private SeoConfig $config;

    public function __construct(SeoConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Validate a generated title.
     *
     * @param string $title The generated title
     * @return array{valid: bool, error: string|null, sanitized: string}
     */
    public function validateTitle(string $title): array
    {
        $title = $this->sanitizeText($title);

        $minLength = $this->config->get('title.min_length', 10);
        $maxLength = $this->config->get('title.max_length', 60);

        // Check if empty
        if (empty($title)) {
            return [
                'valid' => false,
                'error' => 'Title is empty',
                'sanitized' => '',
            ];
        }

        // Check minimum length
        if (strlen($title) < $minLength) {
            return [
                'valid' => false,
                'error' => "Title too short (min: {$minLength} characters)",
                'sanitized' => $title,
            ];
        }

        // Check maximum length
        if (strlen($title) > $maxLength) {
            // Try to truncate at word boundary
            $title = $this->truncateAtWordBoundary($title, $maxLength);
        }

        // Check for suspicious patterns
        if ($this->hasSuspiciousPatterns($title)) {
            return [
                'valid' => false,
                'error' => 'Title contains suspicious patterns',
                'sanitized' => $title,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'sanitized' => $title,
        ];
    }

    /**
     * Validate a generated description.
     *
     * @param string $description The generated description
     * @return array{valid: bool, error: string|null, sanitized: string}
     */
    public function validateDescription(string $description): array
    {
        $description = $this->sanitizeText($description);

        $minLength = $this->config->get('description.min_length', 120);
        $maxLength = $this->config->get('description.max_length', 160);

        // Check if empty
        if (empty($description)) {
            return [
                'valid' => false,
                'error' => 'Description is empty',
                'sanitized' => '',
            ];
        }

        // Check minimum length
        if (strlen($description) < $minLength) {
            return [
                'valid' => false,
                'error' => "Description too short (min: {$minLength} characters)",
                'sanitized' => $description,
            ];
        }

        // Check maximum length
        if (strlen($description) > $maxLength) {
            // Try to truncate at sentence boundary
            $description = $this->truncateAtSentenceBoundary($description, $maxLength);
        }

        // Ensure it ends with proper punctuation
        if (!preg_match('/[.!?]$/', $description)) {
            $description .= '.';
        }

        // Check for suspicious patterns
        if ($this->hasSuspiciousPatterns($description)) {
            return [
                'valid' => false,
                'error' => 'Description contains suspicious patterns',
                'sanitized' => $description,
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'sanitized' => $description,
        ];
    }

    /**
     * Validate generated keywords.
     *
     * @param array<string>|string $keywords The generated keywords
     * @return array{valid: bool, error: string|null, sanitized: array<string>}
     */
    public function validateKeywords(array|string $keywords): array
    {
        // Convert string to array if needed
        if (is_string($keywords)) {
            $keywords = array_map('trim', explode(',', $keywords));
        }

        // Filter and sanitize
        $keywords = array_filter(array_map([$this, 'sanitizeText'], $keywords));

        $maxKeywords = $this->config->get('meta_tags.keywords_max', 10);

        // Check if empty
        if (empty($keywords)) {
            return [
                'valid' => false,
                'error' => 'No valid keywords generated',
                'sanitized' => [],
            ];
        }

        // Limit to max keywords
        if (count($keywords) > $maxKeywords) {
            $keywords = array_slice($keywords, 0, $maxKeywords);
        }

        // Remove duplicates (case-insensitive)
        $keywords = $this->removeDuplicateKeywords($keywords);

        // Filter out invalid keywords
        $keywords = array_filter($keywords, function ($keyword) {
            // Must be at least 2 characters
            if (strlen($keyword) < 2) {
                return false;
            }

            // Must not be too long
            if (strlen($keyword) > 50) {
                return false;
            }

            // Must contain at least one letter
            if (!preg_match('/[a-zA-Z]/', $keyword)) {
                return false;
            }

            return true;
        });

        if (empty($keywords)) {
            return [
                'valid' => false,
                'error' => 'No valid keywords after filtering',
                'sanitized' => [],
            ];
        }

        return [
            'valid' => true,
            'error' => null,
            'sanitized' => array_values($keywords),
        ];
    }

    /**
     * Sanitize text by removing unwanted characters.
     */
    private function sanitizeText(string $text): string
    {
        // Remove HTML tags
        $text = strip_tags($text);

        // Remove control characters except newlines and tabs
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        // Remove markdown-style formatting if present
        $text = preg_replace('/[*_]{1,2}([^*_]+)[*_]{1,2}/', '$1', $text);

        // Remove markdown links [text](url) -> text
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);

        // Remove quotes if the entire text is quoted
        if (preg_match('/^["\'](.+)["\']$/', $text, $matches)) {
            $text = $matches[1];
        }

        return $text;
    }

    /**
     * Check for suspicious patterns that indicate AI didn't follow instructions.
     */
    private function hasSuspiciousPatterns(string $text): bool
    {
        $suspiciousPatterns = [
            '/^(Here is|Here\'s|Sure|Certainly|Of course)/i',
            '/^Title:/i',
            '/^Description:/i',
            '/^Keywords:/i',
            '/\*\*.*\*\*/', // Markdown bold
            '/#{1,6}\s/', // Markdown headers
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Truncate text at word boundary.
     */
    private function truncateAtWordBoundary(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = substr($text, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            return substr($truncated, 0, $lastSpace);
        }

        return substr($text, 0, $maxLength - 3) . '...';
    }

    /**
     * Truncate text at sentence boundary.
     */
    private function truncateAtSentenceBoundary(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        // Try to find last sentence within limit
        $truncated = substr($text, 0, $maxLength);
        $lastPeriod = strrpos($truncated, '.');
        $lastExclamation = strrpos($truncated, '!');
        $lastQuestion = strrpos($truncated, '?');

        $lastSentence = max($lastPeriod, $lastExclamation, $lastQuestion);

        if ($lastSentence !== false && $lastSentence > $maxLength * 0.7) {
            return substr($truncated, 0, $lastSentence + 1);
        }

        // Fallback to word boundary
        return $this->truncateAtWordBoundary($text, $maxLength);
    }

    /**
     * Remove duplicate keywords (case-insensitive).
     *
     * @param array<string> $keywords
     * @return array<string>
     */
    private function removeDuplicateKeywords(array $keywords): array
    {
        $seen = [];
        $unique = [];

        foreach ($keywords as $keyword) {
            $lower = strtolower($keyword);
            if (!isset($seen[$lower])) {
                $seen[$lower] = true;
                $unique[] = $keyword;
            }
        }

        return $unique;
    }
}
