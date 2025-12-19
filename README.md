# php-seo

[![CI](https://github.com/RumenDamyanov/php-seo/actions/workflows/ci.yml/badge.svg)](https://github.com/RumenDamyanov/php-seo/actions/workflows/ci.yml)
[![Analyze](https://github.com/RumenDamyanov/php-seo/actions/workflows/analyze.yml/badge.svg)](https://github.com/RumenDamyanov/php-seo/actions/workflows/analyze.yml)
[![Style](https://github.com/RumenDamyanov/php-seo/actions/workflows/style.yml/badge.svg)](https://github.com/RumenDamyanov/php-seo/actions/workflows/style.yml)
[![CodeQL](https://github.com/RumenDamyanov/php-seo/actions/workflows/github-code-scanning/codeql/badge.svg)](https://github.com/RumenDamyanov/php-seo/actions/workflows/github-code-scanning/codeql)
[![Dependabot](https://github.com/RumenDamyanov/php-seo/actions/workflows/dependabot/dependabot-updates/badge.svg)](https://github.com/RumenDamyanov/php-seo/actions/workflows/dependabot/dependabot-updates)
[![codecov](https://codecov.io/gh/RumenDamyanov/php-seo/branch/master/graph/badge.svg)](https://codecov.io/gh/RumenDamyanov/php-seo)

**php-seo** is a modern, AI-powered, framework-agnostic PHP package for automated SEO optimization. It intelligently generates meta tags, titles, descriptions, and alt texts using configurable AI providers or manual patterns, seamlessly integrating with Laravel, Symfony, or any PHP project.

## ✨ Features

### 🤖 AI-Powered Automation

- **Intelligent Content Analysis**: AI reads and analyzes page content to generate optimal titles, descriptions, and meta tags
- **Image Analysis**: Automatically generates alt text and titles for images by analyzing context
- **Social Media Optimization**: Generates platform-specific meta tags (Open Graph, Twitter Cards, etc.)
- **Multi-Provider Support**: Integration with popular AI models (GPT-4o, Claude 3.5, Gemini 1.5, Grok, Ollama)

### 🔧 Manual Configuration Mode

- **Pattern-Based Generation**: Generate titles and descriptions using configurable patterns
- **Manual Override**: Full manual control over all SEO elements
- **Fallback Systems**: Graceful degradation when AI services are unavailable
- **Template-Based**: Use predefined templates for consistent SEO across pages

### 🏗️ Framework Integration

- **Laravel**: Native service provider, facades, config publishing, middleware
- **Symfony**: Bundle integration with services and configuration
- **Generic PHP**: Framework-agnostic core that works with any PHP project

### 🚀 Modern PHP

- **Type-safe**: Full PHP 8.2+ type declarations and strict types
- **Fluent Interface**: Chainable methods for elegant, readable code
- **Extensible**: Plugin architecture for custom analyzers and generators
- **High Performance**: Optimized for speed with caching and lazy loading
- **100% Test Pass Rate**: Comprehensive test suite with 453+ passing tests using Pest

### 💎 Advanced Features

- **PSR-16 Caching**: Built-in caching support for improved performance (80%+ faster)
- **Rate Limiting**: Automatic API throttling with token bucket algorithm
- **Structured Data**: JSON-LD generation for Schema.org (Article, WebPage, Organization, Breadcrumb)
- **Cost Optimization**: 80-90% reduction in AI API costs through intelligent caching
- **Production Ready**: PHPStan level 6, 100% test coverage, strict types

## 📖 Quick Links

- 🚀 [Installation](#installation)
- 💡 [Usage Examples](#usage-examples)
- ⚙️ [Configuration](https://github.com/RumenDamyanov/php-seo/wiki/Configuration)
- 🤖 [AI Providers](https://github.com/RumenDamyanov/php-seo/wiki/AI-Integration)
- 🧪 [Testing & Development](#testing--development)
- 🤝 [Contributing](CONTRIBUTING.md)
- 💬 [Discussions](https://github.com/RumenDamyanov/php-seo/discussions)
- 🔒 [Security Policy](SECURITY.md)
- 💝 [Support & Funding](FUNDING.md)
- 📄 [License](#license)

## 📦 Installation

Install via Composer:

```bash
composer require rumenx/php-seo
```

### Requirements

- PHP 8.2 or higher
- ext-json
- ext-curl

## 🚀 Usage Examples

### Laravel Example

#### Basic Setup

```php
// In your controller
use Rumenx\PhpSeo\SeoManager;

public function show(Post $post, SeoManager $seo)
{
    $content = view('posts.content', compact('post'))->render();

    $seoData = $seo->analyze($content, [
        'title' => $post->title,
        'url' => request()->url(),
        'image' => $post->featured_image,
        'author' => $post->author->name,
        'published_at' => $post->published_at->toISOString(),
    ])->generateAll();

    return view('posts.show', compact('post', 'seoData'));
}
```

#### Blade Template

```blade
@extends('layouts.app')

@section('head')
    {!! app('seo')->renderMetaTags($seoData) !!}
@endsection

@section('content')
    <!-- Your content here -->
@endsection
```

#### Using Facade

```php
use Rumenx\PhpSeo\Facades\Seo;

// Quick generation
$title = Seo::analyze($content)->generateTitle();
$description = Seo::generateDescription();
$metaTags = Seo::generateMetaTags();

// Complete SEO data
$seoData = Seo::analyze($content, $metadata)->generateAll();
```

#### Middleware Integration

```php
// In app/Http/Kernel.php
protected $routeMiddleware = [
    'seo' => \Rumenx\PhpSeo\Integrations\Laravel\SeoMiddleware::class,
];

// In your routes
Route::get('/posts/{post}', [PostController::class, 'show'])
    ->middleware('seo');
```

### Symfony Example

#### Controller Usage

```php
use Rumenx\PhpSeo\SeoManager;
use Symfony\Component\HttpFoundation\Response;

class PostController extends AbstractController
{
    public function show(Post $post, SeoManager $seo): Response
    {
        $content = $this->renderView('post/content.html.twig', ['post' => $post]);

        $seoData = $seo->analyze($content, [
            'title' => $post->getTitle(),
            'url' => $this->generateUrl('post_show', ['id' => $post->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'image' => $post->getFeaturedImage(),
            'author' => $post->getAuthor()->getName(),
        ])->generateAll();

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'seo_data' => $seoData,
        ]);
    }
}
```

#### Twig Template

```twig
{% extends 'base.html.twig' %}

{% block head %}
    {{ seo_meta_tags(seo_data)|raw }}
{% endblock %}

{% block body %}
    <!-- Your content here -->
{% endblock %}
```

### Generic PHP Example

```php
require 'vendor/autoload.php';

use Rumenx\PhpSeo\SeoManager;
use Rumenx\PhpSeo\Config\SeoConfig;

// Basic usage
$seo = new SeoManager();
$content = file_get_contents('page-content.html');

$seoData = $seo->analyze($content, [
    'title' => 'My Page Title',
    'url' => 'https://example.com/page',
])->generateAll();

// Output meta tags
echo $seo->renderMetaTags($seoData);

// With custom configuration
$config = new SeoConfig([
    'title' => [
        'pattern' => '{title} - {site_name}',
        'site_name' => 'My Website',
        'max_length' => 55,
    ],
    'mode' => 'ai',
    'ai' => [
        'provider' => 'openai',
        'api_key' => $_ENV['OPENAI_API_KEY'],
    ],
]);

$seo = new SeoManager($config);
$optimizedTitle = $seo->analyze($content)->generateTitle();
```

### Caching for Performance

Reduce AI costs by 80-90% and improve speed with PSR-16 caching:

```php
use Rumenx\PhpSeo\SeoManager;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

// Setup Redis cache (or any PSR-16 implementation)
$redisClient = RedisAdapter::createConnection('redis://localhost');
$cache = new Psr16Cache(new RedisAdapter($redisClient));

// Initialize with caching
$seo = new SeoManager($config, $cache);

// First call: analyzes and caches
$seo->analyze($content)->generateAll();

// Second call: instant retrieval from cache (80%+ faster!)
$seo->analyze($content)->generateAll();
```

See [`examples/caching.php`](examples/caching.php) for complete example.

### Structured Data (JSON-LD)

Generate Schema.org structured data for rich snippets:

```php
// Configure structured data
$config = new SeoConfig([
    'structured_data' => [
        'enabled' => true,
        'types' => [
            'article' => true,
            'breadcrumb' => true,
        ],
        'publisher' => [
            'name' => 'Your Site Name',
            'logo' => 'https://example.com/logo.png',
        ],
    ],
]);

$seo = new SeoManager($config);
$seo->analyze($content, [
    'title' => 'Article Title',
    'author' => 'John Doe',
    'published_date' => '2024-01-15T10:00:00Z',
]);

// Render in <head> section
echo $seo->renderStructuredData();
```

See [`examples/structured-data.php`](examples/structured-data.php) for complete example.

### Complete Integration

For a full example showing all features, see [`examples/complete-integration.php`](examples/complete-integration.php).

## ⚙️ Configuration

### Laravel Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Rumenx\PhpSeo\Integrations\Laravel\SeoServiceProvider"
```

Edit `config/seo.php`:

```php
return [
    'mode' => 'hybrid', // 'ai', 'manual', 'hybrid'
    'ai' => [
        'provider' => 'openai',
        'api_key' => env('SEO_AI_API_KEY'),
        'model' => 'gpt-4o-mini', // Best cost/performance (Dec 2024)
    ],
    'title' => [
        'pattern' => '{title} | {site_name}',
        'site_name' => env('APP_NAME'),
        'max_length' => 60,
    ],
    // ... more configuration
];
```

### Environment Variables

```env
# Basic settings
SEO_ENABLED=true
SEO_MODE=hybrid
SEO_CACHE_ENABLED=true

# AI Provider
SEO_AI_PROVIDER=openai
SEO_AI_API_KEY=your-api-key-here
SEO_AI_MODEL=gpt-4o-mini

# Title settings
SEO_TITLE_PATTERN="{title} | {site_name}"
SEO_SITE_NAME="My Website"

# Social media
SEO_OG_SITE_NAME="My Website"
SEO_TWITTER_SITE="@mywebsite"
```

## 🤖 AI Providers

### OpenAI (GPT-4/5)

```php
$config = new SeoConfig([
    'ai' => [
        'provider' => 'openai',
        'api_key' => 'your-openai-api-key',
        'model' => 'gpt-4o-mini', // Best cost/performance (Dec 2024)
    ],
]);
```

### Anthropic (Claude)

```php
$config = new SeoConfig([
    'ai' => [
        'provider' => 'anthropic',
        'api_key' => 'your-anthropic-api-key',
        'model' => 'claude-3-5-sonnet-20241022', // Latest Claude 3.5 Sonnet (Oct 2024)
    ],
]);
```

### Google (Gemini)

```php
$config = new SeoConfig([
    'ai' => [
        'provider' => 'google',
        'api_key' => 'your-google-api-key',
        'model' => 'gemini-1.5-flash', // Latest Gemini 1.5 Flash (Dec 2024)
    ],
]);
```

### Local AI (Ollama)

```php
$config = new SeoConfig([
    'ai' => [
        'provider' => 'ollama',
        'api_url' => 'http://localhost:11434',
        'model' => 'llama2',
    ],
]);
```

### Multiple Providers (Fallback)

```php
$config = new SeoConfig([
    'ai' => [
        'provider' => 'openai',
        'api_key' => 'primary-key',
        'fallback_providers' => [
            ['provider' => 'anthropic', 'api_key' => 'backup-key'],
            ['provider' => 'ollama', 'api_url' => 'http://localhost:11434'],
        ],
    ],
]);
```

## 🎯 Advanced Features

### Fluent Interface

The package supports method chaining for elegant, readable code:

```php
// Chain analyze() with generation methods
$title = $seo->analyze($content, $metadata)->generateTitle();
$description = $seo->analyze($content)->generateDescription();

// Chain all SEO generation
$seoData = $seo
    ->analyze($content, ['title' => 'My Page'])
    ->generateAll();

// Chain configuration methods
$seo = (new SeoManager())
    ->setPageData($customData)
    ->setCache($cacheImplementation);

// Schema classes support fluent interface
$article = (new ArticleSchema())
    ->setHeadline('My Article Title')
    ->setDescription('Article description')
    ->setAuthor('John Doe')
    ->setDatePublished('2024-01-15T10:00:00Z')
    ->setImage('https://example.com/image.jpg');

// All setter methods return $this for chaining
$breadcrumb = (new BreadcrumbListSchema())
    ->addItem('Home', '/', 1)
    ->addItem('Blog', '/blog', 2)
    ->addItem('Article', '/blog/article', 3);
```

**Available Chainable Methods:**

- `analyze(string $content, array $metadata = []): self`
- `setPageData(array $data): self`
- `setCache(CacheInterface $cache): self`
- `withConfig(SeoConfig $config): self`
- All Schema setter methods return `static` for chaining

### Custom Analyzers

```php
use Rumenx\PhpSeo\Contracts\AnalyzerInterface;

class CustomContentAnalyzer implements AnalyzerInterface
{
    public function analyze(string $content, array $metadata = []): array
    {
        // Your custom analysis logic
        return [
            'custom_data' => $this->extractCustomData($content),
            // ... other data
        ];
    }

    public function supports(string $contentType): bool
    {
        return $contentType === 'custom/format';
    }
}

// Use your custom analyzer
$seo = new SeoManager(
    config: $config,
    contentAnalyzer: new CustomContentAnalyzer()
);
```

### Custom Generators

```php
use Rumenx\PhpSeo\Contracts\GeneratorInterface;

class CustomTitleGenerator implements GeneratorInterface
{
    public function generate(array $pageData): string
    {
        // Your custom generation logic
        return $this->createCustomTitle($pageData);
    }

    public function generateCustom(mixed $customInput, array $pageData = []): string
    {
        // Handle custom input
        return $this->processCustomTitle($customInput, $pageData);
    }

    public function supports(string $type): bool
    {
        return $type === 'title';
    }
}
```

### Batch Processing

```php
$posts = Post::all();
$seoResults = [];

foreach ($posts as $post) {
    $content = $post->getContent();
    $metadata = [
        'title' => $post->getTitle(),
        'url' => $post->getUrl(),
        'author' => $post->getAuthor(),
    ];

    $seoResults[$post->getId()] = $seo
        ->analyze($content, $metadata)
        ->generateAll();
}
```

## 🧪 Testing & Development

### Running Tests

```bash
# Run all tests (453 tests, 1247 assertions - 100% passing! ✅)
composer test

# Run tests with coverage
composer test-coverage

# Generate HTML coverage report
composer test-coverage-html

# Run specific test
./vendor/bin/pest tests/Unit/SeoManagerTest.php

# Run specific test group
./vendor/bin/pest --filter="OllamaProvider"

# Run with verbose output
./vendor/bin/pest --verbose
```

### Test Coverage

The package maintains **100% test pass rate** with comprehensive test coverage:

- ✅ **453 passing tests** across all components
- ✅ **1247 assertions** ensuring code quality
- ✅ **Unit tests** for all core functionality
- ✅ **Integration tests** for Laravel and Symfony
- ✅ **AI Provider tests** for OpenAI, Anthropic, Google, xAI, and Ollama
- ✅ **Generator tests** for titles, descriptions, and meta tags
- ✅ **Analyzer tests** for content processing
- ✅ **Validator tests** for response validation

### Code Quality

```bash
# Check coding standards
composer style

# Fix coding standards
composer style-fix

# Run static analysis
composer analyze

# Run all quality checks
composer quality
```

## 📚 Documentation

- [Configuration Reference](https://github.com/RumenDamyanov/php-seo/wiki/Configuration)
- [AI Integration Guide](https://github.com/RumenDamyanov/php-seo/wiki/AI-Integration)
- [Laravel Examples](https://github.com/RumenDamyanov/php-seo/wiki/Laravel-Integration)
- [Symfony Examples](https://github.com/RumenDamyanov/php-seo/wiki/Symfony-Integration)
- [Plain PHP Examples](https://github.com/RumenDamyanov/php-seo/wiki/Plain-PHP-Examples)
- [Basic Examples](https://github.com/RumenDamyanov/php-seo/wiki/Basic-Examples)
- [Home](https://github.com/RumenDamyanov/php-seo/wiki/Home)

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

- 🐛 [Report issues](https://github.com/RumenDamyanov/php-seo/issues)
- 💡 [Request features](https://github.com/RumenDamyanov/php-seo/issues/new?template=feature_request.md)
- 🔧 [Submit pull requests](https://github.com/RumenDamyanov/php-seo/pulls)

## 🔒 Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md) for responsible disclosure guidelines.

## 💝 Support

If you find this package helpful, consider:

- ⭐ [Starring the repository](https://github.com/RumenDamyanov/php-seo)
- 💝 [Supporting development](FUNDING.md)
- 🐛 [Reporting issues](https://github.com/RumenDamyanov/php-seo/issues)
- 🤝 [Contributing improvements](CONTRIBUTING.md)

## 📄 License

[MIT License](LICENSE.md). Please see the license file for more information.

## 🏆 About

**php-seo** is created and maintained by [Rumen Damyanov](https://github.com/RumenDamyanov). It aims to bring the same level of quality and ease-of-use to SEO optimization.

### SEO Package Family

This package is part of a multi-language SEO ecosystem:

- **[@rumenx/seo](https://www.npmjs.com/package/@rumenx/seo)** - JavaScript/TypeScript SEO package for Node.js and browsers
  - 📦 [NPM Package](https://www.npmjs.com/package/@rumenx/seo)
  - 💻 [GitHub Repository](https://github.com/RumenDamyanov/npm-seo)
  - ⚡ Framework-agnostic with React, Vue, Angular integrations
  - 🚀 Works in both Node.js and browser environments

- **php-seo** (this package) - PHP SEO package for Laravel, Symfony, and any PHP project
  - 🐘 PHP 8.2+ with full type safety
  - 🤖 AI-powered content generation
  - 🏗️ Framework-agnostic with Laravel/Symfony integrations

- **go-seo** (planned) - Go SEO package
  - 🚀 High-performance SEO optimization for Go applications
  - Coming soon!

All packages share similar APIs and best practices, making it easy to work across different tech stacks.

### Related Projects

- [php-chatbot](https://github.com/RumenDamyanov/php-chatbot) - AI powered chatbot package
- [php-sitemap](https://github.com/RumenDamyanov/php-sitemap) - Framework-agnostic sitemap generation
- [php-feed](https://github.com/RumenDamyanov/php-feed) - Framework-agnostic rss feed generation
- [php-geolocation](https://github.com/RumenDamyanov/php-geolocation) - Framework-agnostic geolocation detection and handling for PHP
- More projects coming soon!

---

**Framework-agnostic AI-powered SEO optimization for modern PHP applications.**
