<?php

declare(strict_types=1);

/*
 * This file is part of the Inpsyde BackWpUp package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Restore;

use Inpsyde\Restore\Infrastructure\EventSourceTrait;

/**
 * @psalm-type EventData=array{state?: string, message?: string}
 */
class EventSource
{
    use EventSourceTrait;

    /**
     * Set Headers Event Source Request.
     */
    public function setHeaders(): self
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('Content-Encoding: none');

        // 2KB padding for IE
        echo ':' . str_repeat(' ', 2048) . "\n\n"; // phpcs:ignore

        // Ensure we're not buffered.
        wp_ob_end_flush_all();
        flush();

        return $this;
    }

    /**
     * Increase Resources.
     *
     * Increase the php resources such as max_execution_time, memory_limit and time_limit.
     */
    public function increaseResources(): self
    {
        // phpcs:disable
        @set_time_limit(0);
        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '-1');
        // phpcs:enable

        return $this;
    }

    /**
     * Event Source Output.
     *
     * @param string    $event The type of the message. Message, Error, Log.
     * @param EventData $data  The data to output
     */
    public function response(string $event, array $data): self
    {
        $this->echoEventData($event, $data);

        return $this;
    }
}
