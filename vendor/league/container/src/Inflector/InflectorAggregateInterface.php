<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\Inflector;

use IteratorAggregate;
use WPMedia\BackWPup\Dependencies\League\Container\ContainerAwareInterface;

interface InflectorAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(string $type, ?callable $callback = null): Inflector;
    public function inflect(object $object);
}
