<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\Argument\Literal;

use WPMedia\BackWPup\Dependencies\League\Container\Argument\LiteralArgument;

class StringArgument extends LiteralArgument
{
    public function __construct(string $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_STRING);
    }
}
