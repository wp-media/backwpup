<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive\Exception;

final class CouldNotCreateFolder extends \RuntimeException
{
    /**
     * @param non-empty-string $path
     */
    public static function becauseItAlreadyExists(string $path): self
    {
        return new self(sprintf('Could not create folder %s because it already exists', $path));
    }
}
