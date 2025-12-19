<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Schema;

/**
 * Schema.org WebPage structured data.
 *
 * Represents a single web page.
 * https://schema.org/WebPage
 */
class WebPageSchema extends BaseSchema
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'WebPage';
    }

    /**
     * Set the page name/title.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        return $this->setProperty('name', $name);
    }

    /**
     * Set the page description.
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        return $this->setProperty('description', $description);
    }

    /**
     * Set the page URL.
     *
     * @param string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        return $this->setProperty('url', $url);
    }

    /**
     * Set the page image.
     *
     * @param string|array<string> $image URL or array of URLs
     * @return self
     */
    public function setImage(string|array $image): self
    {
        return $this->setProperty('image', $image);
    }

    /**
     * Set the breadcrumb.
     *
     * @param array<string, mixed> $breadcrumb Breadcrumb schema
     * @return self
     */
    public function setBreadcrumb(array $breadcrumb): self
    {
        return $this->setProperty('breadcrumb', $breadcrumb);
    }
}
