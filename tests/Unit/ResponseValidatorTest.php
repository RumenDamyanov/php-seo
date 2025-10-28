<?php

declare(strict_types=1);

use Rumenx\PhpSeo\AI\ResponseValidator;
use Rumenx\PhpSeo\Config\SeoConfig;

test('ResponseValidator can be instantiated with config', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    expect($validator)->toBeInstanceOf(ResponseValidator::class);
});

test('ResponseValidator validateTitle validates title length', function () {
    $config = new SeoConfig([
        'title' => [
            'min_length' => 30,
            'max_length' => 60,
        ],
    ]);

    $validator = new ResponseValidator($config);

    $validTitle = 'This is a valid SEO title for testing';
    $shortTitle = 'Too short';
    $longTitle = 'This is a very long title that exceeds the maximum length allowed for SEO purposes and should be truncated';

    $validResult = $validator->validateTitle($validTitle);
    $shortResult = $validator->validateTitle($shortTitle);
    $longResult = $validator->validateTitle($longTitle);

    expect($validResult)->toBeArray()
        ->and($validResult['valid'])->toBeTrue()
        ->and($validResult['sanitized'])->toBe($validTitle)
        ->and($shortResult['valid'])->toBeFalse()
        ->and($longResult['sanitized'])->toBeString()
        ->and(strlen($longResult['sanitized']))->toBeLessThanOrEqual(60);
});

test('ResponseValidator validateDescription validates description length', function () {
    $config = new SeoConfig([
        'description' => [
            'min_length' => 120,
            'max_length' => 160,
        ],
    ]);

    $validator = new ResponseValidator($config);

    $validDesc = 'This is a valid meta description for SEO testing purposes. It contains enough characters to meet the minimum requirements.';
    $shortDesc = 'Too short description.';
    $longDesc = str_repeat('This is a very long description. ', 20);

    $validResult = $validator->validateDescription($validDesc);
    $shortResult = $validator->validateDescription($shortDesc);
    $longResult = $validator->validateDescription($longDesc);

    expect($validResult)->toBeArray()
        ->and($validResult['valid'])->toBeTrue()
        ->and($validResult['sanitized'])->toContain('valid meta description')
        ->and($shortResult['valid'])->toBeFalse()
        ->and($longResult['sanitized'])->toBeString()
        ->and(strlen($longResult['sanitized']))->toBeLessThanOrEqual(160);
});

test('ResponseValidator validateKeywords validates keyword array', function () {
    $config = new SeoConfig([
        'meta_tags' => [
            'keywords_max' => 10,
        ],
    ]);

    $validator = new ResponseValidator($config);

    $validKeywords = ['keyword1', 'keyword2', 'keyword3'];
    $tooManyKeywords = ['k1', 'k2', 'k3', 'k4', 'k5', 'k6', 'k7', 'k8', 'k9', 'k10', 'k11', 'k12'];

    $validResult = $validator->validateKeywords($validKeywords);
    $tooManyResult = $validator->validateKeywords($tooManyKeywords);

    expect($validResult)->toBeArray()
        ->and($validResult['valid'])->toBeTrue()
        ->and($validResult['sanitized'])->toBeArray()
        ->and($validResult['sanitized'])->toBe($validKeywords)
        ->and(count($tooManyResult['sanitized']))->toBeLessThanOrEqual(10);
});

test('ResponseValidator validateTitle removes HTML tags', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $titleWithHtml = '<strong>Important</strong> SEO Title <em>with tags</em>';

    $result = $validator->validateTitle($titleWithHtml);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->not->toContain('<strong>')
        ->and($result['sanitized'])->not->toContain('</strong>')
        ->and($result['sanitized'])->not->toContain('<em>')
        ->and($result['sanitized'])->toContain('Important')
        ->and($result['sanitized'])->toContain('SEO Title');
});

