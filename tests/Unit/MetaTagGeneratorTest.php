<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Generators\MetaTagGenerator;

test('MetaTagGenerator can be instantiated', function () {
    $config = new SeoConfig();
    $generator = new MetaTagGenerator($config);

    expect($generator)->toBeInstanceOf(MetaTagGenerator::class);
});

test('MetaTagGenerator supports meta_tags type', function () {
    $config = new SeoConfig();
    $generator = new MetaTagGenerator($config);

    expect($generator->supports('meta_tags'))->toBeTrue()
        ->and($generator->supports('other_type'))->toBeFalse();
});

test('MetaTagGenerator generates default meta tags', function () {
    $config = new SeoConfig([
        'meta_tags' => [
            'default_tags' => [
                'charset' => 'utf-8',
                'viewport' => 'width=device-width, initial-scale=1',
            ],
        ],
    ]);
    $generator = new MetaTagGenerator($config);

    $pageData = ['metadata' => []];
    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('charset', 'utf-8')
        ->and($metaTags)->toHaveKey('viewport', 'width=device-width, initial-scale=1');
});

test('MetaTagGenerator generates robots meta tags with all directives', function () {
    $config = new SeoConfig([
        'meta_tags' => [
            'robots' => [
                'index' => false,
                'follow' => false,
                'archive' => false,
                'snippet' => false,
                'imageindex' => false,
            ],
        ],
    ]);
    $generator = new MetaTagGenerator($config);

    $pageData = ['metadata' => []];
    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('robots')
        ->and($metaTags['robots'])->toContain('noindex')
        ->and($metaTags['robots'])->toContain('nofollow')
        ->and($metaTags['robots'])->toContain('noarchive')
        ->and($metaTags['robots'])->toContain('nosnippet')
        ->and($metaTags['robots'])->toContain('noimageindex');
});

test('MetaTagGenerator generates robots meta tags with positive directives', function () {
    $config = new SeoConfig([
        'meta_tags' => [
            'robots' => [
                'index' => true,
                'follow' => true,
                'archive' => true,
                'snippet' => true,
                'imageindex' => true,
            ],
        ],
    ]);
    $generator = new MetaTagGenerator($config);

    $pageData = ['metadata' => []];
    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('robots')
        ->and($metaTags['robots'])->toContain('index')
        ->and($metaTags['robots'])->toContain('follow')
        ->and($metaTags['robots'])->not()->toContain('noarchive')
        ->and($metaTags['robots'])->not()->toContain('nosnippet')
        ->and($metaTags['robots'])->not()->toContain('noimageindex');
});

test('MetaTagGenerator generates Open Graph tags with metadata', function () {
    $config = new SeoConfig([
        'meta_tags' => [
            'open_graph' => [
                'enabled' => true,
                'site_name' => 'Test Site',
                'type' => 'article',
                'locale' => 'en_GB',
            ],
        ],
    ]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [
            'title' => 'Test Page Title',
            'description' => 'Test page description for Open Graph',
            'url' => 'https://example.com/test',
            'image' => 'https://example.com/image.jpg',
            'image_alt' => 'Test image description',
        ],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('og:type', 'article')
        ->and($metaTags)->toHaveKey('og:locale', 'en_GB')
        ->and($metaTags)->toHaveKey('og:site_name', 'Test Site')
        ->and($metaTags)->toHaveKey('og:title', 'Test Page Title')
        ->and($metaTags)->toHaveKey('og:description', 'Test page description for Open Graph')
        ->and($metaTags)->toHaveKey('og:url', 'https://example.com/test')
        ->and($metaTags)->toHaveKey('og:image', 'https://example.com/image.jpg')
        ->and($metaTags)->toHaveKey('og:image:alt', 'Test image description');
});

test('MetaTagGenerator generates Open Graph tags from headings when no title metadata', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'headings' => [
            ['level' => 1, 'text' => 'Main Heading'],
            ['level' => 2, 'text' => 'Sub Heading'],
        ],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('og:title', 'Main Heading');
});

test('MetaTagGenerator generates Open Graph description from summary when no description metadata', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'summary' => 'This is a test summary for the page content that should be used as Open Graph description when no explicit description is provided.',
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('og:description')
        ->and(strlen($metaTags['og:description']))->toBeLessThanOrEqual(200);
});

test('MetaTagGenerator generates Open Graph image from content images when no image metadata', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'images' => [
            ['src' => '/test-image.jpg', 'alt' => 'Test image alt text'],
            ['src' => '/second-image.jpg', 'alt' => 'Second image'],
        ],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('og:image', '/test-image.jpg')
        ->and($metaTags)->toHaveKey('og:image:alt', 'Test image alt text');
});

test('MetaTagGenerator skips Open Graph when disabled', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => false]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = ['metadata' => ['title' => 'Test Title']];
    $metaTags = $generator->generate($pageData);

    expect($metaTags)->not()->toHaveKey('og:title')
        ->and($metaTags)->not()->toHaveKey('og:type');
});

