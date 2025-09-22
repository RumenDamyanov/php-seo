<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Generators;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\GeneratorInterface;

/**
 * Description generator for creating SEO-optimized meta descriptions.
 *
 * Generates descriptions using either content analysis or AI-powered
 * generation based on the configuration and page data.
 */
class DescriptionGenerator implements GeneratorInterface
{
    private SeoConfig $config;

    public function __construct(SeoConfig $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $pageData): string
    {
        // Try to get description from metadata first
        if (!empty($pageData['metadata']['description'])) {
            return $this->processDescription($pageData['metadata']['description']);
        }

        // Use AI if enabled
        if ($this->config->isAiEnabled()) {
            return $this->generateWithAi($pageData);
        }

        // Generate from content
        if (!empty($pageData['main_content'])) {
            $description = $this->extractDescriptionFromContent($pageData['main_content']);

            return $this->processDescription($description);
        }

        // Use summary as fallback
        if (!empty($pageData['summary'])) {
            return $this->processDescription($pageData['summary']);
        }

        // Fallback description
        return $this->processDescription('Page description not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function generateCustom(mixed $customInput, array $pageData = []): string
    {
        if (!is_string($customInput)) {
            throw new \InvalidArgumentException('Custom description input must be a string');
        }

        return $this->processDescription($customInput);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'description';
    }

    /**
     * Process and format the description according to configuration.
     *
     * @param string $description
     * @return string
     */
    private function processDescription(string $description): string
    {
        // Clean the description
        $description = trim(strip_tags($description));

        // Remove extra whitespace
        $description = preg_replace('/\s+/', ' ', $description);

        // Ensure proper length
        $maxLength = $this->config->get('description.max_length', 160);
        $minLength = $this->config->get('description.min_length', 120);

        if (strlen($description) > $maxLength) {
            $description = $this->truncateDescription($description, $maxLength);
        }

        if (strlen($description) < $minLength) {
            $description = $this->padDescription($description, $minLength);
        }

        // Ensure it ends with proper punctuation
        if (!preg_match('/[.!?]$/', $description)) {
            $description .= '.';
        }

        return $description;
    }

    /**
     * Extract description from page content.
     *
     * @param string $content
     * @return string
     */
    private function extractDescriptionFromContent(string $content): string
    {
        // Remove extra whitespace and get clean text
        $content = preg_replace('/\s+/', ' ', trim(strip_tags($content)));

        if (strlen($content) <= 160) {
            return $content;
        }

        // Try to find the first paragraph or meaningful sentence
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $description = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) < 20) {
                continue; // Skip very short sentences
            }

            if (strlen($description . $sentence) <= 140) {
                $description .= ($description !== '' ? '. ' : '') . $sentence;
            } else {
                break;
            }
        }

        return $description ?: substr($content, 0, 140);
    }

    /**
     * Truncate description to fit max length while preserving meaning.
     *
     * @param string $description
     * @param int $maxLength
     * @return string
     */
    private function truncateDescription(string $description, int $maxLength): string
    {
        if (strlen($description) <= $maxLength) {
            return $description;
        }

        // Try to truncate at sentence boundaries
        $sentences = preg_split('/[.!?]+/', $description, -1, PREG_SPLIT_NO_EMPTY);
        $result = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($result . $sentence . '.') <= $maxLength) {
                $result .= ($result !== '' ? '. ' : '') . $sentence;
            } else {
                break;
            }
        }

        if ($result !== '') {
            return $result . '.';
        }

        // Try to truncate at word boundaries
        $truncated = substr($description, 0, $maxLength - 3);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $maxLength * 0.8) {
            return substr($truncated, 0, $lastSpace) . '...';
        }

        // Hard truncate
        return substr($description, 0, $maxLength - 3) . '...';
    }

    /**
     * Pad description if it's too short.
     *
     * @param string $description
     * @param int $minLength
     * @return string
     */
    private function padDescription(string $description, int $minLength): string
    {
        if (strlen($description) >= $minLength) {
            return $description;
        }

        // Add generic content to reach minimum length
        $padding = ' Learn more about this topic and discover additional information.';

        while (strlen($description) < $minLength && strlen($description . $padding) <= 160) {
            $description .= $padding;
        }

        return $description;
    }

    /**
     * Generate description using AI.
     *
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function generateWithAi(array $pageData): string
    {
        // For now, return a processed version of content - AI integration will be implemented later
        $content = $pageData['main_content'] ?? $pageData['summary'] ?? '';

        if ($content) {
            return $this->extractDescriptionFromContent($content);
        }

        return $this->processDescription('AI-generated description will be available soon.');
    }
}
