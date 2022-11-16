<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message\Exception;

final class CouldNotEncodeJsonData extends \RuntimeException
{
    public static function withError(string $error): self
    {
        return new self(sprintf(__('Cannot encode data into JSON. Got error: %s', 'backwpup'), $error));
    }
}
