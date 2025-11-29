<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Orchestra\Testbench\TestCase;
use Rumenx\PhpSeo\Integrations\Laravel\SeoMiddleware;
use Rumenx\PhpSeo\Integrations\Laravel\SeoServiceProvider;
use Rumenx\PhpSeo\SeoManager;

class SeoMiddlewareTest extends TestCase
{
    protected static $latestResponse;

    protected function getPackageProviders($app)
    {
        return [
            SeoServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('seo', [
            'title' => [
                'max_length' => 60,
                'site_name' => 'Test Site',
                'separator' => ' - ',
            ],
            'description' => [
                'max_length' => 160,
            ],
            'mode' => 'manual',
            'enabled' => true,
        ]);
    }

    public function test_middleware_can_be_instantiated()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $this->assertInstanceOf(SeoMiddleware::class, $middleware);
    }

    public function test_middleware_processes_html_response()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/test', 'GET');
        $htmlContent = '<html><head></head><body><h1>Test Page</h1><p>This is test content.</p></body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $processedContent = $processedResponse->getContent();
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringContainsString('<meta name="description"', $processedContent);
        $this->assertStringContainsString('Test Page', $processedContent);
    }

    public function test_middleware_injects_seo_tags_in_head()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/test', 'GET');
        $htmlContent = '<html><head><meta charset="utf-8"></head><body><h1>Test Title</h1></body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $processedContent = $processedResponse->getContent();

        // SEO tags should be injected and charset should be preserved
        $this->assertStringContainsString('<meta charset="utf-8">', $processedContent);
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringContainsString('<meta name="description"', $processedContent);
        $this->assertStringContainsString('Test Title', $processedContent);
    }

    public function test_middleware_injects_before_closing_head_tag_as_fallback()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/test', 'GET');
        // No opening <head> tag, only closing
        $htmlContent = '<html><meta charset="utf-8"></head><body><h1>Fallback Test</h1></body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $processedContent = $processedResponse->getContent();

        // SEO tags should be injected before the closing </head> tag
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringContainsString('</head>', $processedContent);
    }

    public function test_middleware_injects_at_beginning_as_last_resort()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/test', 'GET');
        // No head tags at all
        $htmlContent = '<html><body><h1>Last Resort Test</h1></body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $processedContent = $processedResponse->getContent();

        // SEO tags should be injected at the beginning
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringStartsWith('<title>', trim($processedContent));
    }

    public function test_middleware_skips_non_html_responses()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/api/test', 'GET');
        $jsonContent = '{"message": "test"}';
        $response = new Response($jsonContent, 200, ['Content-Type' => 'application/json']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        // JSON response should remain unchanged
        $this->assertEquals($jsonContent, $processedResponse->getContent());
        $this->assertStringNotContainsString('<title>', $processedResponse->getContent());
    }

    public function test_middleware_skips_non_200_responses()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/error', 'GET');
        $htmlContent = '<html><head></head><body><h1>404 Not Found</h1></body></html>';
        $response = new Response($htmlContent, 404, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        // 404 response should remain unchanged
        $this->assertEquals($htmlContent, $processedResponse->getContent());
        $this->assertEquals(404, $processedResponse->getStatusCode());
    }

    public function test_middleware_handles_empty_content_type()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/test', 'GET');
        $htmlContent = '<html><head></head><body><h1>Empty Content Type</h1></body></html>';
        $response = new Response($htmlContent, 200); // No Content-Type header

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        // Should process the response even without Content-Type
        $processedContent = $processedResponse->getContent();
        $this->assertStringContainsString('<title>', $processedContent);
    }

    public function test_middleware_handles_empty_response_content()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/empty', 'GET');
        $response = new Response('', 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        // Empty response should remain empty
        $this->assertEquals('', $processedResponse->getContent());
    }

    public function test_middleware_extracts_metadata_from_request()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        // Create a more detailed request
        $request = Request::create(
            'https://example.com/test/path?param=value',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Test User Agent',
                'REMOTE_ADDR' => '192.168.1.1',
            ]
        );

        // Mock a route using a simple mock
        $route = $this->createMock(Route::class);
        $route->method('getName')->willReturn('test.route');
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $htmlContent = '<html><head></head><body><h1>Metadata Test</h1></body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        // Verify the response was processed (contains SEO tags)
        $processedContent = $processedResponse->getContent();
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringContainsString('Metadata Test', $processedContent);
    }

    public function test_middleware_with_request_without_route()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/no-route', 'GET');
        // Don't set a route resolver

        $htmlContent = '<html><head></head><body><h1>No Route Test</h1></body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        // Should still process the response even without a route
        $processedContent = $processedResponse->getContent();
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringContainsString('No Route Test', $processedContent);
    }

    public function test_middleware_preserves_existing_head_content()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/preserve', 'GET');
        $htmlContent = '<html><head><meta charset="utf-8"><link rel="stylesheet" href="style.css"></head><body><h1>Preserve Test</h1></body></html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $processedContent = $processedResponse->getContent();

        // Should preserve existing head content
        $this->assertStringContainsString('<meta charset="utf-8">', $processedContent);
        $this->assertStringContainsString('<link rel="stylesheet" href="style.css">', $processedContent);

        // And add SEO content
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringContainsString('<meta name="description"', $processedContent);
    }

    public function test_middleware_works_with_complex_html()
    {
        $seoManager = $this->app->make(SeoManager::class);
        $middleware = new SeoMiddleware($seoManager);

        $request = Request::create('/complex', 'GET');
        $htmlContent = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
</head>
<body>
    <header>
        <h1>Complex HTML Test</h1>
        <nav><a href="/">Home</a></nav>
    </header>
    <main>
        <article>
            <h2>Article Title</h2>
            <p>This is a complex HTML structure with multiple headings and content sections.</p>
        </article>
    </main>
    <footer>
        <p>&copy; 2024 Test Site</p>
    </footer>
</body>
</html>';
        $response = new Response($htmlContent, 200, ['Content-Type' => 'text/html']);

        $processedResponse = $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $processedContent = $processedResponse->getContent();

        // Should preserve the complex structure
        $this->assertStringContainsString('<!DOCTYPE html>', $processedContent);
        $this->assertStringContainsString('<nav><a href="/">Home</a></nav>', $processedContent);
        $this->assertStringContainsString('&copy; 2024 Test Site', $processedContent);

        // And add SEO content
        $this->assertStringContainsString('<title>', $processedContent);
        $this->assertStringContainsString('<meta name="description"', $processedContent);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
