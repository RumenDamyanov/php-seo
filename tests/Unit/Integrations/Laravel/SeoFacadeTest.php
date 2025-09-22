<?php

declare(strict_types=1);

use Orchestra\Testbench\TestCase;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Integrations\Laravel\SeoFacade;
use Rumenx\PhpSeo\Integrations\Laravel\SeoServiceProvider;
use Rumenx\PhpSeo\SeoManager;

class SeoFacadeTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            SeoServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Seo' => SeoFacade::class,
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

    public function test_facade_provides_access_to_seo_manager()
    {
        $this->assertInstanceOf(SeoManager::class, SeoFacade::getFacadeRoot());
    }

    public function test_facade_can_analyze_content()
    {
        $content = '<h1>Test Title</h1><p>This is test content for analysis.</p>';
        $result = SeoFacade::analyze($content);

        $this->assertInstanceOf(SeoManager::class, $result);
    }

    public function test_facade_can_generate_title()
    {
        $content = '<h1>Test Page Title</h1><p>Content goes here.</p>';
        SeoFacade::analyze($content);

        $title = SeoFacade::generateTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function test_facade_can_generate_title_with_custom_title()
    {
        $customTitle = 'Custom Page Title';
        $title = SeoFacade::generateTitle($customTitle);

        $this->assertStringContainsString($customTitle, $title);
    }

    public function test_facade_can_generate_description()
    {
        $content = '<h1>Test Title</h1><p>This is a test description content that should be extracted properly.</p>';
        SeoFacade::analyze($content);

        $description = SeoFacade::generateDescription();

        $this->assertIsString($description);
        $this->assertNotEmpty($description);
    }

    public function test_facade_can_generate_description_with_custom_description()
    {
        $customDescription = 'This is a custom description for testing.';
        $description = SeoFacade::generateDescription($customDescription);

        $this->assertStringContainsString($customDescription, $description);
    }

    public function test_facade_can_generate_meta_tags()
    {
        $content = '<h1>Test Title</h1><p>This is test content.</p>';
        SeoFacade::analyze($content);

        $metaTags = SeoFacade::generateMetaTags();

        $this->assertIsArray($metaTags);
        // generateMetaTags returns the meta_tags array directly, not wrapped with title/description
        $this->assertNotEmpty($metaTags);
    }

    public function test_facade_can_generate_meta_tags_with_custom_meta()
    {
        $customMeta = [
            'keywords' => 'test, custom, meta',
            'author' => 'Test Author',
        ];

        $metaTags = SeoFacade::generateMetaTags($customMeta);

        $this->assertIsArray($metaTags);
        $this->assertArrayHasKey('keywords', $metaTags);
        $this->assertArrayHasKey('author', $metaTags);
    }

    public function test_facade_can_generate_all_seo_data()
    {
        $content = '<h1>Complete Test</h1><p>This is comprehensive test content for generating all SEO data.</p>';
        SeoFacade::analyze($content);

        $allData = SeoFacade::generateAll();

        $this->assertIsArray($allData);
        $this->assertArrayHasKey('title', $allData);
        $this->assertArrayHasKey('description', $allData);
        $this->assertArrayHasKey('meta_tags', $allData);
    }

    public function test_facade_can_generate_all_with_overrides()
    {
        $content = '<h1>Test</h1><p>Content</p>';
        SeoFacade::analyze($content);

        $overrides = [
            'title' => 'Override Title',
            'description' => 'Override Description',
        ];

        $allData = SeoFacade::generateAll($overrides);

        $this->assertIsArray($allData);
        $this->assertStringContainsString('Override Title', $allData['title']);
        $this->assertStringContainsString('Override Description', $allData['description']);
    }

    public function test_facade_can_render_meta_tags()
    {
        $content = '<h1>Render Test</h1><p>This content will be used to render meta tags.</p>';
        SeoFacade::analyze($content);

        $renderedTags = SeoFacade::renderMetaTags();

        $this->assertIsString($renderedTags);
        $this->assertStringContainsString('<title>', $renderedTags);
        $this->assertStringContainsString('<meta name="description"', $renderedTags);
    }

    public function test_facade_can_render_meta_tags_with_custom_data()
    {
        $customData = [
            'title' => 'Custom Rendered Title',
            'description' => 'Custom rendered description',
            'meta_tags' => [
                'keywords' => 'render, test, custom',
            ],
        ];

        $renderedTags = SeoFacade::renderMetaTags($customData);

        $this->assertIsString($renderedTags);
        $this->assertStringContainsString('<title>Custom Rendered Title</title>', $renderedTags);
        $this->assertStringContainsString('Custom rendered description', $renderedTags);
        $this->assertStringContainsString('render, test, custom', $renderedTags);
    }

    public function test_facade_can_get_config()
    {
        $config = SeoFacade::getConfig();

        $this->assertInstanceOf(SeoConfig::class, $config);
        $this->assertEquals('Test Site', $config->get('title.site_name'));
        $this->assertEquals(60, $config->get('title.max_length'));
    }

    public function test_facade_can_get_page_data()
    {
        $content = '<h1>Page Data Test</h1><p>Content for page data.</p>';
        $metadata = ['custom' => 'data'];

        SeoFacade::analyze($content, $metadata);
        $pageData = SeoFacade::getPageData();

        $this->assertIsArray($pageData);
        $this->assertArrayHasKey('content', $pageData);
        $this->assertArrayHasKey('metadata', $pageData);
    }

    public function test_facade_can_set_page_data()
    {
        $pageData = [
            'content' => 'Test content',
            'metadata' => ['title' => 'Test Title'],
        ];

        $result = SeoFacade::setPageData($pageData);

        $this->assertInstanceOf(SeoManager::class, $result);

        $retrievedData = SeoFacade::getPageData();
        $this->assertEquals($pageData, $retrievedData);
    }

    public function test_facade_can_create_new_instance_with_config()
    {
        $newConfig = new SeoConfig([
            'title' => ['max_length' => 80],
            'description' => ['max_length' => 200],
        ]);

        $newManager = SeoFacade::withConfig($newConfig);

        $this->assertInstanceOf(SeoManager::class, $newManager);
        $this->assertEquals(80, $newManager->getConfig()->get('title.max_length'));

        // Original facade should still have original config
        $this->assertEquals(60, SeoFacade::getConfig()->get('title.max_length'));
    }

    public function test_facade_accessor_returns_correct_service_name()
    {
        // Test the protected method through reflection
        $reflection = new \ReflectionClass(SeoFacade::class);
        $method = $reflection->getMethod('getFacadeAccessor');
        $method->setAccessible(true);

        $accessor = $method->invoke(null);
        $this->assertEquals('seo', $accessor);
    }

    public function test_facade_resolves_from_container()
    {
        // Verify the facade resolves the service from the container
        $manager1 = SeoFacade::getFacadeRoot();
        $manager2 = app('seo');

        $this->assertSame($manager1, $manager2);
        $this->assertInstanceOf(SeoManager::class, $manager1);
    }
}
