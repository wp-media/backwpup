<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Client\Exception;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * {@inheritdoc}
 */
final class RequestException extends \RuntimeException implements RequestExceptionInterface
{
    use RequestAwareTrait;

    /**
     * Constructs the exception.
     *
     * @param string           $message  The exception message
     * @param RequestInterface $request  The HTTP request being sent
     * @param \Exception|null  $previous The previous exception
     */
    public function __construct(string $message, RequestInterface $request, ?\Exception $previous = null)
    {
        $this->setRequest($request);

        parent::__construct($message, 0, $previous);
    }
}
