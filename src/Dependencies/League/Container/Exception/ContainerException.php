<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
