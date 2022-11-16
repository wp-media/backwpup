<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive\Exception;

use Google\Service\Exception as GoogleServiceException;

final class CouldNotFindFile extends \RuntimeException
{
    public static function withError(GoogleServiceException $exception): self
    {
        return new self('Could not find file', 0, $exception);
    }
}
