<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message;

use WPMedia\BackWPup\Dependencies\GuzzleHttp\Psr7\Response;
use WPMedia\BackWPup\Dependencies\Psr\Http\Message\ResponseFactoryInterface;
use WPMedia\BackWPup\Dependencies\Psr\Http\Message\ResponseInterface;

/**
 * Factory for creating responses.
 *
 * Uses WPMedia\BackWPup\Dependencies\GuzzleHttp\Psr7 objects for implementation.
 */
final class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
}
