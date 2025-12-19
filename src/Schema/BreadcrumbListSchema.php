<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Schema;

/**
 * Schema.org BreadcrumbList structured data.
 *
 * Represents a breadcrumb navigation trail.
 * https://schema.org/BreadcrumbList
 */
class BreadcrumbListSchema extends BaseSchema
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'BreadcrumbList';
    }

    /**
     * Add a breadcrumb item.
     *
     * @param string $name Item name
     * @param string $url Item URL
     * @param int $position Position in the list (1-based)
     * @return self
     */
    public function addItem(string $name, string $url, int $position): self
    {
        $items = $this->getProperty('itemListElement', []);

        $items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $name,
            'item' => $url,
        ];

        return $this->setProperty('itemListElement', $items);
    }

    /**
     * Set all breadcrumb items at once.
     *
     * @param array<array<string, mixed>> $items Array of items with 'name', 'url', and 'position'
     * @return self
     */
    public function setItems(array $items): self
    {
        $formattedItems = [];

        foreach ($items as $item) {
            $formattedItems[] = [
                '@type' => 'ListItem',
                'position' => $item['position'],
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return $this->setProperty('itemListElement', $formattedItems);
    }
}
