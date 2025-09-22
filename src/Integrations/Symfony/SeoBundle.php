<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Integrations\Symfony;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle for the PHP SEO package.
 */
class SeoBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    /**
     * Configure the container.
     */
    public function configure(ContainerConfigurator $container): void
    {
        $container->import('../Resources/config/services.yaml');
    }

    /**
     * Get the path to the bundle.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__, 2);
    }
}
