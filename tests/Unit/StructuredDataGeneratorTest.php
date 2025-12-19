<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Generators\StructuredDataGenerator;
use Rumenx\PhpSeo\Schema\ArticleSchema;
use Rumenx\PhpSeo\Schema\WebPageSchema;

beforeEach(function () {
    $this->config = new SeoConfig();
    $this->generator = new StructuredDataGenerator($this->config);
});

test('it generates article schema for article content', function () {
    $pageData = [
        'metadata' => [
            'title' => 'Test Article Title',
            'author' => 'John Doe',
            'published_date' => '2024-01-01T12:00:00Z',
        ],
        'summary' => 'This is a test article summary.',
        'word_count' => 500,
        'images' => [
            ['src' => 'https://example.com/image.jpg'],
        ],
    ];

    $schemas = $this->generator->generate($pageData);

    expect($schemas)->toBeArray();
    expect($schemas)->toHaveCount(1);
    expect($schemas[0])->toBeInstanceOf(ArticleSchema::class);
});

test('it generates webpage schema for non-article content', function () {
    $pageData = [
        'metadata' => [
            'title' => 'Test Page Title',
            'url' => 'https://example.com/page',
        ],
        'summary' => 'This is a test page summary.',
        'word_count' => 100,
    ];

    $schemas = $this->generator->generate($pageData);

    expect($schemas)->toBeArray();
    expect($schemas)->toHaveCount(1);
    expect($schemas[0])->toBeInstanceOf(WebPageSchema::class);
});

test('it supports structured_data type', function () {
    expect($this->generator->supports('structured_data'))->toBeTrue();
    expect($this->generator->supports('other'))->toBeFalse();
});

test('it generates custom schema from BaseSchema instance', function () {
    $schema = new ArticleSchema();
    $schema->setHeadline('Custom Headline');

    $json = $this->generator->generateCustom($schema);

    expect($json)->toBeString();
    expect($json)->toContain('Custom Headline');
    expect($json)->toContain('@type');
    expect($json)->toContain('Article');
});

test('it throws exception for invalid custom input', function () {
    $this->generator->generateCustom('invalid');
})->throws(\InvalidArgumentException::class);

test('it generates multiple schemas when configured', function () {
    $config = new SeoConfig([
        'structured_data' => [
            'types' => [
                'article' => true,
                'organization' => true,
            ],
            'organization' => [
                'name' => 'Test Org',
                'url' => 'https://example.com',
            ],
        ],
    ]);
    $generator = new StructuredDataGenerator($config);

    $pageData = [
        'metadata' => [
            'title' => 'Test Article',
            'author' => 'Jane Doe',
        ],
        'word_count' => 400,
    ];

    $schemas = $generator->generate($pageData);

    expect($schemas)->toHaveCount(2);
});

test('it includes breadcrumb schema when breadcrumbs provided', function () {
    $pageData = [
        'metadata' => ['title' => 'Test Page'],
        'word_count' => 100,
        'breadcrumbs' => [
            ['name' => 'Home', 'url' => 'https://example.com/'],
            ['name' => 'Category', 'url' => 'https://example.com/category'],
        ],
    ];

    $schemas = $this->generator->generate($pageData);

    expect($schemas)->toHaveCount(2); // WebPage + Breadcrumb
});

test('it respects configuration for disabled types', function () {
    $config = new SeoConfig([
        'structured_data' => [
            'types' => [
                'webpage' => false,
                'article' => true,
            ],
        ],
    ]);
    $generator = new StructuredDataGenerator($config);

    $pageData = [
        'metadata' => ['title' => 'Test Page'],
        'word_count' => 50,
    ];

    $schemas = $generator->generate($pageData);

    expect($schemas)->toBeEmpty();
});
