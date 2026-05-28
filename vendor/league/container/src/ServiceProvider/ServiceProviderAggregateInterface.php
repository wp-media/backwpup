<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Dependencies\League\Container\ServiceProvider;

use IteratorAggregate;
use WPMedia\BackWPup\Dependencies\League\Container\ContainerAwareInterface;

interface ServiceProviderAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    public function add(ServiceProviderInterface $provider): ServiceProviderAggregateInterface;
    public function provides(string $id): bool;
    public function register(string $service): void;
}
