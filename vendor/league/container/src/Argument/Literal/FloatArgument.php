<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\Argument\Literal;

use WPMedia\BackWPup\Dependencies\League\Container\Argument\LiteralArgument;

class FloatArgument extends LiteralArgument
{
    public function __construct(float $value)
    {
        parent::__construct($value, LiteralArgument::TYPE_FLOAT);
    }
}
