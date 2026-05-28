<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\Argument;

interface ArgumentInterface
{
    /**
     * @return mixed
     */
    public function getValue();
}
