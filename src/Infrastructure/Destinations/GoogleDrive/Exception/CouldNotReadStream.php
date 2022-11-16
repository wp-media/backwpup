<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive\Exception;

final class CouldNotReadStream extends \RuntimeException
{
    public static function withError(\RuntimeException $exception): self
    {
        return new self('Could not read stream', 0, $exception);
    }
}
