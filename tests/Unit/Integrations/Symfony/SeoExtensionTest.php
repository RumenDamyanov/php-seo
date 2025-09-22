<?php

declare(strict_types=1);

namespace Tests\Unit\Integrations\Symfony;

use PHPUnit\Framework\TestCase;
use Rumenx\PhpSeo\Config\SeoConfig;
use Rumenx\PhpSeo\Integrations\Symfony\Twig\SeoExtension;
use Rumenx\PhpSeo\SeoManager;
use Twig\TwigFunction;

/**
 * Test cases for Symfony Twig SeoExtension.
 */
class SeoExtensionTest extends TestCase
{
    private SeoExtension $extension;
    private SeoManager $seoManager;
    private SeoConfig $config;

    protected function setUp(): void
    {
        $this->config = new SeoConfig();
        $this->seoManager = new SeoManager($this->config);
        $this->extension = new SeoExtension($this->seoManager);
    }

    public function test_extension_can_be_instantiated(): void
    {
        $this->assertInstanceOf(SeoExtension::class, $this->extension);
    }

    public function test_extension_accepts_seo_manager(): void
    {
        $extension = new SeoExtension($this->seoManager);
        $this->assertInstanceOf(SeoExtension::class, $extension);
    }

    public function test_extension_extends_abstract_extension(): void
    {
        $this->assertInstanceOf(\Twig\Extension\AbstractExtension::class, $this->extension);
    }

    public function test_extension_provides_correct_functions(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertIsArray($functions);
        $this->assertCount(5, $functions);

        $functionNames = [];
        foreach ($functions as $function) {
            $this->assertInstanceOf(TwigFunction::class, $function);
            $functionNames[] = $function->getName();
        }

        $expectedFunctions = [
            'seo_title',
            'seo_description',
            'seo_meta_tags',
            'seo_analyze',
            'seo_generate_all',
        ];

        foreach ($expectedFunctions as $expectedFunction) {
            $this->assertContains($expectedFunction, $functionNames);
        }
    }

    public function test_seo_title_function_configuration(): void
    {
        $functions = $this->extension->getFunctions();
        $titleFunction = null;

        foreach ($functions as $function) {
            if ($function->getName() === 'seo_title') {
                $titleFunction = $function;

                break;
            }
        }

        $this->assertNotNull($titleFunction);
        $this->assertEquals('seo_title', $titleFunction->getName());

        $callable = $titleFunction->getCallable();
        $this->assertEquals([$this->extension, 'generateTitle'], $callable);
    }

    public function test_seo_description_function_configuration(): void
    {
        $functions = $this->extension->getFunctions();
        $descriptionFunction = null;

        foreach ($functions as $function) {
            if ($function->getName() === 'seo_description') {
                $descriptionFunction = $function;

                break;
            }
        }

        $this->assertNotNull($descriptionFunction);
        $this->assertEquals('seo_description', $descriptionFunction->getName());

        $callable = $descriptionFunction->getCallable();
        $this->assertEquals([$this->extension, 'generateDescription'], $callable);
    }

    public function test_seo_meta_tags_function_configuration(): void
    {
        $functions = $this->extension->getFunctions();
        $metaTagsFunction = null;

        foreach ($functions as $function) {
            if ($function->getName() === 'seo_meta_tags') {
                $metaTagsFunction = $function;

                break;
            }
        }

        $this->assertNotNull($metaTagsFunction);
        $this->assertEquals('seo_meta_tags', $metaTagsFunction->getName());

        $callable = $metaTagsFunction->getCallable();
        $this->assertEquals([$this->extension, 'renderMetaTags'], $callable);

        // Check that function is safe for HTML output by testing its behavior
        $this->seoManager->analyze('<h1>Test</h1><p>Content</p>');
        $result = $this->extension->renderMetaTags();
        $this->assertIsString($result);
        $this->assertStringContainsString('<', $result); // Contains HTML tags
    }

    public function test_seo_analyze_function_configuration(): void
    {
        $functions = $this->extension->getFunctions();
        $analyzeFunction = null;

        foreach ($functions as $function) {
            if ($function->getName() === 'seo_analyze') {
                $analyzeFunction = $function;

                break;
            }
        }

        $this->assertNotNull($analyzeFunction);
        $this->assertEquals('seo_analyze', $analyzeFunction->getName());

        $callable = $analyzeFunction->getCallable();
        $this->assertEquals([$this->extension, 'analyze'], $callable);
    }

