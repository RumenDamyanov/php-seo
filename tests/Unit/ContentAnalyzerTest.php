<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Analyzers\ContentAnalyzer;
use Rumenx\PhpSeo\Config\SeoConfig;

test('ContentAnalyzer can analyze basic content', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = "This is a test content about PHP SEO optimization.";

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toBeArray()
        ->and($analysis['word_count'])->toBeGreaterThan(0)
        ->and($analysis['character_count'])->toBeGreaterThan(0);
});

test('ContentAnalyzer calculates word count correctly', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = "One two three four five";

    $analysis = $analyzer->analyze($content);

    expect($analysis['word_count'])->toBe(5);
});

test('ContentAnalyzer calculates character count correctly', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = "Hello";

    $analysis = $analyzer->analyze($content);

    expect($analysis['character_count'])->toBe(5);
});

test('ContentAnalyzer handles empty content', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = "";

    $analysis = $analyzer->analyze($content);

    expect($analysis['word_count'])->toBe(0)
        ->and($analysis['character_count'])->toBe(0);
});

test('ContentAnalyzer extracts keywords from content', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = "PHP SEO optimization is important for web development. SEO helps websites rank better.";

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toHaveKey('keywords')
        ->and($analysis['keywords'])->toBeArray();
});

test('ContentAnalyzer supports different content types', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);

    expect($analyzer->supports('text/html'))->toBeTrue()
        ->and($analyzer->supports('text/plain'))->toBeTrue()
        ->and($analyzer->supports('markdown'))->toBeTrue()
        ->and($analyzer->supports('application/json'))->toBeFalse();
});

test('ContentAnalyzer extracts images with alt and title attributes', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = '<img src="/test.jpg" alt="Test image" title="Test title"><img src="/test2.jpg">';

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toHaveKey('images')
        ->and($analysis['images'])->toBeArray()
        ->and($analysis['images'][0])->toHaveKey('src', '/test.jpg')
        ->and($analysis['images'][0])->toHaveKey('alt', 'Test image')
        ->and($analysis['images'][0])->toHaveKey('title', 'Test title')
        ->and($analysis['images'][1])->toHaveKey('src', '/test2.jpg')
        ->and($analysis['images'][1])->toHaveKey('alt', '')
        ->and($analysis['images'][1])->toHaveKey('title', '');
});

test('ContentAnalyzer extracts main content from main tag', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = '<html><head></head><body><main>This is the main content</main></body></html>';

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toHaveKey('main_content')
        ->and($analysis['main_content'])->toBe('This is the main content');
});

test('ContentAnalyzer extracts main content from article tag when no main tag', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = '<html><head></head><body><article>This is article content</article></body></html>';

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toHaveKey('main_content')
        ->and($analysis['main_content'])->toBe('This is article content');
});

test('ContentAnalyzer detects markdown content type', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = "# Heading\n\n* List item\n\n1. Numbered item";

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toHaveKey('content_type')
        ->and($analysis['content_type'])->toBe('markdown');
});

test('ContentAnalyzer detects HTML content type', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = '<div><p>HTML content</p></div>';

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toHaveKey('content_type')
        ->and($analysis['content_type'])->toBe('text/html');
});

test('ContentAnalyzer detects plain text content type', function () {
    $config = new SeoConfig();
    $analyzer = new ContentAnalyzer($config);
    $content = 'This is plain text content without any special formatting.';

    $analysis = $analyzer->analyze($content);

    expect($analysis)->toHaveKey('content_type')
        ->and($analysis['content_type'])->toBe('text/plain');
});
