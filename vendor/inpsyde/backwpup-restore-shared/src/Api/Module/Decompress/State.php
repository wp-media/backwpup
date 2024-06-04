<?php

declare(strict_types=1);

/*
 * This file is part of the BackWPup Restore Shared package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore\Api\Module\Decompress;

use Inpsyde\Restore\Api\Module\Registry;

/**
 * Class State.
 *
 * TODO Class Hold State, but it's contains `clean` command method, may be the class
 *      have to be split into two different classes because of concerns.
 *
 * @author Guido Scialfa <dev@guidoscialfa.com>
 */
class State
{
    public const STATUS_DONE = 'done';
    public const STATUS_PROGRESS = 'progress';
    public const STATUS_DEFAULT = 'unknown';

    public const KEY_FILENAME = 'filename';
    public const KEY_INDEX = 'index';
    public const KEY_STATE = 'state';
    public const KEY_FILES_COUNTER = 'files_counter';

    /**
     * @var Registry
     */
    private $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Get the Index.
     */
    public function index(): int
    {
        return (int) $this->stateProperty(self::KEY_INDEX, -1);
    }

    /**
     * Get File Name.
     */
    public function fileName(): string
    {
        return (string) $this->stateProperty(self::KEY_FILENAME, '');
    }

    /**
     * Get the State.
     */
    public function state(): string
    {
        return (string) $this->stateProperty(self::KEY_STATE, '');
    }

    // TODO Introduce Files Counter.

    /**
     * Retrieve the Property Decompression State or default value.
     *
     * @param string|int $default
     *
     * @return string|int
     */
    private function stateProperty(string $property, $default)
    {
        $state = $this->registry->decompression_state;

        return $state[$property] ?? $default;
    }
}
