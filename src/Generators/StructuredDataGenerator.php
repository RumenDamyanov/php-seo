<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Generators;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\GeneratorInterface;
use Rumenx\PhpSeo\Schema\ArticleSchema;
use Rumenx\PhpSeo\Schema\BaseSchema;
use Rumenx\PhpSeo\Schema\BreadcrumbListSchema;
use Rumenx\PhpSeo\Schema\OrganizationSchema;
use Rumenx\PhpSeo\Schema\WebPageSchema;

/**
 * Generator for Schema.org structured data.
 *
 * Creates JSON-LD structured data for improved search engine understanding
 * of page content and context.
 */
class StructuredDataGenerator implements GeneratorInterface
{
    private SeoConfig $config;

    public function __construct(SeoConfig $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<BaseSchema>
     */
    public function generate(array $pageData): array
    {
        $schemas = [];

        // Determine content type and generate appropriate schema
        $contentType = $pageData['content_type'] ?? 'text/html';
        $pageType = $this->detectPageType($pageData);

        $articleEnabled = (bool) $this->config->get('structured_data.types.article', true);
        $webpageEnabled = (bool) $this->config->get('structured_data.types.webpage', true);

        if ($pageType === 'article' && $articleEnabled) {
            $schemas[] = $this->generateArticleSchema($pageData);
        } elseif ($webpageEnabled) {
            $schemas[] = $this->generateWebPageSchema($pageData);
        }

        // Add organization schema if configured
        if ($this->config->get('structured_data.types.organization', false)) {
            $orgSchema = $this->generateOrganizationSchema();
            if ($orgSchema !== null) {
                $schemas[] = $orgSchema;
            }
        }

        // Add breadcrumb schema if data is available
        if (!empty($pageData['breadcrumbs']) && $this->config->get('structured_data.types.breadcrumb', true)) {
            $schemas[] = $this->generateBreadcrumbSchema($pageData['breadcrumbs']);
        }

        return $schemas;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCustom(mixed $customInput, array $pageData = []): string
    {
        if (!$customInput instanceof BaseSchema) {
            throw new \InvalidArgumentException('Custom structured data input must be a BaseSchema instance');
        }

        return $customInput->toJson();
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'structured_data';
    }

    /**
     * Generate Article schema.
     *
     * @param array<string, mixed> $pageData
     * @return ArticleSchema
     */
    private function generateArticleSchema(array $pageData): ArticleSchema
    {
        $schema = new ArticleSchema();

        // Headline
        if (!empty($pageData['metadata']['title'])) {
            $schema->setHeadline($pageData['metadata']['title']);
        } elseif (!empty($pageData['headings'][0]['text'])) {
            $schema->setHeadline($pageData['headings'][0]['text']);
        }

        // Description
        if (!empty($pageData['summary'])) {
            $schema->setDescription($pageData['summary']);
        }

        // Image
        if (!empty($pageData['images'][0]['src'])) {
            $schema->setImage($pageData['images'][0]['src']);
        }

        // Dates
        if (!empty($pageData['metadata']['published_date'])) {
            $schema->setDatePublished($pageData['metadata']['published_date']);
        }

        if (!empty($pageData['metadata']['modified_date'])) {
            $schema->setDateModified($pageData['metadata']['modified_date']);
        }

        // Author
        if (!empty($pageData['metadata']['author'])) {
            $schema->setAuthor($pageData['metadata']['author']);
        }

        // Publisher
        $publisherName = $this->config->get('structured_data.publisher.name');
        if ($publisherName !== null) {
            $publisherLogo = $this->config->get('structured_data.publisher.logo');
            $publisher = [
                '@type' => 'Organization',
                'name' => $publisherName,
            ];

            if ($publisherLogo !== null) {
                $publisher['logo'] = [
                    '@type' => 'ImageObject',
                    'url' => $publisherLogo,
                ];
            }

            $schema->setPublisher($publisher);
        }

        // Word count
        if (!empty($pageData['word_count'])) {
            $schema->setWordCount($pageData['word_count']);
        }

        return $schema;
    }

    /**
     * Generate WebPage schema.
     *
     * @param array<string, mixed> $pageData
     * @return WebPageSchema
     */
    private function generateWebPageSchema(array $pageData): WebPageSchema
    {
        $schema = new WebPageSchema();

        // Name/Title
        if (!empty($pageData['metadata']['title'])) {
            $schema->setName($pageData['metadata']['title']);
        }

        // Description
        if (!empty($pageData['summary'])) {
            $schema->setDescription($pageData['summary']);
        }

        // URL
        if (!empty($pageData['metadata']['url'])) {
            $schema->setUrl($pageData['metadata']['url']);
        }

        // Image
        if (!empty($pageData['images'][0]['src'])) {
            $schema->setImage($pageData['images'][0]['src']);
        }

        return $schema;
    }

    /**
     * Generate Organization schema.
     *
     * @return OrganizationSchema|null
     */
    private function generateOrganizationSchema(): ?OrganizationSchema
    {
        $name = $this->config->get('structured_data.organization.name');

        if ($name === null) {
            return null;
        }

        $schema = new OrganizationSchema();
        $schema->setName($name);

        // URL
        $url = $this->config->get('structured_data.organization.url');
        if ($url !== null) {
            $schema->setUrl($url);
        }

        // Logo
        $logo = $this->config->get('structured_data.organization.logo');
        if ($logo !== null) {
            $schema->setLogo($logo);
        }

        // Social media
        $sameAs = $this->config->get('structured_data.organization.social_media', []);
        if (!empty($sameAs) && is_array($sameAs)) {
            $schema->setSameAs($sameAs);
        }

        return $schema;
    }

    /**
     * Generate Breadcrumb schema.
     *
     * @param array<array<string, mixed>> $breadcrumbs
     * @return BreadcrumbListSchema
     */
    private function generateBreadcrumbSchema(array $breadcrumbs): BreadcrumbListSchema
    {
        $schema = new BreadcrumbListSchema();

        foreach ($breadcrumbs as $position => $item) {
            $schema->addItem(
                $item['name'],
                $item['url'],
                $position + 1
            );
        }

        return $schema;
    }

    /**
     * Detect page type from page data.
     *
     * @param array<string, mixed> $pageData
     * @return string
     */
    private function detectPageType(array $pageData): string
    {
        // Check metadata first
        if (!empty($pageData['metadata']['type'])) {
            return strtolower($pageData['metadata']['type']);
        }

        // Check if it looks like an article
        if (
            !empty($pageData['metadata']['author'])
            || !empty($pageData['metadata']['published_date'])
            || $pageData['word_count'] > 300
        ) {
            return 'article';
        }

        return 'webpage';
    }
}
