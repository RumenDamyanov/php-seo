<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\Symfony;

use PHPUnit\Framework\TestCase;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Integrations\Symfony\EventListener\SeoResponseListener;
use Rumenx\PhpSeo\SeoManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test cases for Symfony SeoResponseListener.
 */
class SeoResponseListenerTest extends TestCase
{
    private SeoResponseListener $listener;
    private SeoManager $seoManager;
    private SeoConfig $config;

    protected function setUp(): void
    {
        $this->config = new SeoConfig();
        $this->seoManager = new SeoManager($this->config);
        $this->listener = new SeoResponseListener($this->seoManager);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SeoResponseListener::class, $this->listener);
    }

    public function test_listener_accepts_seo_manager(): void
    {
        $listener = new SeoResponseListener($this->seoManager);
        $this->assertInstanceOf(SeoResponseListener::class, $listener);
    }

    public function test_listener_ignores_sub_requests(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><head></head><body>Test</body></html>');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $response);

        $originalContent = $response->getContent();
        $this->listener->onKernelResponse($event);

        // Content should remain unchanged for sub-requests
        $this->assertEquals($originalContent, $response->getContent());
    }

    public function test_listener_processes_main_requests(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><head></head><body><h1>Test Title</h1><p>Test content</p></body></html>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        // Content should be modified with SEO tags
        $this->assertStringContainsString('<title>', $response->getContent());
        $this->assertStringContainsString('<meta', $response->getContent());
    }

    public function test_listener_skips_non_200_responses(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><head></head><body>Not Found</body></html>', 404);
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $originalContent = $response->getContent();
        $this->listener->onKernelResponse($event);

        // Content should remain unchanged for non-200 responses
        $this->assertEquals($originalContent, $response->getContent());
    }

    public function test_listener_skips_non_html_responses(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('{"data": "test"}', 200);
        $response->headers->set('Content-Type', 'application/json');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $originalContent = $response->getContent();
        $this->listener->onKernelResponse($event);

        // Content should remain unchanged for non-HTML responses
        $this->assertEquals($originalContent, $response->getContent());
    }

    public function test_listener_processes_html_without_explicit_content_type(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><head></head><body><h1>Test Title</h1></body></html>');
        // No content-type header set

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        // Content should be processed when no content-type is set
        $this->assertStringContainsString('<title>', $response->getContent());
    }

    public function test_listener_handles_empty_response_content(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response(''); // Empty content
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        // Empty content should remain empty
        $this->assertEquals('', $response->getContent());
    }

    public function test_listener_injects_seo_tags_in_head(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><head><title>Original</title></head><body><h1>Test Title</h1></body></html>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $content = $response->getContent();

        // Should contain original content plus new SEO tags
        $this->assertStringContainsString('<title>Original</title>', $content);
        $this->assertStringContainsString('<meta', $content);
        $this->assertStringContainsString('<head>', $content);
    }

    public function test_listener_injects_before_closing_head_as_fallback(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><title>Test</title></head><body><h1>Title</h1></body></html>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $content = $response->getContent();

        // Should inject before </head>
        $this->assertStringContainsString('<meta', $content);
        $this->assertStringContainsString('</head>', $content);

        // Meta tags should come before </head>
        $headClosePos = strpos($content, '</head>');
        $metaPos = strpos($content, '<meta');
        $this->assertLessThan($headClosePos, $metaPos);
    }

    public function test_listener_injects_at_beginning_as_last_resort(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<div><h1>Title</h1><p>Content without head tags</p></div>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $content = $response->getContent();

        // Should inject at the beginning
        $this->assertStringStartsWith('<title>', $content);
        $this->assertStringContainsString('<meta', $content);
        $this->assertStringContainsString('<div><h1>Title</h1>', $content);
    }

    public function test_listener_extracts_metadata_from_request(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], ['_route' => 'test_route'], [], [], [
            'REQUEST_URI' => '/test/path',
            'REQUEST_METHOD' => 'GET',
            'HTTP_USER_AGENT' => 'Test Agent',
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $request->server->set('REQUEST_URI', '/test/path');

        $response = new Response('<html><head></head><body><h1>Test</h1></body></html>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        // Should process without errors and include metadata
        $this->assertStringContainsString('<title>', $response->getContent());
    }

    public function test_listener_handles_complex_html_structure(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $complexHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Original Title</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>Navigation</nav>
    </header>
    <main>
        <h1>Main Heading</h1>
        <p>Main content paragraph with important information.</p>
        <article>
            <h2>Article Title</h2>
            <p>Article content goes here.</p>
        </article>
    </main>
    <footer>Footer content</footer>
</body>
</html>';

        $response = new Response($complexHtml);
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $content = $response->getContent();

        // Should preserve structure and add SEO tags
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('<title>Original Title</title>', $content);
        $this->assertStringContainsString('<meta', $content);
        $this->assertStringContainsString('Main content paragraph', $content);
    }

    public function test_listener_works_with_different_content_types(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();

        // Test various HTML content types that should be processed
        $processedContentTypes = [
            'text/html',
            'text/html; charset=UTF-8',
            'text/html; charset=utf-8',
        ];

        foreach ($processedContentTypes as $contentType) {
            $response = new Response('<html><head></head><body><h1>Test</h1></body></html>');
            $response->headers->set('Content-Type', $contentType);

            $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

            $originalContent = $response->getContent();
            $this->listener->onKernelResponse($event);

            // Should be processed
            $this->assertNotEquals($originalContent, $response->getContent());
            $this->assertStringContainsString('<title>', $response->getContent());
        }

        // Test content types that should NOT be processed
        $ignoredContentTypes = [
            'application/json',
            'application/xml',
            'text/plain',
            'application/xhtml+xml', // This is not processed by the current implementation
        ];

        foreach ($ignoredContentTypes as $contentType) {
            $response = new Response('<html><head></head><body><h1>Test</h1></body></html>');
            $response->headers->set('Content-Type', $contentType);

            $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

            $originalContent = $response->getContent();
            $this->listener->onKernelResponse($event);

            // Should NOT be processed
            $this->assertEquals($originalContent, $response->getContent());
        }
    }

    public function test_listener_preserves_existing_content(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><head><script>alert("test");</script></head><body><h1>Test</h1><p>Existing content</p></body></html>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        $content = $response->getContent();

        // Should preserve existing content
        $this->assertStringContainsString('<script>alert("test");</script>', $content);
        $this->assertStringContainsString('Existing content', $content);
        $this->assertStringContainsString('<title>', $content);
        $this->assertStringContainsString('<meta', $content);
    }

    public function test_listener_handles_request_without_route(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request(); // No route attribute
        $response = new Response('<html><head></head><body><h1>Test</h1></body></html>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        $this->listener->onKernelResponse($event);

        // Should work even without route information
        $this->assertStringContainsString('<title>', $response->getContent());
    }

    public function test_listener_method_visibility(): void
    {
        $reflection = new \ReflectionClass($this->listener);

        // Public methods
        $this->assertTrue($reflection->getMethod('onKernelResponse')->isPublic());

        // Private methods
        $this->assertTrue($reflection->getMethod('shouldProcessResponse')->isPrivate());
        $this->assertTrue($reflection->getMethod('extractMetadataFromRequest')->isPrivate());
        $this->assertTrue($reflection->getMethod('injectSeoTags')->isPrivate());
    }

    public function test_listener_constructor_signature(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertEquals(1, $constructor->getNumberOfParameters());

        $parameter = $constructor->getParameters()[0];
        $this->assertEquals('seoManager', $parameter->getName());

        $parameterType = $parameter->getType();
        $this->assertNotNull($parameterType);
        $this->assertEquals(SeoManager::class, $parameterType->getName());
    }

    public function test_listener_handles_multiple_events(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request();
        $response = new Response('<html><head></head><body><h1>Test</h1></body></html>');
        $response->headers->set('Content-Type', 'text/html');

        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // Process multiple times
        $this->listener->onKernelResponse($event);
        $firstResult = $response->getContent();

        $this->listener->onKernelResponse($event);
        $secondResult = $response->getContent();

        // Second processing should work on already processed content
        $this->assertStringContainsString('<title>', $firstResult);
        $this->assertStringContainsString('<title>', $secondResult);
    }
}
