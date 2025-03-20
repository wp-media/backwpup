<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\Argument;

interface ResolvableArgumentInterface extends ArgumentInterface
{
    public function getValue(): string;
}
