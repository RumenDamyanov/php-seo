<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Schema;

/**
 * Schema.org Organization structured data.
 *
 * Represents an organization such as a company or website publisher.
 * https://schema.org/Organization
 */
class OrganizationSchema extends BaseSchema
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'Organization';
    }

    /**
     * Set the organization name.
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        return $this->setProperty('name', $name);
    }

    /**
     * Set the organization URL.
     *
     * @param string $url
     * @return self
     */
    public function setUrl(string $url): self
    {
        return $this->setProperty('url', $url);
    }

    /**
     * Set the organization logo.
     *
     * @param string|array<string, mixed> $logo URL string or ImageObject schema
     * @return self
     */
    public function setLogo(string|array $logo): self
    {
        if (is_string($logo)) {
            $logo = [
                '@type' => 'ImageObject',
                'url' => $logo,
            ];
        }

        return $this->setProperty('logo', $logo);
    }

    /**
     * Set social media profiles.
     *
     * @param array<string> $sameAs Array of social media URLs
     * @return self
     */
    public function setSameAs(array $sameAs): self
    {
        return $this->setProperty('sameAs', $sameAs);
    }

    /**
     * Set contact point.
     *
     * @param array<string, mixed> $contactPoint ContactPoint schema
     * @return self
     */
    public function setContactPoint(array $contactPoint): self
    {
        return $this->setProperty('contactPoint', $contactPoint);
    }
}