test('ResponseValidator validateDescription removes HTML tags', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $descWithHtml = '<p>This is a description with <a href="#">links</a> and <strong>formatting</strong>.</p>';

    $result = $validator->validateDescription($descWithHtml);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->not->toContain('<p>')
        ->and($result['sanitized'])->not->toContain('<a href')
        ->and($result['sanitized'])->not->toContain('<strong>')
        ->and($result['sanitized'])->toContain('description');
});

test('ResponseValidator validateTitle removes markdown', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $titleWithMarkdown = '**Bold Title** with *italic* text';

    $result = $validator->validateTitle($titleWithMarkdown);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->not->toContain('**')
        ->and($result['sanitized'])->not->toContain('*')
        ->and($result['sanitized'])->toContain('Bold Title')
        ->and($result['sanitized'])->toContain('italic');
});

test('ResponseValidator validateDescription removes markdown', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $descWithMarkdown = 'Description with **bold** and *italic* and [link](url)';

    $result = $validator->validateDescription($descWithMarkdown);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->not->toContain('**')
        ->and($result['sanitized'])->not->toContain('[')
        ->and($result['sanitized'])->not->toContain('](')
        ->and($result['sanitized'])->toContain('Description');
});

test('ResponseValidator validateTitle detects suspicious patterns', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $suspiciousTitle = 'Here is a title for you: Best SEO Practices';

    $result = $validator->validateTitle($suspiciousTitle);

    expect($result)->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['error'])->toContain('suspicious');
});

test('ResponseValidator validateDescription detects suspicious patterns', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    // Make it long enough to pass min length but still suspicious
    $suspiciousDesc = 'Sure! Here\'s a meta description: This is the actual content of the description that is long enough to meet the minimum length requirement for descriptions, making it valid in terms of length but still containing suspicious patterns.';

    $result = $validator->validateDescription($suspiciousDesc);

    expect($result)->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['error'])->toContain('suspicious');
});

test('ResponseValidator validateKeywords removes duplicates', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $keywordsWithDupes = ['keyword', 'Keyword', 'KEYWORD', 'other', 'OTHER'];

    $result = $validator->validateKeywords($keywordsWithDupes);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->toBeArray()
        ->and(count($result['sanitized']))->toBe(2);
});

test('ResponseValidator validateKeywords filters empty strings', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $keywordsWithEmpty = ['keyword1', '', '  ', 'keyword2', 'keyword3'];

    $result = $validator->validateKeywords($keywordsWithEmpty);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->toBeArray()
        ->and(count($result['sanitized']))->toBe(3)
        ->and($result['sanitized'])->toContain('keyword1')
        ->and($result['sanitized'])->toContain('keyword2')
        ->and($result['sanitized'])->toContain('keyword3');
});

test('ResponseValidator validateKeywords handles comma-separated string', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $keywordsString = 'keyword1, keyword2, keyword3';

    $result = $validator->validateKeywords($keywordsString);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->toBeArray()
        ->and(count($result['sanitized']))->toBe(3)
        ->and($result['sanitized'])->toContain('keyword1')
        ->and($result['sanitized'])->toContain('keyword2')
        ->and($result['sanitized'])->toContain('keyword3');
});

test('ResponseValidator validateTitle normalizes whitespace', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $titleWithWhitespace = "Title  with   multiple    spaces\nand\nnewlines\tand\ttabs";

    $result = $validator->validateTitle($titleWithWhitespace);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->not->toContain('  ')
        ->and($result['sanitized'])->not->toContain("\n")
        ->and($result['sanitized'])->not->toContain("\t")
        ->and($result['sanitized'])->toContain('Title with multiple spaces');
});

test('ResponseValidator validateDescription normalizes whitespace', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $descWithWhitespace = "Description  with   extra    spaces\n\nand newlines";

    $result = $validator->validateDescription($descWithWhitespace);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->not->toContain('  ')
        ->and($result['sanitized'])->not->toContain("\n\n");
});