    public function test_seo_generate_all_function_configuration(): void
    {
        $functions = $this->extension->getFunctions();
        $generateAllFunction = null;

        foreach ($functions as $function) {
            if ($function->getName() === 'seo_generate_all') {
                $generateAllFunction = $function;

                break;
            }
        }

        $this->assertNotNull($generateAllFunction);
        $this->assertEquals('seo_generate_all', $generateAllFunction->getName());

        $callable = $generateAllFunction->getCallable();
        $this->assertEquals([$this->extension, 'generateAll'], $callable);
    }

    public function test_generate_title_without_custom_title(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<h1>Test Title</h1><p>Test content</p>');

        $result = $this->extension->generateTitle();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_generate_title_with_custom_title(): void
    {
        $customTitle = 'Custom Test Title';

        $result = $this->extension->generateTitle($customTitle);

        $this->assertIsString($result);
        $this->assertStringContainsString($customTitle, $result);
    }

    public function test_generate_title_with_null(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<h1>Heading Title</h1><p>Content</p>');

        $result = $this->extension->generateTitle(null);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_generate_description_without_custom_description(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<p>This is test content for description generation.</p>');

        $result = $this->extension->generateDescription();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_generate_description_with_custom_description(): void
    {
        $customDescription = 'Custom test description for the page';

        $result = $this->extension->generateDescription($customDescription);

        $this->assertIsString($result);
        $this->assertStringContainsString($customDescription, $result);
    }

    public function test_generate_description_with_null(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<p>Content for description generation testing.</p>');

        $result = $this->extension->generateDescription(null);

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_render_meta_tags_without_seo_data(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<h1>Test</h1><p>Content</p>');

        $result = $this->extension->renderMetaTags();

        $this->assertIsString($result);
        $this->assertStringContainsString('<title>', $result);
        $this->assertStringContainsString('<meta', $result);
    }

    public function test_render_meta_tags_with_seo_data(): void
    {
        $seoData = [
            'title' => 'Test Title',
            'description' => 'Test Description',
            'meta_tags' => [
                'keywords' => 'test, keywords',
                'author' => 'Test Author',
            ],
        ];

        $result = $this->extension->renderMetaTags($seoData);

        $this->assertIsString($result);
        $this->assertStringContainsString('Test Title', $result);
        $this->assertStringContainsString('Test Description', $result);
    }

    public function test_render_meta_tags_with_null(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<h1>Title</h1><p>Content</p>');

        $result = $this->extension->renderMetaTags(null);

        $this->assertIsString($result);
        $this->assertStringContainsString('<title>', $result);
    }

    public function test_analyze_content(): void
    {
        $content = '<h1>Test Title</h1><p>Test content for analysis</p>';
        $metadata = ['url' => 'https://example.com/test'];

        $result = $this->extension->analyze($content, $metadata);

        $this->assertInstanceOf(SeoManager::class, $result);
        $this->assertSame($this->seoManager, $result);
    }

    public function test_analyze_content_without_metadata(): void
    {
        $content = '<h1>Test Title</h1><p>Test content</p>';

        $result = $this->extension->analyze($content);

        $this->assertInstanceOf(SeoManager::class, $result);
        $this->assertSame($this->seoManager, $result);
    }

    public function test_generate_all_without_overrides(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<h1>Title</h1><p>Content</p>');

        $result = $this->extension->generateAll();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('meta_tags', $result);
    }

    public function test_generate_all_with_overrides(): void
    {
        // Set up content for the manager
        $this->seoManager->analyze('<h1>Title</h1><p>Content</p>');

        $overrides = [
            'title' => 'Override Title',
            'description' => 'Override Description',
        ];

        $result = $this->extension->generateAll($overrides);

        $this->assertIsArray($result);
        $this->assertStringContainsString('Override Title', $result['title']);
        $this->assertStringContainsString('Override Description', $result['description']);
    }

    public function test_extension_constructor_signature(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertEquals(1, $constructor->getNumberOfParameters());

        $parameter = $constructor->getParameters()[0];
        $this->assertEquals('seoManager', $parameter->getName());

        $parameterType = $parameter->getType();
        $this->assertNotNull($parameterType);
        $this->assertEquals(SeoManager::class, $parameterType->getName());
    }

    public function test_extension_method_signatures(): void
    {
        $reflection = new \ReflectionClass($this->extension);

        // Test generateTitle method
        $generateTitleMethod = $reflection->getMethod('generateTitle');
        $this->assertTrue($generateTitleMethod->isPublic());
        $this->assertEquals(1, $generateTitleMethod->getNumberOfParameters());

        $titleParam = $generateTitleMethod->getParameters()[0];
        $this->assertEquals('customTitle', $titleParam->getName());
        $this->assertTrue($titleParam->allowsNull());

        // Test generateDescription method
        $generateDescMethod = $reflection->getMethod('generateDescription');
        $this->assertTrue($generateDescMethod->isPublic());
        $this->assertEquals(1, $generateDescMethod->getNumberOfParameters());

        $descParam = $generateDescMethod->getParameters()[0];
        $this->assertEquals('customDescription', $descParam->getName());
        $this->assertTrue($descParam->allowsNull());

        // Test renderMetaTags method
        $renderMethod = $reflection->getMethod('renderMetaTags');
        $this->assertTrue($renderMethod->isPublic());
        $this->assertEquals(1, $renderMethod->getNumberOfParameters());

        $metaParam = $renderMethod->getParameters()[0];
        $this->assertEquals('seoData', $metaParam->getName());
        $this->assertTrue($metaParam->allowsNull());

        // Test analyze method
        $analyzeMethod = $reflection->getMethod('analyze');
        $this->assertTrue($analyzeMethod->isPublic());
        $this->assertEquals(2, $analyzeMethod->getNumberOfParameters());

        $contentParam = $analyzeMethod->getParameters()[0];
        $this->assertEquals('content', $contentParam->getName());

        $metadataParam = $analyzeMethod->getParameters()[1];
        $this->assertEquals('metadata', $metadataParam->getName());
        $this->assertTrue($metadataParam->hasType());

        // Test generateAll method
        $generateAllMethod = $reflection->getMethod('generateAll');
        $this->assertTrue($generateAllMethod->isPublic());
        $this->assertEquals(1, $generateAllMethod->getNumberOfParameters());

        $overridesParam = $generateAllMethod->getParameters()[0];
        $this->assertEquals('overrides', $overridesParam->getName());
        $this->assertTrue($overridesParam->hasType());
    }

    public function test_extension_namespace(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $namespace = $reflection->getNamespaceName();

        $this->assertEquals('Rumenx\PhpSeo\Integrations\Symfony\Twig', $namespace);
    }

    public function test_extension_strict_types(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $filename = $reflection->getFileName();

        $this->assertNotFalse($filename);
        $content = file_get_contents($filename);

        $this->assertStringContainsString('declare(strict_types=1);', $content);
    }

    public function test_extension_functions_return_values(): void
    {
        // Set up content
        $this->seoManager->analyze('<h1>Test</h1><p>Content</p>');

        // Test all function return types
        $title = $this->extension->generateTitle();
        $this->assertIsString($title);

        $description = $this->extension->generateDescription();
        $this->assertIsString($description);

        $metaTags = $this->extension->renderMetaTags();
        $this->assertIsString($metaTags);

        $analyzeResult = $this->extension->analyze('<h1>Test</h1>');
        $this->assertInstanceOf(SeoManager::class, $analyzeResult);

        $generateAllResult = $this->extension->generateAll();
        $this->assertIsArray($generateAllResult);
    }

    public function test_extension_can_be_used_multiple_times(): void
    {
        // Set up content
        $this->seoManager->analyze('<h1>Test</h1><p>Content</p>');

        // Call functions multiple times
        $title1 = $this->extension->generateTitle('Title 1');
        $title2 = $this->extension->generateTitle('Title 2');

        $this->assertIsString($title1);
        $this->assertIsString($title2);
        $this->assertStringContainsString('Title 1', $title1);
        $this->assertStringContainsString('Title 2', $title2);
    }
}
