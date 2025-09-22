<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rumenx\PhpSeo\Integrations\Laravel\SeoServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SeoServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configure SEO package for testing
        $app['config']->set('seo.enabled', true);
        $app['config']->set('seo.mode', 'manual');
        $app['config']->set('seo.cache_enabled', false);
        $app['config']->set('seo.ai.provider', 'openai');
        $app['config']->set('seo.logging.enabled', false);
    }
}
