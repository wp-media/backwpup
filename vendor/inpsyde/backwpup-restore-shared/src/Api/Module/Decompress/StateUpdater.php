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

use Exception;
use Inpsyde\BackWPup\Archiver\CurrentExtractInfo;
use Inpsyde\Restore\Api\Module\Registry;
use Webmozart\Assert\Assert;

/**
 * @author Guido Scialfa <dev@guidoscialfa.com>
 *
 * @psalm-type DecompressionState=array<string,int|string>
 */
class StateUpdater
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * StateUpdater constructor.
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function updateStatus(CurrentExtractInfo $data): void
    {
        $remains = $data->remains;
        $index = $data->index;
        $state = $index === $remains ? State::STATUS_DONE : State::STATUS_PROGRESS;

        $this->saveStatus(
            [
                State::KEY_FILENAME => $data->fileName,
                State::KEY_INDEX => $index,
                State::KEY_STATE => $state,
                State::KEY_FILES_COUNTER => $remains,
            ]
        );
    }

    /**
     * Clean Registry Decompression state.
     */
    public function clean(): void
    {
        $this->registry->decompression_state = [];
    }

    /**
     * Set the current state within the registry.
     *
     * @param DecompressionState $args The arguments to store into the registry
     *
     * @throws Exception If the registry cannot be saved
     */
    private function saveStatus(array $args): void
    {
        // TODO Assert not empty args

        $defaults = [
            State::KEY_FILENAME => '',
            State::KEY_INDEX => 0,
            State::KEY_STATE => 'unknown',
            State::KEY_FILES_COUNTER => 0,
        ];

        $status = array_merge($defaults, $args);
        $this->registry->decompression_state = $status;
    }
}
