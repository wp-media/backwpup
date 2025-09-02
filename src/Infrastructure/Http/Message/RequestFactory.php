<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

/**
 * Factory for creating requests.
 *
 * Uses GuzzleHttp\Psr7 objects for implementation.
 */
final class RequestFactory implements RequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
}
