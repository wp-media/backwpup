<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message\Exception;

final class CouldNotDecodeJsonData extends \RuntimeException
{
    public static function withError(string $error): self
    {
        return new self(sprintf(__('Data is not valid JSON. Error: %s', 'backwpup'), $error));
    }
}
