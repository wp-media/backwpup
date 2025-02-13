<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Infrastructure;

/**
 * @psalm-import-type EventData from \Inpsyde\Restore\EventSource
 */
trait EventSourceTrait
{
    /**
     * Event Source Output.
     *
     * @param string $event The type of the message. Message, Error, Log...
     * @param array  $data  The data to output
     *
     * @psalm-param EventData $data
     */
    private function echoEventData(string $event, array $data): void
    {
        echo "event: {$event}\n"; // phpcs:ignore
        printf("data: %s\n\n", wp_json_encode($data) ?: '');
        flush();
    }
}
