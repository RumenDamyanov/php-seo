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
- **Multi-Provider Support**: Integration with popular AI models (GPT-4/5, Claude, Gemini, Grok, Ollama)

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
- **Extensible**: Plugin architecture for custom analyzers and generators
- **High Performance**: Optimized for speed with caching and lazy loading
- **100% Test Coverage**: Comprehensive test suite using Pest

## 📖 Quick Links

- 🚀 [Installation](#installation)
- 💡 [Usage Examples](#usage-examples)
- ⚙️ [Configuration](#configuration)
- 🤖 [AI Providers](#ai-providers)
- 🧪 [Testing & Development](#testing--development)
- 🤝 [Contributing](CONTRIBUTING.md)
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
        'model' => 'gpt-4-turbo-preview',
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
SEO_AI_MODEL=gpt-4-turbo-preview

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
        'model' => 'gpt-4-turbo-preview',
    ],
]);
```

### Anthropic (Claude)

```php
$config = new SeoConfig([
    'ai' => [
        'provider' => 'anthropic',
        'api_key' => 'your-anthropic-api-key',
        'model' => 'claude-3-sonnet-20240229',
    ],
]);
```

### Google (Gemini)

```php
$config = new SeoConfig([
    'ai' => [
        'provider' => 'google',
        'api_key' => 'your-google-api-key',
        'model' => 'gemini-pro',
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
# Run all tests
composer test

# Run tests with coverage
composer test-coverage

# Generate HTML coverage report
composer test-coverage-html

# Run specific test
./vendor/bin/pest tests/Unit/SeoManagerTest.php
```

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

- [Configuration Reference](wiki/Configuration)
- [AI Integration Guide](wiki/AI-Integration)
- [Laravel Examples](wiki/Laravel-Examples)
- [Symfony Examples](wiki/Symfony-Examples)
- [Plain PHP Examples](wiki/Plain-PHP-Examples)
- [Basic Examples](wiki/Basic-Examples)
- [Home](wiki/Home)

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

**php-seo** is created and maintained by [Rumen Damyanov](https://github.com/RumenDamyanov). It's inspired by the success of [php-sitemap](https://github.com/RumenDamyanov/php-sitemap) and aims to bring the same level of quality and ease-of-use to SEO optimization.

### Related Projects

- [php-chatbot](https://github.com/RumenDamyanov/php-chatbot) - AI powered chatbot package
- [php-sitemap](https://github.com/RumenDamyanov/php-sitemap) - Framework-agnostic sitemap generation
- [php-feed](https://github.com/RumenDamyanov/php-feed) - Framework-agnostic rss feed generation
- [php-calendar](https://github.com/RumenDamyanov/php-calendar) - Framework-agnostic calendar package
- [php-vcard](https://github.com/RumenDamyanov/php-vcard) - Framework-agnostic vcard generation
- More projects coming soon!

---

**Framework-agnostic AI-powered SEO optimization for modern PHP applications.**
