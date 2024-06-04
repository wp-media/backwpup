<?php

declare(strict_types=1);

namespace Inpsyde\Restore;

interface StorageInterface
{
    /**
     * @param non-empty-string $key
     * @param mixed            $value
     */
    public function set(string $key, $value): void;

    /**
     * @param non-empty-string $key
     *
     * @return mixed|null The item value or null if not exists
     */
    public function get(string $key);

    /**
     * @param non-empty-string $key
     */
    public function delete(string $key): void;
}
