<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Integrations\Symfony\Twig;

use Rumenx\PhpSeo\SeoManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension for SEO functionality.
 */
class SeoExtension extends AbstractExtension
{
    public function __construct(
        private readonly SeoManager $seoManager
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('seo_title', [$this, 'generateTitle']),
            new TwigFunction('seo_description', [$this, 'generateDescription']),
            new TwigFunction('seo_meta_tags', [$this, 'renderMetaTags'], ['is_safe' => ['html']]),
            new TwigFunction('seo_analyze', [$this, 'analyze']),
            new TwigFunction('seo_generate_all', [$this, 'generateAll']),
        ];
    }

    /**
     * Generate an SEO title.
     *
     * @param string|null $customTitle
     * @return string
     */
    public function generateTitle(?string $customTitle = null): string
    {
        return $this->seoManager->generateTitle($customTitle);
    }

    /**
     * Generate an SEO description.
     *
     * @param string|null $customDescription
     * @return string
     */
    public function generateDescription(?string $customDescription = null): string
    {
        return $this->seoManager->generateDescription($customDescription);
    }

    /**
     * Render HTML meta tags.
     *
     * @param array<string, mixed>|null $seoData
     * @return string
     */
    public function renderMetaTags(?array $seoData = null): string
    {
        return $this->seoManager->renderMetaTags($seoData);
    }

    /**
     * Analyze content for SEO.
     *
     * @param string $content
     * @param array<string, mixed> $metadata
     * @return SeoManager
     */
    public function analyze(string $content, array $metadata = []): SeoManager
    {
        return $this->seoManager->analyze($content, $metadata);
    }

    /**
     * Generate all SEO data.
     *
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public function generateAll(array $overrides = []): array
    {
        return $this->seoManager->generateAll($overrides);
    }
}
