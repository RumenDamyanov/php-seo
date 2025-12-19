<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo;

use Psr\SimpleCache\CacheInterface;
use Rumenx\PhpSeo\Analyzers\ContentAnalyzer;
use Rumenx\PhpSeo\Analyzers\ImageAnalyzer;
use Rumenx\PhpSeo\Cache\SeoCache;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Generators\DescriptionGenerator;
use Rumenx\PhpSeo\Generators\ImageAltGenerator;
use Rumenx\PhpSeo\Generators\MetaTagGenerator;
use Rumenx\PhpSeo\Generators\StructuredDataGenerator;
use Rumenx\PhpSeo\Generators\TitleGenerator;

/**
 * Main SEO Manager class that orchestrates SEO optimization tasks.
 *
 * This class provides the primary interface for generating SEO-optimized
 * content including titles, descriptions, meta tags, and more using either
 * AI-powered analysis or manual configuration patterns.
 */
class SeoManager
{
    private SeoConfig $config;
    private ?SeoCache $cache = null;
    private ContentAnalyzer $contentAnalyzer;
    private ImageAnalyzer $imageAnalyzer;
    private TitleGenerator $titleGenerator;
    private DescriptionGenerator $descriptionGenerator;
    private MetaTagGenerator $metaTagGenerator;
    private ImageAltGenerator $imageAltGenerator;
    private StructuredDataGenerator $structuredDataGenerator;

    /**
     * @var array<string, mixed>
     */
    private array $pageData = [];

    public function __construct(
        ?SeoConfig $config = null,
        ?CacheInterface $cacheImplementation = null,
        ?ContentAnalyzer $contentAnalyzer = null,
        ?ImageAnalyzer $imageAnalyzer = null,
        ?TitleGenerator $titleGenerator = null,
        ?DescriptionGenerator $descriptionGenerator = null,
        ?MetaTagGenerator $metaTagGenerator = null,
        ?ImageAltGenerator $imageAltGenerator = null,
        ?StructuredDataGenerator $structuredDataGenerator = null
    ) {
        $this->config = $config ?? new SeoConfig();

        // Initialize cache if provided
        if ($cacheImplementation !== null) {
            $this->cache = new SeoCache($this->config, $cacheImplementation);
        }

        $this->imageAnalyzer = $imageAnalyzer ?? new ImageAnalyzer($this->config);
        $this->contentAnalyzer = $contentAnalyzer ?? new ContentAnalyzer($this->config, $this->imageAnalyzer, $this->cache);
        $this->titleGenerator = $titleGenerator ?? new TitleGenerator($this->config);
        $this->descriptionGenerator = $descriptionGenerator ?? new DescriptionGenerator($this->config);
        $this->metaTagGenerator = $metaTagGenerator ?? new MetaTagGenerator($this->config);
        $this->imageAltGenerator = $imageAltGenerator ?? new ImageAltGenerator($this->config);
        $this->structuredDataGenerator = $structuredDataGenerator ?? new StructuredDataGenerator($this->config);
    }

    /**
     * Analyze page content and set it for SEO generation.
     *
     * @param string $content The HTML or text content to analyze
     * @param array<string, mixed> $metadata Additional metadata about the page
     * @return self
     */
    public function analyze(string $content, array $metadata = []): self
    {
        $this->pageData = $this->contentAnalyzer->analyze($content, $metadata);

        return $this;
    }

    /**
     * Generate an optimized title for the current page.
     *
     * @param string|null $customTitle Custom title to use instead of generated one
     * @return string
     */
    public function generateTitle(?string $customTitle = null): string
    {
        if ($customTitle !== null) {
            return $this->titleGenerator->generateCustom($customTitle, $this->pageData);
        }

        return $this->titleGenerator->generate($this->pageData);
    }

    /**
     * Generate an optimized description for the current page.
     *
     * @param string|null $customDescription Custom description to use instead of generated one
     * @return string
     */
    public function generateDescription(?string $customDescription = null): string
    {
        if ($customDescription !== null) {
            return $this->descriptionGenerator->generateCustom($customDescription, $this->pageData);
        }

        return $this->descriptionGenerator->generate($this->pageData);
    }

    /**
     * Generate all meta tags for the current page.
     *
     * @param array<string, mixed> $customMeta Custom meta tags to merge with generated ones
     * @return array<string, string>
     */
    public function generateMetaTags(array $customMeta = []): array
    {
        $generated = $this->metaTagGenerator->generate($this->pageData);

        return array_merge($generated, $customMeta);
    }

    /**
     * Generate alt text for images in the current page.
     *
     * @return array<string, string> Map of image src to generated alt text
     */
    public function generateImageAltTexts(): array
    {
        if (empty($this->pageData['images'])) {
            return [];
        }

        return $this->imageAltGenerator->generateForImages(
            $this->pageData['images'],
            $this->pageData
        );
    }

