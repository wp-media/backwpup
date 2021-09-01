<?php

namespace Inpsyde\BackWPup\Http\Message;

use GuzzleHttp\Psr7\Request;

/**
 * Factory for creating requests.
 *
 * Uses GuzzleHttp\Psr7 objects for implementation.
 */
class RequestFactory implements RequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createRequest($method, $uri)
    {
        return new Request($method, $uri);
    }
}