test('MetaTagGenerator generates Twitter Card tags with metadata', function () {
    $config = new SeoConfig([
        'meta_tags' => [
            'twitter' => [
                'enabled' => true,
                'card' => 'summary',
                'site' => '@testsite',
                'creator' => '@testcreator',
            ],
        ],
    ]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [
            'title' => 'Twitter Test Title',
            'description' => 'Twitter test description',
            'image' => 'https://example.com/twitter-image.jpg',
            'image_alt' => 'Twitter image description',
        ],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('twitter:card', 'summary')
        ->and($metaTags)->toHaveKey('twitter:site', '@testsite')
        ->and($metaTags)->toHaveKey('twitter:creator', '@testcreator')
        ->and($metaTags)->toHaveKey('twitter:title', 'Twitter Test Title')
        ->and($metaTags)->toHaveKey('twitter:description', 'Twitter test description')
        ->and($metaTags)->toHaveKey('twitter:image', 'https://example.com/twitter-image.jpg')
        ->and($metaTags)->toHaveKey('twitter:image:alt', 'Twitter image description');
});

test('MetaTagGenerator generates Twitter Card description from summary when no description metadata', function () {
    $config = new SeoConfig(['meta_tags' => ['twitter' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'summary' => 'This is a test summary for Twitter Card description that should be truncated to 200 characters maximum length.',
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('twitter:description')
        ->and(strlen($metaTags['twitter:description']))->toBeLessThanOrEqual(200);
});

test('MetaTagGenerator generates Twitter Card image from content images when no image metadata', function () {
    $config = new SeoConfig(['meta_tags' => ['twitter' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'images' => [
            ['src' => '/twitter-test-image.jpg', 'alt' => 'Twitter test image alt text'],
        ],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('twitter:image', '/twitter-test-image.jpg')
        ->and($metaTags)->toHaveKey('twitter:image:alt', 'Twitter test image alt text');
});

test('MetaTagGenerator skips Twitter Card when disabled', function () {
    $config = new SeoConfig(['meta_tags' => ['twitter' => ['enabled' => false]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = ['metadata' => ['title' => 'Test Title']];
    $metaTags = $generator->generate($pageData);

    expect($metaTags)->not()->toHaveKey('twitter:title')
        ->and($metaTags)->not()->toHaveKey('twitter:card');
});

test('MetaTagGenerator generates comprehensive SEO tags', function () {
    $config = new SeoConfig();
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'language' => 'en',
        'keywords' => ['php', 'seo', 'meta', 'tags', 'optimization', 'web', 'development', 'testing', 'framework', 'library', 'extra1', 'extra2'],
        'metadata' => [
            'author' => 'Test Author',
            'published_at' => '2023-01-01T12:00:00Z',
            'updated_at' => '2023-06-01T14:30:00Z',
            'category' => 'Technology',
            'canonical_url' => 'https://example.com/canonical',
        ],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->toHaveKey('language', 'en')
        ->and($metaTags)->toHaveKey('author', 'Test Author')
        ->and($metaTags)->toHaveKey('keywords')
        ->and($metaTags)->toHaveKey('article:published_time', '2023-01-01T12:00:00Z')
        ->and($metaTags)->toHaveKey('article:modified_time', '2023-06-01T14:30:00Z')
        ->and($metaTags)->toHaveKey('article:section', 'Technology')
        ->and($metaTags)->toHaveKey('canonical', 'https://example.com/canonical');

    // Keywords should be limited to 10
    $keywords = explode(', ', $metaTags['keywords']);
    expect(count($keywords))->toBeLessThanOrEqual(10);
});

test('MetaTagGenerator generates custom meta tags', function () {
    $config = new SeoConfig();
    $generator = new MetaTagGenerator($config);

    $pageData = ['metadata' => []];
    $customTags = [
        'custom:tag1' => 'Custom Value 1',
        'custom:tag2' => 'Custom Value 2',
    ];

    $metaTags = $generator->generateCustom($customTags, $pageData);

    expect($metaTags)->toHaveKey('custom:tag1', 'Custom Value 1')
        ->and($metaTags)->toHaveKey('custom:tag2', 'Custom Value 2')
        ->and($metaTags)->toHaveKey('robots'); // Should also have default generated tags
});

test('MetaTagGenerator throws exception for invalid custom input', function () {
    $config = new SeoConfig();
    $generator = new MetaTagGenerator($config);

    $pageData = ['metadata' => []];

    expect(fn () => $generator->generateCustom('invalid', $pageData))
        ->toThrow(InvalidArgumentException::class, 'Custom meta tags input must be an array');
});

test('MetaTagGenerator handles empty headings array', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'headings' => [],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->not()->toHaveKey('og:title');
});

test('MetaTagGenerator handles headings without level 1', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'headings' => [
            ['level' => 2, 'text' => 'Sub Heading'],
            ['level' => 3, 'text' => 'Sub Sub Heading'],
            ['level' => 4, 'text' => 'Deep Heading'],
        ],
    ];

    $metaTags = $generator->generate($pageData);

    // Should not have og:title since no H1 is found
    expect($metaTags)->not()->toHaveKey('og:title');
});

test('MetaTagGenerator handles missing image src in content images', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'images' => [
            ['alt' => 'Image without src'], // Missing 'src' key
        ],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->not()->toHaveKey('og:image');
});

test('MetaTagGenerator handles empty images array', function () {
    $config = new SeoConfig(['meta_tags' => ['open_graph' => ['enabled' => true]]]);
    $generator = new MetaTagGenerator($config);

    $pageData = [
        'metadata' => [],
        'images' => [],
    ];

    $metaTags = $generator->generate($pageData);

    expect($metaTags)->not()->toHaveKey('og:image');
});
