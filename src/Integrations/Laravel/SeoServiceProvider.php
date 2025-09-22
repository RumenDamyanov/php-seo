<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Integrations\Laravel;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\SeoManager;

/**
 * Laravel service provider for the PHP SEO package.
 */
class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../../config/seo.php', 'seo');

        $this->app->singleton(SeoConfig::class, function ($app) {
            return new SeoConfig($app['config']['seo']);
        });

        $this->app->singleton(SeoManager::class, function ($app) {
            return new SeoManager($app[SeoConfig::class]);
        });

        $this->app->alias(SeoManager::class, 'seo');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../../config/seo.php' => config_path('seo.php'),
            ], 'seo-config');
        }

        $this->registerBladeDirectives();
        $this->registerViewComposer();
    }

    /**
     * Register Blade directives for SEO.
     */
    private function registerBladeDirectives(): void
    {
        if (!config('seo.laravel.blade_directives', true)) {
            return;
        }

        Blade::directive('seoTitle', function ($expression) {
            return "<?php echo app('seo')->generateTitle({$expression}); ?>";
        });

        Blade::directive('seoDescription', function ($expression) {
            return "<?php echo app('seo')->generateDescription({$expression}); ?>";
        });

        Blade::directive('seoMeta', function ($expression) {
            return "<?php echo app('seo')->renderMetaTags({$expression}); ?>";
        });

        Blade::directive('seoAnalyze', function ($expression) {
            return "<?php app('seo')->analyze({$expression}); ?>";
        });
    }

    /**
     * Register view composer for automatic SEO injection.
     */
    private function registerViewComposer(): void
    {
        $this->app['view']->composer('*', function ($view) {
            if (!$view->offsetExists('seo')) {
                $view->with('seo', $this->app[SeoManager::class]);
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            SeoConfig::class,
            SeoManager::class,
            'seo',
        ];
    }
}
