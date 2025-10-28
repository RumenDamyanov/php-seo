<?php

declare(strict_types=1);

use Rumenx\PhpSeo\AI\PromptBuilder;
use Rumenx\PhpSeo\Config\SeoConfig;

test('PromptBuilder can be instantiated with config', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    expect($builder)->toBeInstanceOf(PromptBuilder::class);
});

test('PromptBuilder buildTitlePrompt generates prompt', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = [
        'summary' => 'This is a test summary',
        'main_content' => 'This is the main content of the page',
        'keywords' => ['test', 'example', 'SEO'],
    ];

    $prompt = $builder->buildTitlePrompt($analysis);

    expect($prompt)->toBeString()
        ->and($prompt)->toContain('SEO-optimized page title')
        ->and($prompt)->toContain('60 characters');
});

test('PromptBuilder buildDescriptionPrompt generates prompt', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = [
        'summary' => 'This is a test summary',
        'main_content' => 'This is the main content of the page',
        'keywords' => ['test', 'example', 'SEO'],
    ];

    $prompt = $builder->buildDescriptionPrompt($analysis);

    expect($prompt)->toBeString()
        ->and($prompt)->toContain('meta description')
        ->and($prompt)->toContain('120-160 characters');
});

test('PromptBuilder buildKeywordsPrompt generates prompt', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = [
        'summary' => 'This is a test summary',
        'main_content' => 'This is the main content of the page',
        'headings' => [
            ['text' => 'Main Heading', 'level' => 1],
            ['text' => 'Subheading', 'level' => 2],
        ],
    ];

    $prompt = $builder->buildKeywordsPrompt($analysis);

    expect($prompt)->toBeString()
        ->and($prompt)->toContain('keywords')
        ->and($prompt)->toContain('comma-separated');
});

test('PromptBuilder uses custom title template from config', function () {
    $config = new SeoConfig([
        'ai' => [
            'prompts' => [
                'title' => 'Custom template: {summary}',
            ],
        ],
    ]);

    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test Summary'];

    $prompt = $builder->buildTitlePrompt($analysis);

    expect($prompt)->toContain('Custom template')
        ->and($prompt)->toContain('Test Summary');
});

test('PromptBuilder uses custom description template from config', function () {
    $config = new SeoConfig([
        'ai' => [
            'prompts' => [
                'description' => 'Custom desc: {summary}',
            ],
        ],
    ]);

    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test Summary'];

    $prompt = $builder->buildDescriptionPrompt($analysis);

    expect($prompt)->toContain('Custom desc')
        ->and($prompt)->toContain('Test Summary');
});

test('PromptBuilder uses custom keywords template from config', function () {
    $config = new SeoConfig([
        'ai' => [
            'prompts' => [
                'keywords' => 'Custom keywords: {summary}',
            ],
        ],
    ]);

    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test Summary'];

    $prompt = $builder->buildKeywordsPrompt($analysis);

    expect($prompt)->toContain('Custom keywords')
        ->and($prompt)->toContain('Test Summary');
});

test('PromptBuilder extracts context from analysis', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = [
        'summary' => 'Page summary',
        'main_content' => 'Full content of the page',
        'headings' => [
            ['text' => 'Heading 1', 'level' => 1],
            ['text' => 'Heading 2', 'level' => 2],
        ],
        'keywords' => ['keyword1', 'keyword2', 'keyword3'],
    ];

    $prompt = $builder->buildTitlePrompt($analysis);

    // The prompt should contain context extracted from analysis
    expect($prompt)->toBeString()
        ->and(strlen($prompt))->toBeGreaterThan(50);
});

test('PromptBuilder handles empty analysis', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $prompt = $builder->buildTitlePrompt([]);

    expect($prompt)->toBeString()
        ->and($prompt)->toContain('SEO-optimized page title');
});

test('PromptBuilder handles analysis with only summary', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = ['summary' => 'Just a summary'];

    $prompt = $builder->buildTitlePrompt($analysis);

    expect($prompt)->toBeString()
        ->and($prompt)->toContain('Just a summary');
});

test('PromptBuilder handles analysis with only content', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = ['main_content' => 'Just content'];

    $prompt = $builder->buildDescriptionPrompt($analysis);

    expect($prompt)->toBeString()
        ->and(strlen($prompt))->toBeGreaterThan(0);
});

test('PromptBuilder respects max_length from config', function () {
    $config = new SeoConfig([
        'title' => ['max_length' => 70],
        'description' => ['max_length' => 180],
    ]);

    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test'];

    $titlePrompt = $builder->buildTitlePrompt($analysis);
    $descPrompt = $builder->buildDescriptionPrompt($analysis);

    expect($titlePrompt)->toContain('70')
        ->and($descPrompt)->toContain('180');
});

test('PromptBuilder handles headings in analysis', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = [
        'summary' => 'Test summary',
        'headings' => [
            ['text' => 'Important Heading', 'level' => 1],
            ['text' => 'Secondary Heading', 'level' => 2],
            ['text' => 'Third Level', 'level' => 3],
        ],
    ];

    $prompt = $builder->buildTitlePrompt($analysis);

    expect($prompt)->toBeString()
        ->and(strlen($prompt))->toBeGreaterThan(50);
});

test('PromptBuilder handles keywords in analysis', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $analysis = [
        'summary' => 'Test summary',
        'keywords' => ['PHP', 'SEO', 'testing', 'automation'],
    ];

    $prompt = $builder->buildTitlePrompt($analysis);

    expect($prompt)->toBeString()
        ->and(strlen($prompt))->toBeGreaterThan(50);
});

test('PromptBuilder truncates long content', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);

    $longContent = str_repeat('This is a very long content. ', 500);

    $analysis = [
        'summary' => 'Short summary',
        'main_content' => $longContent,
    ];

    $prompt = $builder->buildDescriptionPrompt($analysis);

    // Prompt should not contain the entire long content
    expect($prompt)->toBeString()
        ->and(strlen($prompt))->toBeLessThan(strlen($longContent));
});

test('PromptBuilder uses language from config', function () {
    $config = new SeoConfig([
        'language' => 'es',
    ]);

    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test'];

    $prompt = $builder->buildTitlePrompt($analysis);

    expect($prompt)->toContain('es');
});

test('PromptBuilder uses tone from config', function () {
    $config = new SeoConfig([
        'generation' => [
            'tone' => 'professional',
        ],
    ]);

    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test'];

    $prompt = $builder->buildTitlePrompt($analysis);

    expect($prompt)->toContain('professional');
});

test('PromptBuilder handles max_count for keywords', function () {
    $config = new SeoConfig([
        'meta_tags' => ['keywords_max' => 15],
    ]);

    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test'];

    $prompt = $builder->buildKeywordsPrompt($analysis);

    expect($prompt)->toContain('15');
});

test('PromptBuilder handles custom options for max_length', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test'];

    $prompt = $builder->buildTitlePrompt($analysis, ['max_length' => 80]);

    expect($prompt)->toContain('80');
});

test('PromptBuilder handles custom options for min_length', function () {
    $config = new SeoConfig();
    $builder = new PromptBuilder($config);
    $analysis = ['summary' => 'Test'];

    $prompt = $builder->buildDescriptionPrompt($analysis, ['min_length' => 100]);

    expect($prompt)->toContain('100');
});
