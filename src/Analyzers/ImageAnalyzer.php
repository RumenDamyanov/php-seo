<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Analyzers;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\AnalyzerInterface;

/**
 * Image analyzer for extracting and analyzing images from content.
 *
 * This analyzer extracts images from HTML content and provides
 * structured data about each image for further processing.
 */
class ImageAnalyzer implements AnalyzerInterface
{
    /**
     * @param SeoConfig $config Configuration (reserved for future use)
     * @phpstan-ignore-next-line (Config reserved for future image analysis features)
     */
    public function __construct(private SeoConfig $config)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(string $content, array $metadata = []): array
    {
        $images = $this->extractImages($content);
        $processedImages = $this->processImages($images, $content, $metadata);

        return [
            'images' => $processedImages,
            'image_count' => count($processedImages),
            'images_without_alt' => $this->countImagesWithoutAlt($processedImages),
        ];
    }

    /**
     * Extract images from HTML content.
     *
     * @param string $content
     * @return array<array<string, string|null>>
     */
    private function extractImages(string $content): array
    {
        $images = [];

        // Match img tags and extract src, alt, title attributes
        if (preg_match_all('/<img[^>]+>/i', $content, $matches)) {
            foreach ($matches[0] as $imgTag) {
                $image = [
                    'src' => $this->extractAttribute($imgTag, 'src'),
                    'alt' => $this->extractAttribute($imgTag, 'alt'),
                    'title' => $this->extractAttribute($imgTag, 'title'),
                    'class' => $this->extractAttribute($imgTag, 'class'),
                    'id' => $this->extractAttribute($imgTag, 'id'),
                ];

                // Only add if we have at least a src
                if ($image['src'] !== null) {
                    $images[] = $image;
                }
            }
        }

        return $images;
    }

    /**
     * Extract an attribute value from an HTML tag.
     *
     * @param string $tag
     * @param string $attribute
     * @return string|null
     */
    private function extractAttribute(string $tag, string $attribute): ?string
    {
        $pattern = '/' . $attribute . '=["\']([^"\']*)["\']|' . $attribute . '=([^\s>]*)/i';

        if (preg_match($pattern, $tag, $matches)) {
            return $matches[1] ?? $matches[2] ?? null;
        }

        return null;
    }

    /**
     * Process images to add contextual information.
     *
     * @param array<array<string, string|null>> $images
     * @param string $content
     * @param array<string, mixed> $metadata
     * @return array<array<string, mixed>>
     */
    private function processImages(array $images, string $content, array $metadata): array
    {
        $processed = [];

        foreach ($images as $image) {
            $processed[] = [
                'src' => $image['src'],
                'alt' => $image['alt'],
                'title' => $image['title'],
                'class' => $image['class'],
                'id' => $image['id'],
                'context' => $this->extractImageContext($image['src'], $content),
                'filename' => $this->extractFilename($image['src']),
                'needs_alt' => empty($image['alt']),
            ];
        }

        return $processed;
    }

    /**
     * Extract contextual text around an image.
     *
     * @param string|null $src
     * @param string $content
     * @return string
     */
    private function extractImageContext(?string $src, string $content): string
    {
        if ($src === null) {
            return '';
        }

        // Find the image in content
        $escapedSrc = preg_quote($src, '/');
        $pattern = '/(.{0,200})<img[^>]*' . $escapedSrc . '[^>]*>(.{0,200})/is';

        if (preg_match($pattern, $content, $matches)) {
            // Get text before and after the image
            $before = strip_tags($matches[1] ?? '');
            $after = strip_tags($matches[2] ?? '');

            // Combine and clean up
            $context = trim($before . ' ' . $after);

            // Remove extra whitespace
            $context = preg_replace('/\s+/', ' ', $context);

            return $context;
        }

        return '';
    }

    /**
     * Extract filename from image source.
     *
     * @param string|null $src
     * @return string
     */
    private function extractFilename(?string $src): string
    {
        if ($src === null) {
            return '';
        }

        // Extract filename from path
        $parts = explode('/', $src);
        $filename = end($parts);

        // Remove extension
        $filename = preg_replace('/\.[^.]+$/', '', $filename);

        // Clean up: convert hyphens, underscores to spaces
        $filename = str_replace(['-', '_'], ' ', $filename);

        // Capitalize words
        $filename = ucwords($filename);

        return $filename;
    }

    /**
     * Count images without alt text.
     *
     * @param array<array<string, mixed>> $images
     * @return int
     */
    private function countImagesWithoutAlt(array $images): int
    {
        return count(array_filter($images, fn ($img) => $img['needs_alt']));
    }

    /**
     * Get images that need alt text.
     *
     * @param array<array<string, mixed>> $images
     * @return array<array<string, mixed>>
     */
    public function getImagesNeedingAlt(array $images): array
    {
        return array_filter($images, fn ($img) => $img['needs_alt']);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'images';
    }
}
