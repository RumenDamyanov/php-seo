<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SEO Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the default configuration for the PHP SEO package.
    | You can customize these settings according to your application's needs.
    |
    */

    // Global settings
    'enabled' => env('SEO_ENABLED', true),
    'mode' => env('SEO_MODE', 'manual'), // 'ai', 'manual', 'hybrid'
    'cache_enabled' => env('SEO_CACHE_ENABLED', true),
    'cache_ttl' => env('SEO_CACHE_TTL', 3600), // 1 hour

    // AI Provider settings
    'ai' => [
        'provider' => env('SEO_AI_PROVIDER', 'openai'), // 'openai', 'anthropic', 'google', 'xai', 'ollama'
        'model' => env('SEO_AI_MODEL', 'gpt-4-turbo-preview'),
        'api_key' => env('SEO_AI_API_KEY'),
        'api_url' => env('SEO_AI_API_URL'),
        'timeout' => env('SEO_AI_TIMEOUT', 30),
        'max_retries' => env('SEO_AI_MAX_RETRIES', 3),
        'fallback_providers' => [],
        'cost_tracking' => env('SEO_AI_COST_TRACKING', false),
        'rate_limiting' => [
            'enabled' => env('SEO_AI_RATE_LIMITING', true),
            'requests_per_minute' => env('SEO_AI_RATE_LIMIT', 10),
        ],
    ],

    // Title generation settings
    'title' => [
        'max_length' => env('SEO_TITLE_MAX_LENGTH', 60),
        'min_length' => env('SEO_TITLE_MIN_LENGTH', 10),
        'pattern' => env('SEO_TITLE_PATTERN', '{title}'),
        'site_name' => env('SEO_SITE_NAME', config('app.name', '')),
        'separator' => env('SEO_TITLE_SEPARATOR', ' | '),
        'case' => env('SEO_TITLE_CASE', 'title'), // 'title', 'sentence', 'lower', 'upper'
        'ai_prompt' => 'Generate an SEO-optimized title for this content. Keep it under 60 characters and make it compelling for search results.',
    ],

    // Description generation settings
    'description' => [
        'max_length' => env('SEO_DESCRIPTION_MAX_LENGTH', 160),
        'min_length' => env('SEO_DESCRIPTION_MIN_LENGTH', 120),
        'pattern' => env('SEO_DESCRIPTION_PATTERN'),
        'ai_prompt' => 'Generate an SEO-optimized meta description for this content. Keep it between 120-160 characters and make it compelling for search results.',
    ],

    // Meta tags settings
    'meta_tags' => [
        'default_tags' => [
            'viewport' => 'width=device-width, initial-scale=1',
            'charset' => 'utf-8',
        ],
        'open_graph' => [
            'enabled' => env('SEO_OG_ENABLED', true),
            'site_name' => env('SEO_OG_SITE_NAME', config('app.name', '')),
            'type' => env('SEO_OG_TYPE', 'website'),
            'locale' => env('SEO_OG_LOCALE', 'en_US'),
        ],
        'twitter' => [
            'enabled' => env('SEO_TWITTER_ENABLED', true),
            'card' => env('SEO_TWITTER_CARD', 'summary_large_image'),
            'site' => env('SEO_TWITTER_SITE'),
            'creator' => env('SEO_TWITTER_CREATOR'),
        ],
        'robots' => [
            'index' => env('SEO_ROBOTS_INDEX', true),
            'follow' => env('SEO_ROBOTS_FOLLOW', true),
            'archive' => env('SEO_ROBOTS_ARCHIVE', true),
            'snippet' => env('SEO_ROBOTS_SNIPPET', true),
            'imageindex' => env('SEO_ROBOTS_IMAGEINDEX', true),
        ],
    ],

    // Image optimization settings
    'images' => [
        'alt_text' => [
            'enabled' => env('SEO_IMAGE_ALT_ENABLED', true),
            'ai_vision' => env('SEO_IMAGE_AI_VISION', false),
            'pattern' => env('SEO_IMAGE_ALT_PATTERN'),
            'ai_prompt' => 'Generate descriptive alt text for this image based on its context.',
        ],
        'title_text' => [
            'enabled' => env('SEO_IMAGE_TITLE_ENABLED', true),
            'pattern' => env('SEO_IMAGE_TITLE_PATTERN'),
        ],
    ],

    // Content analysis settings
    'analysis' => [
        'extract_headings' => env('SEO_EXTRACT_HEADINGS', true),
        'extract_images' => env('SEO_EXTRACT_IMAGES', true),
        'extract_links' => env('SEO_EXTRACT_LINKS', true),
        'extract_keywords' => env('SEO_EXTRACT_KEYWORDS', true),
        'min_content_length' => env('SEO_MIN_CONTENT_LENGTH', 100),
        'language_detection' => env('SEO_LANGUAGE_DETECTION', true),
    ],

    // Laravel-specific settings
    'laravel' => [
        'middleware' => [
            'enabled' => env('SEO_MIDDLEWARE_ENABLED', false),
            'routes' => ['web'],
        ],
        'blade_directives' => env('SEO_BLADE_DIRECTIVES', true),
        'config_cache' => env('SEO_CONFIG_CACHE', true),
    ],

    // Logging settings
    'logging' => [
        'enabled' => env('SEO_LOGGING_ENABLED', false),
        'level' => env('SEO_LOGGING_LEVEL', 'info'),
        'channels' => [env('SEO_LOGGING_CHANNEL', 'stack')],
    ],

    // Performance settings
    'performance' => [
        'lazy_loading' => env('SEO_LAZY_LOADING', true),
        'compression' => env('SEO_COMPRESSION', true),
        'minify_output' => env('SEO_MINIFY_OUTPUT', false),
    ],
];