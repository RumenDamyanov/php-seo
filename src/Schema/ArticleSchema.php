<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Schema;

/**
 * Schema.org Article structured data.
 *
 * Represents news articles, blog posts, or editorial content.
 * https://schema.org/Article
 */
class ArticleSchema extends BaseSchema
{
    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return 'Article';
    }

    /**
     * Set the article headline.
     *
     * @param string $headline
     * @return self
     */
    public function setHeadline(string $headline): self
    {
        return $this->setProperty('headline', $headline);
    }

    /**
     * Set the article description.
     *
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        return $this->setProperty('description', $description);
    }

    /**
     * Set the article image.
     *
     * @param string|array<string> $image URL or array of URLs
     * @return self
     */
    public function setImage(string|array $image): self
    {
        return $this->setProperty('image', $image);
    }

    /**
     * Set the date published.
     *
     * @param string $datePublished ISO 8601 date
     * @return self
     */
    public function setDatePublished(string $datePublished): self
    {
        return $this->setProperty('datePublished', $datePublished);
    }

    /**
     * Set the date modified.
     *
     * @param string $dateModified ISO 8601 date
     * @return self
     */
    public function setDateModified(string $dateModified): self
    {
        return $this->setProperty('dateModified', $dateModified);
    }

    /**
     * Set the author.
     *
     * @param string|array<string, mixed> $author Name string or Person schema
     * @return self
     */
    public function setAuthor(string|array $author): self
    {
        if (is_string($author)) {
            $author = [
                '@type' => 'Person',
                'name' => $author,
            ];
        }

        return $this->setProperty('author', $author);
    }

    /**
     * Set the publisher.
     *
     * @param string|array<string, mixed> $publisher Name string or Organization schema
     * @return self
     */
    public function setPublisher(string|array $publisher): self
    {
        if (is_string($publisher)) {
            $publisher = [
                '@type' => 'Organization',
                'name' => $publisher,
            ];
        }

        return $this->setProperty('publisher', $publisher);
    }

    /**
     * Set the article body.
     *
     * @param string $articleBody
     * @return self
     */
    public function setArticleBody(string $articleBody): self
    {
        return $this->setProperty('articleBody', $articleBody);
    }

    /**
     * Set the word count.
     *
     * @param int $wordCount
     * @return self
     */
    public function setWordCount(int $wordCount): self
    {
        return $this->setProperty('wordCount', $wordCount);
    }
}
