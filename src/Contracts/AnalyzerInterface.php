<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Contracts;

/**
 * Interface for content analyzers.
 *
 * Analyzers are responsible for extracting and processing
 * content to prepare it for SEO generation.
 */
interface AnalyzerInterface
{
    /**
     * Analyze the given content and return structured data.
     *
     * @param string $content The content to analyze
     * @param array<string, mixed> $metadata Additional metadata
     * @return array<string, mixed> Analyzed data
     */
    public function analyze(string $content, array $metadata = []): array;

    /**
     * Check if the analyzer can handle the given content type.
     *
     * @param string $contentType
     * @return bool
     */
    public function supports(string $contentType): bool;
}
