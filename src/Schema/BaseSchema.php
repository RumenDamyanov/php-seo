<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Schema;

/**
 * Base class for Schema.org structured data types.
 *
 * Provides common functionality for all schema types including
 * JSON-LD generation and property management.
 */
abstract class BaseSchema
{
    /**
     * @var array<string, mixed>
     */
    protected array $properties = [];

    /**
     * Get the schema type (e.g., "Article", "WebPage").
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * Set a property value.
     *
     * @param string $name Property name
     * @param mixed $value Property value
     * @return static
     */
    public function setProperty(string $name, mixed $value): static
    {
        $this->properties[$name] = $value;

        return $this;
    }

    /**
     * Get a property value.
     *
     * @param string $name Property name
     * @param mixed $default Default value if not set
     * @return mixed
     */
    public function getProperty(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    /**
     * Check if a property is set.
     *
     * @param string $name Property name
     * @return bool
     */
    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * Remove a property.
     *
     * @param string $name Property name
     * @return static
     */
    public function removeProperty(string $name): static
    {
        unset($this->properties[$name]);

        return $this;
    }

    /**
     * Get all properties.
     *
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Generate JSON-LD array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            [
                '@context' => 'https://schema.org',
                '@type' => $this->getType(),
            ],
            $this->properties
        );
    }

    /**
     * Generate JSON-LD string representation.
     *
     * @param int $options JSON encoding options
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Generate HTML script tag with JSON-LD.
     *
     * @return string
     */
    public function toHtml(): string
    {
        return sprintf(
            '<script type="application/ld+json">%s</script>',
            $this->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }
}
