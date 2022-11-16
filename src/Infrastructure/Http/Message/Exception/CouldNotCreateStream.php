<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message\Exception;

final class CouldNotCreateStream extends \RuntimeException
{
    public static function becauseItIsNotReadable(): self
    {
        return new self(__('The stream is not readable', 'backwpup'));
    }
}
