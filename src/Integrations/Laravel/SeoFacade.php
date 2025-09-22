<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Integrations\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * Laravel facade for the SEO manager.
 *
 * @method static \Rumenx\PhpSeo\SeoManager analyze(string $content, array $metadata = [])
 * @method static string generateTitle(string|null $customTitle = null)
 * @method static string generateDescription(string|null $customDescription = null)
 * @method static array generateMetaTags(array $customMeta = [])
 * @method static array generateAll(array $overrides = [])
 * @method static string renderMetaTags(array|null $seoData = null)
 * @method static \Rumenx\PhpSeo\Config\SeoConfig getConfig()
 * @method static array getPageData()
 * @method static \Rumenx\PhpSeo\SeoManager setPageData(array $data)
 * @method static \Rumenx\PhpSeo\SeoManager withConfig(\Rumenx\PhpSeo\Config\SeoConfig $config)
 *
 * @see \Rumenx\PhpSeo\SeoManager
 */
class SeoFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'seo';
    }
}
