<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Integrations\Laravel;

use Closure;
use Illuminate\Http\Request;
use Rumenx\PhpSeo\SeoManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Laravel middleware for automatic SEO processing.
 */
class SeoMiddleware
{
    public function __construct(
        private readonly SeoManager $seoManager
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$this->shouldProcessResponse($response)) {
            return $response;
        }

        $content = $response->getContent();
        if (!$content) {
            return $response;
        }

        $metadata = $this->extractMetadataFromRequest($request);

        $seoData = $this->seoManager
            ->analyze($content, $metadata)
            ->generateAll();

        $processedContent = $this->injectSeoTags($content, $seoData);
        $response->setContent($processedContent);

        return $response;
    }

    /**
     * Determine if the response should be processed.
     *
     * @param Response $response
     * @return bool
     */
    private function shouldProcessResponse(Response $response): bool
    {
        // Only process successful HTML responses
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'text/html') || empty($contentType);
    }

    /**
     * Extract metadata from the request.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    private function extractMetadataFromRequest(Request $request): array
    {
        $route = $request->route();
        $routeName = null;

        if ($route !== null && method_exists($route, 'getName')) {
            $routeName = $route->getName();
        }

        return [
            'url' => $request->url(),
            'path' => $request->path(),
            'route' => $routeName,
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ];
    }

    /**
     * Inject SEO tags into the HTML content.
     *
     * @param string $content
     * @param array<string, mixed> $seoData
     * @return string
     */
    private function injectSeoTags(string $content, array $seoData): string
    {
        $seoTags = $this->seoManager->renderMetaTags($seoData);

        // Try to inject in <head> section
        if (preg_match('/<head[^>]*>/i', $content)) {
            return preg_replace(
                '/(<head[^>]*>)/i',
                '$1' . "\n" . $seoTags,
                $content,
                1
            );
        }

        // Fallback: inject before closing </head> tag
        if (preg_match('/<\/head>/i', $content)) {
            return preg_replace(
                '/(<\/head>)/i',
                $seoTags . "\n" . '$1',
                $content,
                1
            );
        }

        // Last resort: inject at the beginning of the content
        return $seoTags . "\n" . $content;
    }
}
