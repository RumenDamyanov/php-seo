<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\AI;

use Rumenx\PhpSeo\Config\SeoConfig;

/**
 * Prompt builder for AI content generation.
 *
 * Builds optimized prompts for AI providers to generate SEO content
 * including titles, descriptions, and keywords.
 */
class PromptBuilder
{
    private SeoConfig $config;

    public function __construct(SeoConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Build a prompt for title generation.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Additional options
     * @return string The generated prompt
     */
    public function buildTitlePrompt(array $analysis, array $options = []): string
    {
        $template = $this->config->get('ai.prompts.title', $this->getDefaultTitleTemplate());

        return $this->renderTemplate($template, $analysis, $options, 'title');
    }

    /**
     * Build a prompt for description generation.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Additional options
     * @return string The generated prompt
     */
    public function buildDescriptionPrompt(array $analysis, array $options = []): string
    {
        $template = $this->config->get('ai.prompts.description', $this->getDefaultDescriptionTemplate());

        return $this->renderTemplate($template, $analysis, $options, 'description');
    }

    /**
     * Build a prompt for keywords generation.
     *
     * @param array<string, mixed> $analysis Content analysis data
     * @param array<string, mixed> $options Additional options
     * @return string The generated prompt
     */
    public function buildKeywordsPrompt(array $analysis, array $options = []): string
    {
        $template = $this->config->get('ai.prompts.keywords', $this->getDefaultKeywordsTemplate());

        return $this->renderTemplate($template, $analysis, $options, 'keywords');
    }

    /**
     * Render a prompt template with analysis data.
     *
     * @param string $template The prompt template
     * @param array<string, mixed> $analysis Content analysis
     * @param array<string, mixed> $options Additional options
     * @param string $type The generation type (title, description, keywords)
     * @return string The rendered prompt
     */
    private function renderTemplate(string $template, array $analysis, array $options, string $type): string
    {
        $context = $this->buildContext($analysis, $options, $type);

        // Replace placeholders in template
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';
            $template = str_replace($placeholder, (string)$value, $template);
        }

        return trim($template);
    }

    /**
     * Build context data for template rendering.
     *
     * @param array<string, mixed> $analysis Content analysis
     * @param array<string, mixed> $options Additional options
     * @param string $type The generation type
     * @return array<string, mixed>
     */
    private function buildContext(array $analysis, array $options, string $type): array
    {
        $context = [
            'summary' => $this->extractSummary($analysis),
            'content' => $this->extractContent($analysis),
            'headings' => $this->extractHeadings($analysis),
            'keywords' => $this->extractKeywords($analysis),
            'max_length' => $this->getMaxLength($type, $options),
            'min_length' => $this->getMinLength($type, $options),
            'language' => $options['language'] ?? $this->config->get('ai.language', 'English'),
            'tone' => $options['tone'] ?? $this->config->get('ai.tone', 'professional'),
        ];

        return array_merge($context, $options);
    }

    /**
     * Extract summary from analysis.
     */
    private function extractSummary(array $analysis): string
    {
        return $analysis['summary'] ?? '';
    }

    /**
     * Extract content from analysis.
     */
    private function extractContent(array $analysis): string
    {
        $content = $analysis['main_content'] ?? '';

        if (strlen($content) > 500) {
            $content = substr($content, 0, 500) . '...';
        }

        return strip_tags($content);
    }

    /**
     * Extract headings from analysis.
     */
    private function extractHeadings(array $analysis): string
    {
        if (empty($analysis['headings'])) {
            return '';
        }

        $headings = [];
        $count = 0;

        foreach ($analysis['headings'] as $heading) {
            if ($count >= 5) {
                break;
            }

            if (isset($heading['text'])) {
                $headings[] = $heading['text'];
                $count++;
            }
        }

        return implode(', ', $headings);
    }

    /**
     * Extract keywords from analysis.
     */
    private function extractKeywords(array $analysis): string
    {
        if (empty($analysis['keywords'])) {
            return '';
        }

        $keywords = array_slice($analysis['keywords'], 0, 10);

        return implode(', ', $keywords);
    }

    /**
     * Get max length for the generation type.
     */
    private function getMaxLength(string $type, array $options): int
    {
        if (isset($options['max_length'])) {
            return (int)$options['max_length'];
        }

        return match ($type) {
            'title' => $this->config->get('title.max_length', 60),
            'description' => $this->config->get('description.max_length', 160),
            'keywords' => $this->config->get('meta_tags.keywords_max', 10),
            default => 100,
        };
    }

    /**
     * Get min length for the generation type.
     */
    private function getMinLength(string $type, array $options): int
    {
        if (isset($options['min_length'])) {
            return (int)$options['min_length'];
        }

        return match ($type) {
            'title' => $this->config->get('title.min_length', 10),
            'description' => $this->config->get('description.min_length', 120),
            default => 0,
        };
    }

    /**
     * Get default title prompt template.
     */
    private function getDefaultTitleTemplate(): string
    {
        return <<<'PROMPT'
Generate an SEO-optimized page title.

Content Summary: {summary}

Main Content Preview: {content}

Key Headings: {headings}

Keywords: {keywords}

Requirements:
- Language: {language}
- Tone: {tone}
- Maximum length: {max_length} characters
- Make it compelling and click-worthy
- Include relevant keywords naturally
- Focus on the main topic

Generate only the title, nothing else.
PROMPT;
    }

    /**
     * Get default description prompt template.
     */
    private function getDefaultDescriptionTemplate(): string
    {
        return <<<'PROMPT'
Generate an SEO-optimized meta description.

Content Summary: {summary}

Main Content Preview: {content}

Key Headings: {headings}

Keywords: {keywords}

Requirements:
- Language: {language}
- Tone: {tone}
- Length: {min_length}-{max_length} characters
- Make it engaging and informative
- Include a call-to-action if appropriate
- Use relevant keywords naturally
- Summarize the page value

Generate only the description, nothing else.
PROMPT;
    }

    /**
     * Get default keywords prompt template.
     */
    private function getDefaultKeywordsTemplate(): string
    {
        return <<<'PROMPT'
Generate relevant SEO keywords.

Content Summary: {summary}

Main Content Preview: {content}

Key Headings: {headings}

Requirements:
- Language: {language}
- Maximum keywords: {max_length}
- Focus on search intent
- Include both primary and long-tail keywords
- Consider user search queries

Return keywords as a comma-separated list, nothing else.
PROMPT;
    }
}