test('ResponseValidator validateTitle truncates at word boundary', function () {
    $config = new SeoConfig([
        'title' => ['max_length' => 50],
    ]);

    $validator = new ResponseValidator($config);

    $longTitle = 'This is a very long title that needs to be truncated at a proper word boundary';

    $result = $validator->validateTitle($longTitle);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->toBeString()
        ->and(strlen($result['sanitized']))->toBeLessThanOrEqual(50)
        ->and($result['sanitized'])->toContain('This is');
});

test('ResponseValidator validateDescription truncates at sentence boundary', function () {
    $config = new SeoConfig([
        'description' => ['max_length' => 100],
    ]);

    $validator = new ResponseValidator($config);

    $longDesc = 'This is the first sentence. This is the second sentence. This is the third sentence that will be cut off.';

    $result = $validator->validateDescription($longDesc);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->toBeString()
        ->and(strlen($result['sanitized']))->toBeLessThanOrEqual(105)
        ->and($result['sanitized'])->toContain('first sentence');
});

test('ResponseValidator handles empty title', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $result = $validator->validateTitle('');

    expect($result)->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['error'])->toContain('empty');
});

test('ResponseValidator handles empty description', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $result = $validator->validateDescription('');

    expect($result)->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['error'])->toContain('empty');
});

test('ResponseValidator handles empty keywords', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $result = $validator->validateKeywords([]);

    expect($result)->toBeArray()
        ->and($result['valid'])->toBeFalse()
        ->and($result['sanitized'])->toBeArray()
        ->and(count($result['sanitized']))->toBe(0);
});

test('ResponseValidator respects custom min_length', function () {
    $config = new SeoConfig([
        'title' => ['min_length' => 40],
        'description' => ['min_length' => 100],
    ]);

    $validator = new ResponseValidator($config);

    $shortTitle = 'Short title';
    $shortDesc = 'Short description';

    $titleResult = $validator->validateTitle($shortTitle);
    $descResult = $validator->validateDescription($shortDesc);

    expect($titleResult)->toBeArray()
        ->and($titleResult['valid'])->toBeFalse()
        ->and($descResult['valid'])->toBeFalse();
});

test('ResponseValidator respects custom max_length', function () {
    $config = new SeoConfig([
        'title' => ['max_length' => 70],
        'description' => ['max_length' => 180],
    ]);

    $validator = new ResponseValidator($config);

    $longTitle = str_repeat('Long title content. ', 20);
    $longDesc = str_repeat('Long description content. ', 20);

    $titleResult = $validator->validateTitle($longTitle);
    $descResult = $validator->validateDescription($longDesc);

    expect($titleResult)->toBeArray()
        ->and(strlen($titleResult['sanitized']))->toBeLessThanOrEqual(70)
        ->and(strlen($descResult['sanitized']))->toBeLessThanOrEqual(180);
});

test('ResponseValidator respects custom max_count for keywords', function () {
    $config = new SeoConfig([
        'meta_tags' => ['keywords_max' => 5],
    ]);

    $validator = new ResponseValidator($config);

    $manyKeywords = ['k1', 'k2', 'k3', 'k4', 'k5', 'k6', 'k7', 'k8', 'k9', 'k10'];

    $result = $validator->validateKeywords($manyKeywords);

    expect($result)->toBeArray()
        ->and(count($result['sanitized']))->toBeLessThanOrEqual(5);
});

test('ResponseValidator removes control characters', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $titleWithControl = "Title with \x00 control \x01 chars \x02";

    $result = $validator->validateTitle($titleWithControl);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->not->toContain("\x00")
        ->and($result['sanitized'])->not->toContain("\x01")
        ->and($result['sanitized'])->not->toContain("\x02")
        ->and($result['sanitized'])->toContain('Title with');
});

test('ResponseValidator handles quotes properly', function () {
    $config = new SeoConfig();
    $validator = new ResponseValidator($config);

    $titleWithQuotes = '"Title in quotes" with \'single quotes\'';

    $result = $validator->validateTitle($titleWithQuotes);

    expect($result)->toBeArray()
        ->and($result['sanitized'])->toBeString()
        ->and($result['sanitized'])->toContain('Title in quotes');
});
