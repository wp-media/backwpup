<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\Exception;

use WPMedia\BackWPup\Dependencies\Psr\Container\NotFoundExceptionInterface;
use InvalidArgumentException;

class NotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
}
