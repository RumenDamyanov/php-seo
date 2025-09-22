<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Generators;

use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Contracts\GeneratorInterface;

/**
 * Meta tag generator for creating comprehensive SEO meta tags.
 *
 * Generates various meta tags including Open Graph, Twitter Cards,
 * robots directives, and other SEO-relevant meta information.
 */
class MetaTagGenerator implements GeneratorInterface
{
    private SeoConfig $config;

    public function __construct(SeoConfig $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $pageData): array
    {
        $metaTags = [];

        // Add default meta tags
        $metaTags = array_merge($metaTags, $this->generateDefaultTags());

        // Add robots meta tags
        $metaTags = array_merge($metaTags, $this->generateRobotsTags($pageData));

        // Add Open Graph tags
        if ($this->config->get('meta_tags.open_graph.enabled', true)) {
            $metaTags = array_merge($metaTags, $this->generateOpenGraphTags($pageData));
        }

        // Add Twitter Card tags
        if ($this->config->get('meta_tags.twitter.enabled', true)) {
            $metaTags = array_merge($metaTags, $this->generateTwitterTags($pageData));
        }

        // Add additional SEO tags
        $metaTags = array_merge($metaTags, $this->generateSeoTags($pageData));

        return $metaTags;
    }

    /**
     * {@inheritdoc}
     */
    public function generateCustom(mixed $customInput, array $pageData = []): array
    {
        if (!is_array($customInput)) {
            throw new \InvalidArgumentException('Custom meta tags input must be an array');
        }

        $generated = $this->generate($pageData);

        return array_merge($generated, $customInput);
    }

    /**
     * {@inheritdoc}
     */
    public function supports(string $type): bool
    {
        return $type === 'meta_tags';
    }

    /**
     * Generate default meta tags.
     *
     * @return array<string, string>
     */
    private function generateDefaultTags(): array
    {
        return $this->config->get('meta_tags.default_tags', []);
    }

    /**
     * Generate robots meta tags.
     *
     * @param array<string, mixed> $pageData
     * @return array<string, string>
     */
    private function generateRobotsTags(array $pageData): array
    {
        $robotsConfig = $this->config->get('meta_tags.robots', []);
        $robotsDirectives = [];

        // Basic directives
        if ($robotsConfig['index'] ?? true) {
            $robotsDirectives[] = 'index';
        } else {
            $robotsDirectives[] = 'noindex';
        }

        if ($robotsConfig['follow'] ?? true) {
            $robotsDirectives[] = 'follow';
        } else {
            $robotsDirectives[] = 'nofollow';
        }

        // Additional directives
        if (!($robotsConfig['archive'] ?? true)) {
            $robotsDirectives[] = 'noarchive';
        }

        if (!($robotsConfig['snippet'] ?? true)) {
            $robotsDirectives[] = 'nosnippet';
        }

        if (!($robotsConfig['imageindex'] ?? true)) {
            $robotsDirectives[] = 'noimageindex';
        }

        return [
            'robots' => implode(', ', $robotsDirectives),
        ];
    }

    /**
     * Generate Open Graph meta tags.
     *
     * @param array<string, mixed> $pageData
     * @return array<string, string>
     */
    private function generateOpenGraphTags(array $pageData): array
    {
        $ogConfig = $this->config->get('meta_tags.open_graph', []);
        $tags = [];

        // Basic OG tags
        $tags['og:type'] = $ogConfig['type'] ?? 'website';
        $tags['og:locale'] = $ogConfig['locale'] ?? 'en_US';

        // Site name
        if (!empty($ogConfig['site_name'])) {
            $tags['og:site_name'] = $ogConfig['site_name'];
        }

        // Title from page data or metadata
        if (!empty($pageData['metadata']['title'])) {
            $tags['og:title'] = $pageData['metadata']['title'];
        } elseif (!empty($pageData['headings'])) {
            $h1 = $this->findHeadingByLevel($pageData['headings'], 1);
            if ($h1 !== null) {
                $tags['og:title'] = $h1['text'];
            }
        }

        // Description
        if (!empty($pageData['metadata']['description'])) {
            $tags['og:description'] = $pageData['metadata']['description'];
        } elseif (!empty($pageData['summary'])) {
            $tags['og:description'] = substr($pageData['summary'], 0, 200);
        }

        // URL
        if (!empty($pageData['metadata']['url'])) {
            $tags['og:url'] = $pageData['metadata']['url'];
        }

        // Image
        if (!empty($pageData['metadata']['image'])) {
            $tags['og:image'] = $pageData['metadata']['image'];

            if (!empty($pageData['metadata']['image_alt'])) {
                $tags['og:image:alt'] = $pageData['metadata']['image_alt'];
            }
        } elseif (!empty($pageData['images'])) {
            // Use first image from content
            $firstImage = $pageData['images'][0];
            if (!empty($firstImage['src'])) {
                $tags['og:image'] = $firstImage['src'];
                if (!empty($firstImage['alt'])) {
                    $tags['og:image:alt'] = $firstImage['alt'];
                }
            }
        }

        return $tags;
    }

