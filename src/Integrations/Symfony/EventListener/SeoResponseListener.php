<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Integrations\Symfony\EventListener;

use Rumenx\PhpSeo\SeoManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Event listener for automatic SEO processing in Symfony responses.
 */
class SeoResponseListener
{
    public function __construct(
        private readonly SeoManager $seoManager
    ) {
    }

    /**
     * Handle the kernel response event.
     *
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        if (!$this->shouldProcessResponse($response)) {
            return;
        }

        $content = $response->getContent();
        if (!$content) {
            return;
        }

        $metadata = $this->extractMetadataFromRequest($request);

        $seoData = $this->seoManager
            ->analyze($content, $metadata)
            ->generateAll();

        $processedContent = $this->injectSeoTags($content, $seoData);
        $response->setContent($processedContent);
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
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array<string, mixed>
     */
    private function extractMetadataFromRequest($request): array
    {
        return [
            'url' => $request->getUri(),
            'path' => $request->getPathInfo(),
            'route' => $request->attributes->get('_route'),
            'method' => $request->getMethod(),
            'user_agent' => $request->headers->get('User-Agent'),
            'ip' => $request->getClientIp(),
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
