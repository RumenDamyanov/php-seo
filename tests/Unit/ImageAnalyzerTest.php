<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Analyzers\ImageAnalyzer;
use Rumenx\PhpSeo\Config\SeoConfig;

describe('ImageAnalyzer', function () {
    beforeEach(function () {
        $this->config = new SeoConfig();
        $this->analyzer = new ImageAnalyzer($this->config);
    });

    it('extracts images from HTML content', function () {
        $content = '<img src="/test.jpg" alt="Test image" title="Test">';
        $result = $this->analyzer->analyze($content);

        expect($result)->toHaveKey('images')
            ->and($result['images'])->toHaveCount(1)
            ->and($result['images'][0]['src'])->toBe('/test.jpg')
            ->and($result['images'][0]['alt'])->toBe('Test image')
            ->and($result['images'][0]['title'])->toBe('Test');
    });

    it('identifies images without alt text', function () {
        $content = '<img src="/test1.jpg" alt="Alt text"><img src="/test2.jpg">';
        $result = $this->analyzer->analyze($content);

        expect($result['images'])->toHaveCount(2)
            ->and($result['images'][0]['needs_alt'])->toBeFalse()
            ->and($result['images'][1]['needs_alt'])->toBeTrue()
            ->and($result['images_without_alt'])->toBe(1);
    });

    it('extracts image context from surrounding content', function () {
        $content = '<p>Here is a beautiful sunset image.</p><img src="/sunset.jpg"><p>It was taken at the beach.</p>';
        $result = $this->analyzer->analyze($content);

        expect($result['images'])->toHaveCount(1)
            ->and($result['images'][0]['context'])->toContain('sunset');
    });

    it('extracts filename from image source', function () {
        $content = '<img src="/images/beautiful-sunset-beach.jpg">';
        $result = $this->analyzer->analyze($content);

        expect($result['images'][0]['filename'])->toBe('Beautiful Sunset Beach');
    });

    it('handles images with all attributes', function () {
        $content = '<img src="/test.jpg" alt="Alt" title="Title" class="img-fluid" id="main-img">';
        $result = $this->analyzer->analyze($content);

        expect($result['images'][0])->toMatchArray([
            'src' => '/test.jpg',
            'alt' => 'Alt',
            'title' => 'Title',
            'class' => 'img-fluid',
            'id' => 'main-img',
        ]);
    });

    it('handles content with no images', function () {
        $content = '<p>No images here</p>';
        $result = $this->analyzer->analyze($content);

        expect($result['images'])->toBeEmpty()
            ->and($result['image_count'])->toBe(0)
            ->and($result['images_without_alt'])->toBe(0);
    });

    it('handles multiple images', function () {
        $content = '
            <img src="/img1.jpg" alt="First">
            <img src="/img2.jpg" alt="Second">
            <img src="/img3.jpg">
        ';
        $result = $this->analyzer->analyze($content);

        expect($result['images'])->toHaveCount(3)
            ->and($result['image_count'])->toBe(3)
            ->and($result['images_without_alt'])->toBe(1);
    });

    it('extracts context with special characters', function () {
        $content = '<p>Test "quotes" &amp; special</p><img src="/test.jpg">';
        $result = $this->analyzer->analyze($content);

        expect($result['images'][0]['context'])->not->toBeEmpty();
    });

    it('supports images type', function () {
        expect($this->analyzer->supports('images'))->toBeTrue()
            ->and($this->analyzer->supports('other'))->toBeFalse();
    });

    it('gets images needing alt text', function () {
        $content = '
            <img src="/img1.jpg" alt="Has alt">
            <img src="/img2.jpg">
            <img src="/img3.jpg">
        ';
        $result = $this->analyzer->analyze($content);
        $needingAlt = $this->analyzer->getImagesNeedingAlt($result['images']);

        expect($needingAlt)->toHaveCount(2);
    });

    it('handles images with single quotes', function () {
        $content = "<img src='/test.jpg' alt='Alt text'>";
        $result = $this->analyzer->analyze($content);

        expect($result['images'][0]['src'])->toBe('/test.jpg')
            ->and($result['images'][0]['alt'])->toBe('Alt text');
    });

    it('handles images without quotes', function () {
        $content = '<img src=/test.jpg alt=AltText>';
        $result = $this->analyzer->analyze($content);

        expect($result['images'])->toHaveCount(1);
    });

    it('cleans filename with various separators', function () {
        $content = '<img src="/my_image-test_file.jpg">';
        $result = $this->analyzer->analyze($content);

        expect($result['images'][0]['filename'])->toBe('My Image Test File');
    });
});
