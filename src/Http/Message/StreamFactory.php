<?php

namespace Inpsyde\BackWPup\Http\Message;

use GuzzleHttp\Psr7\Utils;

/**
 * Factory for creating streams.
 *
 * Uses GuzzleHttp\Psr7 objects for implementation.
 */
class StreamFactory implements StreamFactoryInterface
{
    const VALID_MODES = ['r', 'w', 'a', 'x', 'c'];

    /**
     * {@inheritdoc}
     */
    public function createStream($content = '')
    {
        if (!is_string($content)) {
            throw new \InvalidArgumentException(__('Content must be a string.', 'backwpup'));
        }

        return Utils::streamFor($content);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile($filename, $mode = 'r')
    {
        $this->assertMode($mode);

        $resource = Utils::tryFopen($filename, $mode);

        return $this->createStreamFromResource($resource);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromResource($resource)
    {
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException(__('Stream must be a resource', 'backwpup'));
        }

        $stream = Utils::streamFor($resource);

        if (!$stream->isReadable()) {
            throw new \RuntimeException(__('The stream is not readable', 'backwpup'));
        }

        return $stream;
    }

    /**
     * Check that the given mode is valid.
     *
     * PHP only checks the first character, so as long as that is valid, we ignore the rest.
     *
     * Resource: https://stackoverflow.com/a/44483367/96264
     *
     * @param string $mode
     *
     * @throws \InvalidArgumentException If the mode is invalid
     */
    private function assertMode($mode)
    {
        if (!in_array(strtolower($mode[0]), self::VALID_MODES, true)) {
            throw new \InvalidArgumentException(sprintf(__('Invalid mode provided for fopen: %s', 'backwpup'), $mode));
        }
    }
}