    /**
     * Generate Twitter Card meta tags.
     *
     * @param array<string, mixed> $pageData
     * @return array<string, string>
     */
    private function generateTwitterTags(array $pageData): array
    {
        $twitterConfig = $this->config->get('meta_tags.twitter', []);
        $tags = [];

        // Card type
        $tags['twitter:card'] = $twitterConfig['card'] ?? 'summary_large_image';

        // Site and creator
        if (!empty($twitterConfig['site'])) {
            $tags['twitter:site'] = $twitterConfig['site'];
        }

        if (!empty($twitterConfig['creator'])) {
            $tags['twitter:creator'] = $twitterConfig['creator'];
        }

        // Title
        if (!empty($pageData['metadata']['title'])) {
            $tags['twitter:title'] = $pageData['metadata']['title'];
        }

        // Description
        if (!empty($pageData['metadata']['description'])) {
            $tags['twitter:description'] = $pageData['metadata']['description'];
        } elseif (!empty($pageData['summary'])) {
            $tags['twitter:description'] = substr($pageData['summary'], 0, 200);
        }

        // Image
        if (!empty($pageData['metadata']['image'])) {
            $tags['twitter:image'] = $pageData['metadata']['image'];

            if (!empty($pageData['metadata']['image_alt'])) {
                $tags['twitter:image:alt'] = $pageData['metadata']['image_alt'];
            }
        } elseif (!empty($pageData['images'])) {
            $firstImage = $pageData['images'][0];
            if (!empty($firstImage['src'])) {
                $tags['twitter:image'] = $firstImage['src'];
                if (!empty($firstImage['alt'])) {
                    $tags['twitter:image:alt'] = $firstImage['alt'];
                }
            }
        }

        return $tags;
    }

    /**
     * Generate additional SEO meta tags.
     *
     * @param array<string, mixed> $pageData
     * @return array<string, string>
     */
    private function generateSeoTags(array $pageData): array
    {
        $tags = [];

        // Language
        if (!empty($pageData['language'])) {
            $tags['language'] = $pageData['language'];
        }

        // Author
        if (!empty($pageData['metadata']['author'])) {
            $tags['author'] = $pageData['metadata']['author'];
        }

        // Keywords
        if (!empty($pageData['keywords'])) {
            $tags['keywords'] = implode(', ', array_slice($pageData['keywords'], 0, 10));
        }

        // Publication date
        if (!empty($pageData['metadata']['published_at'])) {
            $tags['article:published_time'] = $pageData['metadata']['published_at'];
        }

        // Modified date
        if (!empty($pageData['metadata']['updated_at'])) {
            $tags['article:modified_time'] = $pageData['metadata']['updated_at'];
        }

        // Category/Section
        if (!empty($pageData['metadata']['category'])) {
            $tags['article:section'] = $pageData['metadata']['category'];
        }

        // Canonical URL
        if (!empty($pageData['metadata']['canonical_url'])) {
            $tags['canonical'] = $pageData['metadata']['canonical_url'];
        }

        return $tags;
    }

    /**
     * Find heading by level.
     *
     * @param array<array<string, mixed>> $headings
     * @param int $level
     * @return array<string, mixed>|null
     */
    private function findHeadingByLevel(array $headings, int $level): ?array
    {
        foreach ($headings as $heading) {
            if ($heading['level'] === $level) {
                return $heading;
            }
        }

        return null;
    }
}
