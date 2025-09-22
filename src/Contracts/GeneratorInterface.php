<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Contracts;

/**
 * Interface for SEO content generators.
 *
 * Generators create SEO-optimized content such as titles,
 * descriptions, and meta tags from analyzed page data.
 */
interface GeneratorInterface
{
    /**
     * Generate SEO content from the given page data.
     *
     * @param array<string, mixed> $pageData
     * @return mixed
     */
    public function generate(array $pageData): mixed;

    /**
     * Generate custom SEO content with user-provided input.
     *
     * @param mixed $customInput
     * @param array<string, mixed> $pageData
     * @return mixed
     */
    public function generateCustom(mixed $customInput, array $pageData = []): mixed;

    /**
     * Check if the generator supports the given content type.
     *
     * @param string $type
     * @return bool
     */
    public function supports(string $type): bool;
}
