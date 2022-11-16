<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive\Exception;

final class CouldNotSeekStream extends \RuntimeException
{
    public static function withError(\RuntimeException $exception): self
    {
        return new self('Could not seek the provided stream', 0, $exception);
    }
}
