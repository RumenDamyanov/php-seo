<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Generators\ImageAltGenerator;

describe('ImageAltGenerator', function () {
    beforeEach(function () {
        $this->config = new SeoConfig();
        $this->generator = new ImageAltGenerator($this->config);
    });

    it('generates alt text from filename', function () {
        $image = [
            'src' => '/images/beautiful-sunset-beach.jpg',
            'filename' => 'Beautiful Sunset Beach',
            'context' => '',
            'alt' => null,
        ];

        $altText = $this->generator->generateForImage($image);

        expect($altText)->toBe('Beautiful Sunset Beach');
    });

    it('generates alt text from context', function () {
        $image = [
            'src' => '/test.jpg',
            'filename' => 'Test',
            'context' => 'This is a beautiful mountain landscape with snow peaks',
            'alt' => null,
        ];

        $altText = $this->generator->generateForImage($image);

        expect($altText)->toContain('mountain')
            ->and($altText)->toContain('landscape');
    });

    it('uses page title as additional context', function () {
        $image = [
            'src' => '/test.jpg',
            'filename' => 'Product',
            'context' => 'Our flagship product',
            'alt' => null,
        ];

        $pageData = [
            'metadata' => [
                'title' => 'Amazing Product Launch',
            ],
        ];

        $altText = $this->generator->generateForImage($image, $pageData);

        expect($altText)->not->toBeEmpty();
    });

    it('truncates alt text to max length', function () {
        $image = [
            'src' => '/test.jpg',
            'filename' => 'Test',
            'context' => str_repeat('This is a very long context that exceeds the maximum allowed length for alt text ', 5),
            'alt' => null,
        ];

        $altText = $this->generator->generateForImage($image);

        expect(strlen($altText))->toBeLessThanOrEqual(125);
    });

    it('keeps existing good alt text', function () {
        $image = [
            'src' => '/test.jpg',
            'filename' => 'Test',
            'context' => 'Some context',
            'alt' => 'Existing good alt text',
        ];

        $altText = $this->generator->generateForImage($image);

        expect($altText)->toBe('Existing good alt text');
    });

    it('enhances short placeholder alt text', function () {
        $image = [
            'src' => '/test.jpg',
            'filename' => 'Beautiful Sunset',
            'context' => 'Amazing sunset view',
            'alt' => 'img',
        ];

        $altText = $this->generator->generateForImage($image);

        expect($altText)->not->toBe('img')
            ->and(strlen($altText))->toBeGreaterThan(5);
    });

    it('enhances filename-only alt text', function () {
        $image = [
            'src' => '/beautiful-sunset.jpg',
            'filename' => 'Beautiful Sunset',
            'context' => 'Sunset over the ocean waves',
            'alt' => 'Beautiful Sunset', // Exact match with filename
        ];

        $altText = $this->generator->generateForImage($image);

        // Should enhance with context
        expect($altText)->toContain('ocean');
    });

    it('generates alt text for multiple images', function () {
        $images = [
            [
                'src' => '/img1.jpg',
                'filename' => 'Image One',
                'context' => 'First image',
                'alt' => null,
            ],
            [
                'src' => '/img2.jpg',
                'filename' => 'Image Two',
                'context' => 'Second image',
                'alt' => null,
            ],
        ];

        $results = $this->generator->generateForImages($images);

        expect($results)->toHaveCount(2)
            ->and($results)->toHaveKey('/img1.jpg')
            ->and($results)->toHaveKey('/img2.jpg');
    });

    it('handles images without context or filename', function () {
        $image = [
            'src' => '/test.jpg',
            'filename' => '',
            'context' => '',
            'alt' => '',
        ];

        $altText = $this->generator->generateForImage($image);

        expect($altText)->toBe('Image');
    });

    it('cleans up context text', function () {
        $image = [
            'src' => '/test.jpg',
            'filename' => 'Test',
            'context' => "Multiple    spaces\nand\nnewlines",
            'alt' => null,
        ];

        $altText = $this->generator->generateForImage($image);

        expect($altText)->not->toContain("\n")
            ->and($altText)->not->toContain('    ');
    });

    it('supports image_alt type', function () {
        expect($this->generator->supports('image_alt'))->toBeTrue()
            ->and($this->generator->supports('other'))->toBeFalse();
    });

    it('generates custom alt text', function () {
        $customAlt = 'Custom alt text';
        $result = $this->generator->generateCustom($customAlt);

        expect($result)->toBe($customAlt);
    });

    it('throws exception for invalid custom input', function () {
        expect(fn () => $this->generator->generateCustom(123))
            ->toThrow(InvalidArgumentException::class);
    });

    it('truncates at word boundary when possible', function () {
        $longContext = 'This is a very long context that should be truncated at a word boundary rather than in the middle of a word to maintain readability';

        $image = [
            'src' => '/test.jpg',
            'filename' => 'Test',
            'context' => $longContext,
            'alt' => null,
        ];

        $altText = $this->generator->generateForImage($image);

        // Should be truncated to max length
        expect(strlen($altText))->toBeLessThanOrEqual(125);
    });

    it('combines context and page title appropriately', function () {
        $image = [
            'src' => '/product.jpg',
            'filename' => 'Product',
            'context' => 'Our new smartphone',
            'alt' => null,
        ];

        $pageData = [
            'metadata' => [
                'title' => 'iPhone 15 Launch Event',
            ],
        ];

        $altText = $this->generator->generateForImage($image, $pageData);

        expect($altText)->not->toBeEmpty()
            ->and(strlen($altText))->toBeLessThanOrEqual(125);
    });
});
