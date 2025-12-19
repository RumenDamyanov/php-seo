<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Generators;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\GeneratorInterface;
use Rumenx\PhpSeo\Providers\ProviderRegistry;

/**
 * Image alt text generator for creating SEO-optimized alt text.
 *
 * Generates alt text using either context-based patterns or AI-powered analysis.
 * This implementation focuses on context-based generation.
 * Future enhancement: Vision API integration for actual image analysis.
 */
class ImageAltGenerator implements GeneratorInterface
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
        // This generates alt text for a single image context
        // Not typically called directly - use generateForImage instead
        if (isset($pageData['image'])) {
            return $this->generateForImage($pageData['image'], $pageData);
        }

        return '';
    }

    /**
     * Generate alt text for a specific image.
     *
     * @param array<string, mixed> $image Image data
     * @param array<string, mixed> $pageData Page context data
     * @return string Generated alt text
     */
    public function generateForImage(array $image, array $pageData = []): string
    {
        // If image already has alt text, optionally enhance it
        $alt = $image['alt'] ?? null;
        if ($alt !== null && $alt !== '') {
            return $this->enhanceAltText($alt, $image, $pageData);
        }

        // Try AI generation if enabled
        if ($this->config->isAiEnabled() && $this->config->get('images.alt_text.enabled', true)) {
            try {
                return $this->generateWithAi($image, $pageData);
            } catch (\Exception $e) {
                // Fall back to context-based generation
            }
        }

        // Context-based generation
        return $this->generateFromContext($image, $pageData);
    }

    /**
     * Generate alt text for multiple images.
     *
     * @param array<array<string, mixed>> $images Array of images
     * @param array<string, mixed> $pageData Page context data
     * @return array<string, string> Map of image src to alt text
     */
    public function generateForImages(array $images, array $pageData = []): array
    {
        $results = [];

        foreach ($images as $image) {
            $src = $image['src'] ?? '';
            if ($src !== '') {
                $results[$src] = $this->generateForImage($image, $pageData);
            }
        }

        return $results;
    }

    /**
     * Generate alt text from context.
     *
     * @param array<string, mixed> $image
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function generateFromContext(array $image, array $pageData): string
    {
        $parts = [];

        // Use image context if available
        if (!empty($image['context'])) {
            $context = $this->cleanContext($image['context']);
            if (strlen($context) > 10) {
                $parts[] = $context;
            }
        }

        // Use filename as fallback
        if (empty($parts) && !empty($image['filename'])) {
            $parts[] = $image['filename'];
        }

        // Use page title as additional context
        if (!empty($pageData['metadata']['title'])) {
            $pageTitle = $pageData['metadata']['title'];
            // Only add if not already in parts
            if (!empty($parts) && !$this->containsSimilarText($parts[0], $pageTitle)) {
                $parts[] = 'related to ' . $pageTitle;
            }
        }

        // Combine parts
        $altText = implode(', ', $parts);

        // Ensure reasonable length (max 125 characters for alt text)
        $maxLength = $this->config->get('images.alt_text.max_length', 125);
        if (strlen($altText) > $maxLength) {
            $altText = $this->truncateAltText($altText, $maxLength);
        }

        // Fallback if nothing generated
        if (empty($altText)) {
            $altText = (!empty($image['filename'])) ? $image['filename'] : 'Image';
        }

        return $altText;
    }

    /**
     * Generate alt text using AI (context-based, not vision).
     *
     * Note: This uses text-based AI, not vision APIs.
     * For actual image analysis, see future vision API enhancement.
     *
     * @param array<string, mixed> $image
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function generateWithAi(array $image, array $pageData): string
    {
        if ($this->providerRegistry === null || !$this->providerRegistry->hasAvailableProvider()) {
            throw new \RuntimeException('No AI provider available');
        }

        // Build prompt from available context
        $prompt = $this->buildAiPrompt($image, $pageData);

        try {
            // Use general generate method with specific prompt
            $altText = $this->providerRegistry->generateWithFallback($prompt, [
                'max_tokens' => 50,
                'temperature' => 0.7,
            ]);

            // Clean up AI response
            $altText = trim($altText);
            $altText = preg_replace('/^["\']|["\']$/', '', $altText); // Remove quotes

            return $this->truncateAltText($altText, 125);
        } catch (\Exception $e) {
            throw new \RuntimeException('AI generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Build AI prompt for alt text generation.
     *
     * @param array<string, mixed> $image
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function buildAiPrompt(array $image, array $pageData): string
    {
        $prompt = "Generate a concise, descriptive alt text (max 125 characters) for an image";

        if (!empty($image['filename'])) {
            $prompt .= " with filename '{$image['filename']}'";
        }

        if (!empty($image['context'])) {
            $context = $this->cleanContext($image['context']);
            $prompt .= ". Context: {$context}";
        }

        if (!empty($pageData['metadata']['title'])) {
            $prompt .= ". Page title: {$pageData['metadata']['title']}";
        }

        if (!empty($pageData['summary'])) {
            $summary = substr($pageData['summary'], 0, 200);
            $prompt .= ". Page summary: {$summary}";
        }

        $prompt .= ". Return only the alt text, no quotes or explanation.";

        return $prompt;
    }

    /**
     * Enhance existing alt text.
     *
     * @param string $existingAlt
     * @param array<string, mixed> $image
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function enhanceAltText(string $existingAlt, array $image, array $pageData): string
    {
        // If alt text is too short (likely placeholder), regenerate
        if (strlen($existingAlt) < 5) {
            return $this->generateFromContext($image, $pageData);
        }

        // If alt text is just the filename, enhance it
        $filename = $image['filename'] ?? '';
        if (!empty($filename) && strtolower($existingAlt) === strtolower($filename)) {
            return $this->generateFromContext($image, $pageData);
        }

        // Otherwise, keep existing alt text
        return $existingAlt;
    }

    /**
     * Clean context text.
     *
     * @param string $context
     * @return string
     */
    private function cleanContext(string $context): string
    {
        // Remove extra whitespace
        $context = preg_replace('/\s+/', ' ', $context);

        // Remove common noise words if context is too long
        if (strlen($context) > 100) {
            // Get first sentence or first 100 chars
            $sentences = preg_split('/[.!?]+/', $context, -1, PREG_SPLIT_NO_EMPTY);
            if (!empty($sentences)) {
                $context = trim($sentences[0]);
            }
        }

        return trim($context);
    }

    /**
     * Truncate alt text to fit max length.
     *
     * @param string $altText
     * @param int $maxLength
     * @return string
     */
    private function truncateAltText(string $altText, int $maxLength): string
    {
        if (strlen($altText) <= $maxLength) {
            return $altText;
        }

        // Try to truncate at word boundary
        $truncated = substr($altText, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false && $lastSpace > $maxLength * 0.7) {
            return substr($truncated, 0, $lastSpace);
        }

        // Hard truncate
        return substr($altText, 0, $maxLength - 3) . '...';
    }

    /**
     * Check if two texts contain similar content.
     *
     * @param string $text1
     * @param string $text2
     * @return bool
     */
    private function containsSimilarText(string $text1, string $text2): bool
    {
        $text1Lower = strtolower($text1);
        $text2Lower = strtolower($text2);

        return str_contains($text1Lower, $text2Lower) || str_contains($text2Lower, $text1Lower);
    }

    /**
     * {@inheritdoc}
     */
    public function generateCustom(mixed $customInput, array $pageData = []): string
    {
        if (!is_string($customInput)) {
            throw new \InvalidArgumentException('Custom alt text input must be a string');
        }

        return $customInput;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'image_alt';
    }
}
