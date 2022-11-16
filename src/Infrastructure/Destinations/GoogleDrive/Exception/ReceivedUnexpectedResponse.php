<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive\Exception;

final class ReceivedUnexpectedResponse extends \RuntimeException
{
    /**
     * @param mixed $response
     */
    public static function withResponse($response): self
    {
        $type = self::resolveType($response);

        return new self(sprintf('Received unexpected response of type %s', $type));
    }

    /**
     * @param mixed $response
     */
    private static function resolveType($response): string
    {
        $type = \gettype($response);
        if (\is_object($response)) {
            $type = \get_class($response);
        }

        return $type;
    }
}
