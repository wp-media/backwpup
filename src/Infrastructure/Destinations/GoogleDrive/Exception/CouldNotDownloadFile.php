<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive\Exception;

final class CouldNotDownloadFile extends \RuntimeException
{
    public static function withError(\RuntimeException $exception): self
    {
        return new self('The file could not be downloaded', 0, $exception);
    }
}