    /**
     * Generate structured data for the current page.
     *
     * @return array<array<string, mixed>>
     */
    public function generateStructuredData(): array
    {
        if (!$this->config->get('structured_data.enabled', true)) {
            return [];
        }

        $schemas = $this->structuredDataGenerator->generate($this->pageData);

        return array_map(fn ($schema) => $schema->toArray(), $schemas);
    }

    /**
     * Get structured data as HTML script tags.
     *
     * @return string
     */
    public function renderStructuredData(): string
    {
        if (!$this->config->get('structured_data.enabled', true)) {
            return '';
        }

        $schemas = $this->structuredDataGenerator->generate($this->pageData);

        return implode("\n", array_map(fn ($schema) => $schema->toHtml(), $schemas));
    }

    /**
     * Generate complete SEO data for the current page.
     *
     * @param array<string, mixed> $overrides Custom values to override generated ones
     * @return array<string, mixed>
     */
    public function generateAll(array $overrides = []): array
    {
        $seoData = [
            'title' => $this->generateTitle($overrides['title'] ?? null),
            'description' => $this->generateDescription($overrides['description'] ?? null),
            'meta_tags' => $this->generateMetaTags($overrides['meta_tags'] ?? []),
            'image_alt_texts' => $this->generateImageAltTexts(),
            'structured_data' => $this->generateStructuredData(),
            'page_data' => $this->pageData,
        ];

        return array_merge($seoData, $overrides);
    }

    /**
     * Render HTML meta tags from generated SEO data.
     *
     * @param array<string, mixed> $seoData SEO data array (optional, uses generated if not provided)
     * @return string
     */
    public function renderMetaTags(?array $seoData = null): string
    {
        if ($seoData === null) {
            $seoData = $this->generateAll();
        }

        $html = '';

        // Title tag
        if (!empty($seoData['title'])) {
            $html .= sprintf('<title>%s</title>', htmlspecialchars($seoData['title'], ENT_QUOTES, 'UTF-8')) . "\n";
        }

        // Meta description
        if (!empty($seoData['description'])) {
            $html .= sprintf(
                '<meta name="description" content="%s">',
                htmlspecialchars($seoData['description'], ENT_QUOTES, 'UTF-8')
            ) . "\n";
        }

        // Other meta tags
        if (!empty($seoData['meta_tags'])) {
            foreach ($seoData['meta_tags'] as $name => $content) {
                if (str_starts_with($name, 'og:') || str_starts_with($name, 'twitter:')) {
                    $html .= sprintf(
                        '<meta property="%s" content="%s">',
                        htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
                    ) . "\n";
                } else {
                    $html .= sprintf(
                        '<meta name="%s" content="%s">',
                        htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
                    ) . "\n";
                }
            }
        }

        return trim($html);
    }

    /**
     * Get the current configuration.
     *
     * @return SeoConfig
     */
    public function getConfig(): SeoConfig
    {
        return $this->config;
    }

    /**
     * Get the current page data.
     *
     * @return array<string, mixed>
     */
    public function getPageData(): array
    {
        return $this->pageData;
    }

    /**
     * Set custom page data.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public function setPageData(array $data): self
    {
        $this->pageData = $data;

        return $this;
    }

    /**
     * Create a new instance with a different configuration.
     *
     * @param SeoConfig $config
     * @return self
     */
    public function withConfig(SeoConfig $config): self
    {
        // Note: Cache is not preserved when creating with new config.
        // Users should use setCache() after withConfig() if they want caching.
        $imageAnalyzer = new ImageAnalyzer($config);

        return new self(
            $config,
            null,
            new ContentAnalyzer($config, $imageAnalyzer, null),
            $imageAnalyzer,
            new TitleGenerator($config),
            new DescriptionGenerator($config),
            new MetaTagGenerator($config),
            new ImageAltGenerator($config),
            new StructuredDataGenerator($config)
        );
    }

    /**
     * Set a cache implementation.
     *
     * @param CacheInterface $cacheImplementation PSR-16 cache implementation
     * @return self
     */
    public function setCache(CacheInterface $cacheImplementation): self
    {
        $this->cache = new SeoCache($this->config, $cacheImplementation);

        // Update ContentAnalyzer with the new cache
        $this->contentAnalyzer = new ContentAnalyzer($this->config, $this->imageAnalyzer, $this->cache);

        return $this;
    }

    /**
     * Get the cache instance.
     *
     * @return SeoCache|null
     */
    public function getCache(): ?SeoCache
    {
        return $this->cache;
    }
}
