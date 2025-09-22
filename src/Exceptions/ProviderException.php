<?php

declare(strict_types=1);

namespace Rumenx\PhpSeo\Exceptions;

/**
 * Exception thrown when a provider encounters an error.
 *
 * This exception is thrown when AI providers fail to generate content
 * or encounter configuration/communication issues.
 */
class ProviderException extends \Exception
{
    /**
     * Create a new provider exception.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous exception
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create an exception for configuration errors.
     *
     * @param string $provider The provider name
     * @param string $reason The configuration issue
     * @return self
     */
    public static function configurationError(string $provider, string $reason): self
    {
        return new self("Provider '{$provider}' configuration error: {$reason}");
    }

    /**
     * Create an exception for communication errors.
     *
     * @param string $provider The provider name
     * @param string $reason The communication issue
     * @return self
     */
    public static function communicationError(string $provider, string $reason): self
    {
        return new self("Provider '{$provider}' communication error: {$reason}");
    }

    /**
     * Create an exception for API errors.
     *
     * @param string $provider The provider name
     * @param string $errorMessage The API error message
     * @param int $httpCode The HTTP status code
     * @return self
     */
    public static function apiError(string $provider, string $errorMessage, int $httpCode = 0): self
    {
        return new self("Provider '{$provider}' API error: {$errorMessage}", $httpCode);
    }
}
