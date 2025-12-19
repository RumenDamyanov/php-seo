<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Cache\CacheKeyGenerator;

beforeEach(function () {
    $this->generator = new CacheKeyGenerator();
});

test('it generates cache key for content analysis', function () {
    $content = '<html><body>Test content</body></html>';
    $metadata = ['url' => '/test'];

    $key = $this->generator->forContentAnalysis($content, $metadata);

    expect($key)->toBeString();
    expect($key)->toStartWith('seo:analysis:');
});

test('it generates same key for same content analysis', function () {
    $content = '<html><body>Test</body></html>';
    $metadata = ['url' => '/test'];

    $key1 = $this->generator->forContentAnalysis($content, $metadata);
    $key2 = $this->generator->forContentAnalysis($content, $metadata);

    expect($key1)->toBe($key2);
});

test('it generates different key for different content', function () {
    $key1 = $this->generator->forContentAnalysis('content1', []);
    $key2 = $this->generator->forContentAnalysis('content2', []);

    expect($key1)->not->toBe($key2);
});

test('it generates cache key for title generation', function () {
    $analysis = ['summary' => 'Test summary'];
    $options = ['max_length' => 60];

    $key = $this->generator->forTitleGeneration($analysis, $options);

    expect($key)->toBeString();
    expect($key)->toStartWith('seo:title:');
});

test('it generates same key for same title generation params', function () {
    $analysis = ['summary' => 'Test'];
    $options = ['max_length' => 60];

    $key1 = $this->generator->forTitleGeneration($analysis, $options);
    $key2 = $this->generator->forTitleGeneration($analysis, $options);

    expect($key1)->toBe($key2);
});

test('it generates cache key for description generation', function () {
    $analysis = ['summary' => 'Test summary'];
    $options = ['max_length' => 160];

    $key = $this->generator->forDescriptionGeneration($analysis, $options);

    expect($key)->toBeString();
    expect($key)->toStartWith('seo:description:');
});

test('it generates cache key for keywords generation', function () {
    $analysis = ['keywords' => ['test', 'seo']];
    $options = [];

    $key = $this->generator->forKeywordsGeneration($analysis, $options);

    expect($key)->toBeString();
    expect($key)->toStartWith('seo:keywords:');
});

test('it generates cache key for meta tags generation', function () {
    $pageData = ['metadata' => ['title' => 'Test']];
    $overrides = ['og:image' => 'test.jpg'];

    $key = $this->generator->forMetaTagsGeneration($pageData, $overrides);

    expect($key)->toBeString();
    expect($key)->toStartWith('seo:metatags:');
});

test('it generates cache key for image alt generation', function () {
    $image = ['src' => 'test.jpg', 'alt' => ''];
    $pageData = ['metadata' => ['title' => 'Test Page']];

    $key = $this->generator->forImageAltGeneration($image, $pageData);

    expect($key)->toBeString();
    expect($key)->toStartWith('seo:imagealt:');
});

test('it generates cache key for provider response', function () {
    $provider = 'openai';
    $model = 'gpt-4o-mini';
    $prompt = 'Generate a title for this content';
    $options = ['temperature' => 0.7];

    $key = $this->generator->forProviderResponse($provider, $model, $prompt, $options);

    expect($key)->toBeString();
    expect($key)->toStartWith('seo:provider:openai:gpt-4o-mini:');
});

test('it generates different keys for different providers', function () {
    $prompt = 'Test prompt';
    $options = [];

    $key1 = $this->generator->forProviderResponse('openai', 'gpt-4o-mini', $prompt, $options);
    $key2 = $this->generator->forProviderResponse('anthropic', 'claude-3-5-sonnet-20241022', $prompt, $options);

    expect($key1)->not->toBe($key2);
});

test('it generates different keys for different models', function () {
    $provider = 'openai';
    $prompt = 'Test prompt';
    $options = [];

    $key1 = $this->generator->forProviderResponse($provider, 'gpt-4o-mini', $prompt, $options);
    $key2 = $this->generator->forProviderResponse($provider, 'gpt-4o', $prompt, $options);

    expect($key1)->not->toBe($key2);
});

test('it generates different keys for different prompts', function () {
    $provider = 'openai';
    $model = 'gpt-4o-mini';
    $options = [];

    $key1 = $this->generator->forProviderResponse($provider, $model, 'prompt 1', $options);
    $key2 = $this->generator->forProviderResponse($provider, $model, 'prompt 2', $options);

    expect($key1)->not->toBe($key2);
});

test('it handles empty arrays in key generation', function () {
    $key = $this->generator->forTitleGeneration([], []);

    expect($key)->toBeString();
    expect($key)->toContain('empty');
});

test('it generates consistent hash for same data with different key order', function () {
    $data1 = ['a' => 1, 'b' => 2];
    $data2 = ['b' => 2, 'a' => 1];

    $key1 = $this->generator->forTitleGeneration($data1, []);
    $key2 = $this->generator->forTitleGeneration($data2, []);

    expect($key1)->toBe($key2);
});

test('it generates short hash keys', function () {
    $content = str_repeat('Lorem ipsum dolor sit amet ', 1000);
    $key = $this->generator->forContentAnalysis($content, []);

    // Key should be reasonably short despite long content
    expect(strlen($key))->toBeLessThan(100);
});
