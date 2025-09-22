<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo;

use Rumenx\PhpSeo\Analyzers\ContentAnalyzer;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Generators\DescriptionGenerator;
use Rumenx\PhpSeo\Generators\MetaTagGenerator;
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
    private ContentAnalyzer $contentAnalyzer;
    private TitleGenerator $titleGenerator;
    private DescriptionGenerator $descriptionGenerator;
    private MetaTagGenerator $metaTagGenerator;

    /**
     * @var array<string, mixed>
     */
    private array $pageData = [];

    public function __construct(
        ?SeoConfig $config = null,
        ?ContentAnalyzer $contentAnalyzer = null,
        ?TitleGenerator $titleGenerator = null,
        ?DescriptionGenerator $descriptionGenerator = null,
        ?MetaTagGenerator $metaTagGenerator = null
    ) {
        $this->config = $config ?? new SeoConfig();
        $this->contentAnalyzer = $contentAnalyzer ?? new ContentAnalyzer($this->config);
        $this->titleGenerator = $titleGenerator ?? new TitleGenerator($this->config);
        $this->descriptionGenerator = $descriptionGenerator ?? new DescriptionGenerator($this->config);
        $this->metaTagGenerator = $metaTagGenerator ?? new MetaTagGenerator($this->config);
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
        return new self(
            $config,
            new ContentAnalyzer($config),
            new TitleGenerator($config),
            new DescriptionGenerator($config),
            new MetaTagGenerator($config)
        );
    }
}
