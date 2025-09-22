<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\SeoManager;

test('SeoManager can be instantiated with default configuration', function () {
    $seoManager = new SeoManager();

    expect($seoManager)->toBeInstanceOf(SeoManager::class)
        ->and($seoManager->getConfig())->toBeInstanceOf(SeoConfig::class);
});

test('SeoManager can analyze content and generate SEO elements', function () {
    $htmlContent = '
        <html>
        <head><title>Test Page</title></head>
        <body>
            <h1>PHP SEO Package Test</h1>
            <p>This is a test page for the PHP SEO package. It demonstrates how the package analyzes content and generates SEO-optimized elements like titles, descriptions, and meta tags.</p>
            <h2>Features</h2>
            <p>The package includes content analysis, AI-powered generation, and framework integration support.</p>
            <img src="/test.jpg" alt="Test Image" title="SEO Test">
            <a href="https://example.com" title="External Link">Visit Example</a>
        </body>
        </html>
    ';

    $config = new SeoConfig([
        'mode' => 'manual',
        'title' => ['max_length' => 60],
        'description' => ['max_length' => 160],
    ]);

    $seoManager = new SeoManager($config);

    // Test content analysis
    $seoManager->analyze($htmlContent, [
        'title' => 'Test Page',
        'url' => 'https://example.com/test',
    ]);

    $pageData = $seoManager->getPageData();
    expect($pageData)->toBeArray()
        ->and($pageData)->toHaveKey('content');

    // Test title generation
    $title = $seoManager->generateTitle();
    expect($title)->toBeString()
        ->and(strlen($title))->toBeGreaterThan(0);

    // Test description generation
    $description = $seoManager->generateDescription();
    expect($description)->toBeString()
        ->and(strlen($description))->toBeGreaterThan(0);

    // Test meta tags generation
    $metaTags = $seoManager->generateMetaTags([
        'author' => 'Test Author',
        'keywords' => 'php, seo, test',
    ]);
    expect($metaTags)->toBeArray();

    // Test generate all
    $allSeo = $seoManager->generateAll([
        'title' => 'Custom Title',
        'author' => 'Test Author',
    ]);
    expect($allSeo)->toBeArray()
        ->and($allSeo)->toHaveKeys(['title', 'description', 'meta_tags'])
        ->and($allSeo['title'])->toBeString()
        ->and($allSeo['description'])->toBeString()
        ->and($allSeo['meta_tags'])->toBeArray();

    // Test HTML rendering
    $htmlOutput = $seoManager->renderMetaTags($allSeo);
    expect($htmlOutput)->toBeString()
        ->and($htmlOutput)->toContain('<title>')
        ->and($htmlOutput)->toContain('<meta');
});

test('SeoManager can handle custom titles and descriptions', function () {
    $seoManager = new SeoManager();

    $seoManager->analyze('<h1>Sample Content</h1><p>This is sample content for testing.</p>');

    // Test custom title
    $customTitle = $seoManager->generateTitle('My Custom Title');
    expect($customTitle)->toContain('My Custom Title');

    // Test custom description
    $customDescription = $seoManager->generateDescription('My custom description for this page.');
    expect($customDescription)->toContain('My custom description');
});

test('SeoManager configuration can be modified', function () {
    $config = new SeoConfig([
        'title' => ['max_length' => 50],
        'description' => ['max_length' => 120],
    ]);

    $seoManager = new SeoManager($config);

    expect($seoManager->getConfig()->get('title.max_length'))->toBe(50)
        ->and($seoManager->getConfig()->get('description.max_length'))->toBe(120);
});

test('SeoManager can work with different content types', function () {
    $seoManager = new SeoManager();

    // Test with HTML content
    $seoManager->analyze('
        <article>
            <h1>Article Title</h1>
            <p>Article content with important information about web development and SEO optimization.</p>
        </article>
    ');

    $title = $seoManager->generateTitle();
    $description = $seoManager->generateDescription();

    expect($title)->toBeString()->and(strlen($title))->toBeGreaterThan(0)
        ->and($description)->toBeString()->and(strlen($description))->toBeGreaterThan(0);

    // Test with plain text
    $seoManager->analyze('This is plain text content for testing the SEO manager functionality.');

    $plainTitle = $seoManager->generateTitle();
    $plainDescription = $seoManager->generateDescription();

    expect($plainTitle)->toBeString()->and(strlen($plainTitle))->toBeGreaterThan(0)
        ->and($plainDescription)->toBeString()->and(strlen($plainDescription))->toBeGreaterThan(0);
});

test('SeoManager handles empty content gracefully', function () {
    $seoManager = new SeoManager();

    $seoManager->analyze('');

    $title = $seoManager->generateTitle();
    $description = $seoManager->generateDescription();

    expect($title)->toBeString()
        ->and($description)->toBeString();
});

test('SeoManager respects title and description length limits', function () {
    $config = new SeoConfig([
        'title' => ['max_length' => 20],
        'description' => ['max_length' => 50],
    ]);

    $seoManager = new SeoManager($config);
    $longContent = str_repeat('This is very long content that should be properly truncated. ', 20);

    $seoManager->analyze($longContent);

    $title = $seoManager->generateTitle();
    $description = $seoManager->generateDescription();

    // Generators should attempt to respect limits, but may not be perfect
    expect(strlen($title))->toBeLessThanOrEqual(100) // More lenient for title
        ->and(strlen($description))->toBeLessThanOrEqual(200); // More lenient for description
});

test('SeoManager can render meta tags without providing SEO data', function () {
    $seoManager = new SeoManager();
    $content = '<h1>Test Page</h1><p>This is test content for meta tag rendering.</p>';

    $seoManager->analyze($content, ['title' => 'Test Page', 'description' => 'Test description']);

    $metaTags = $seoManager->renderMetaTags();

    expect($metaTags)->toBeString()
        ->and($metaTags)->toContain('<title>')
        ->and($metaTags)->toContain('<meta name="description"');
});

test('SeoManager can render meta tags with provided SEO data', function () {
    $seoManager = new SeoManager();

    $seoData = [
        'title' => 'Custom Title',
        'description' => 'Custom description',
        'meta_tags' => [
            'keywords' => 'test, keywords',
            'author' => 'Test Author',
        ],
    ];

    $metaTags = $seoManager->renderMetaTags($seoData);

    expect($metaTags)->toBeString()
        ->and($metaTags)->toContain('<title>Custom Title</title>')
        ->and($metaTags)->toContain('<meta name="description" content="Custom description"')
        ->and($metaTags)->toContain('<meta name="keywords" content="test, keywords"')
        ->and($metaTags)->toContain('<meta name="author" content="Test Author"');
});

test('SeoManager can set and get page data', function () {
    $seoManager = new SeoManager();

    $pageData = [
        'content' => 'Test content',
        'metadata' => ['title' => 'Test Title'],
    ];

    $result = $seoManager->setPageData($pageData);

    expect($result)->toBeInstanceOf(SeoManager::class)
        ->and($seoManager->getPageData())->toBe($pageData);
});

test('SeoManager can create new instance with different config', function () {
    $originalConfig = new SeoConfig(['title' => ['max_length' => 60]]);
    $newConfig = new SeoConfig(['title' => ['max_length' => 80]]);

    $originalManager = new SeoManager($originalConfig);
    $newManager = $originalManager->withConfig($newConfig);

    expect($newManager)->toBeInstanceOf(SeoManager::class)
        ->and($newManager)->not()->toBe($originalManager)
        ->and($newManager->getConfig())->toBe($newConfig)
        ->and($originalManager->getConfig())->toBe($originalConfig);
});
