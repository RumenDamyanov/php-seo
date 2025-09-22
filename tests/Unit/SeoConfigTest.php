<?php

declare(strict_types=1);

use Rumenx\PhpSeo\Config\SeoConfig;

test('SeoConfig can be instantiated with default settings', function () {
    $config = new SeoConfig();

    expect($config)->toBeInstanceOf(SeoConfig::class);
});

test('SeoConfig allows custom max title length', function () {
    $config = new SeoConfig();
    $config->set('title.max_length', 50);

    expect($config->get('title.max_length'))->toBe(50);
});

test('SeoConfig allows custom max description length', function () {
    $config = new SeoConfig();
    $config->set('description.max_length', 140);

    expect($config->get('description.max_length'))->toBe(140);
});

test('SeoConfig allows enabling/disabling keywords', function () {
    $config = new SeoConfig();

    // Default should be true
    expect($config->get('analysis.extract_keywords'))->toBeTrue();

    $config->set('analysis.extract_keywords', false);
    expect($config->get('analysis.extract_keywords'))->toBeFalse();
});

test('SeoConfig allows setting default site name', function () {
    $config = new SeoConfig();
    $config->set('title.site_name', 'My Website');

    expect($config->get('title.site_name'))->toBe('My Website');
});

test('SeoConfig allows setting custom separators', function () {
    $config = new SeoConfig();
    $config->set('title.separator', ' | ');

    expect($config->get('title.separator'))->toBe(' | ');
});

test('SeoConfig can check if AI mode is enabled', function () {
    $config = new SeoConfig();

    // Default mode is manual
    expect($config->isAiEnabled())->toBeFalse();

    $config->set('mode', 'ai');
    expect($config->isAiEnabled())->toBeTrue();
});

test('SeoConfig can merge configurations', function () {
    $config = new SeoConfig();
    $originalLength = $config->get('title.max_length');

    $config->merge(['title' => ['custom_setting' => 'test']]);

    // The merge should keep the original max_length and add the new setting
    expect($config->get('title.max_length'))->toBe($originalLength)
        ->and($config->get('title.custom_setting'))->toBe('test');
});

test('SeoConfig can check if configuration key exists', function () {
    $config = new SeoConfig();

    // Test existing keys
    expect($config->has('title'))->toBeTrue()
        ->and($config->has('title.max_length'))->toBeTrue()
        ->and($config->has('description.max_length'))->toBeTrue();

    // Test non-existing keys
    expect($config->has('non_existing_key'))->toBeFalse()
        ->and($config->has('title.non_existing'))->toBeFalse()
        ->and($config->has('non.existing.nested.key'))->toBeFalse();

    // Test edge cases
    expect($config->has(''))->toBeFalse();
});

test('SeoConfig can return all configuration', function () {
    $config = new SeoConfig();
    $all = $config->all();

    expect($all)->toBeArray()
        ->and($all)->toHaveKey('title')
        ->and($all)->toHaveKey('description')
        ->and($all)->toHaveKey('meta_tags')
        ->and($all)->toHaveKey('analysis')
        ->and($all)->toHaveKey('mode')
        ->and($all)->toHaveKey('enabled');
});

test('SeoConfig can check if manual mode is enabled', function () {
    $config = new SeoConfig();

    // Default mode should be manual and enabled
    expect($config->isManualEnabled())->toBeTrue();

    // Test with manual mode explicitly set
    $config->set('mode', 'manual');
    expect($config->isManualEnabled())->toBeTrue();

    // Test with hybrid mode
    $config->set('mode', 'hybrid');
    expect($config->isManualEnabled())->toBeTrue();

    // Test with AI mode
    $config->set('mode', 'ai');
    expect($config->isManualEnabled())->toBeFalse();

    // Test when disabled
    $config->set('mode', 'manual');
    $config->set('enabled', false);
    expect($config->isManualEnabled())->toBeFalse();
});

test('SeoConfig can get AI configuration', function () {
    $config = new SeoConfig();

    // Default AI config should be an array
    $aiConfig = $config->getAiConfig();
    expect($aiConfig)->toBeArray();

    // Test with custom AI config
    $customAiConfig = [
        'provider' => 'openai',
        'model' => 'gpt-4',
        'api_key' => 'test-key',
    ];
    $config->set('ai', $customAiConfig);

    expect($config->getAiConfig())->toBe($customAiConfig);
});

test('SeoConfig can create new instance with merged configuration', function () {
    $config = new SeoConfig();
    $originalTitleLength = $config->get('title.max_length');

    $newConfig = $config->with([
        'title' => ['max_length' => 100],
        'custom_setting' => 'test_value',
    ]);

    // Original instance should remain unchanged
    expect($config->get('title.max_length'))->toBe($originalTitleLength)
        ->and($config->has('custom_setting'))->toBeFalse();

    // New instance should have merged configuration
    expect($newConfig)->toBeInstanceOf(SeoConfig::class)
        ->and($newConfig)->not()->toBe($config)
        ->and($newConfig->get('title.max_length'))->toBe(100)
        ->and($newConfig->get('custom_setting'))->toBe('test_value');
});
