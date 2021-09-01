<?php

namespace Inpsyde\BackWPup\Http\Message;

/**
 * Factory for creating a stream.
 */
interface StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     *
     * The stream SHOULD be created with a temporary resource.
     *
     * @param string $content string content with which to populate the stream
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function createStream($content = '');

    /**
     * Create a stream from a file.
     *
     * @param string $filename the file to open
     * @param string $mode     the mode to use when opening the file
     *
     * @return \Psr\Http\Message\StreamInterface
     *
     * @throws \RuntimeException         if the file cannot be opened
     * @throws \InvalidArgumentException if the mode is invalid
     */
    public function createStreamFromFile($filename, $mode = 'r');

    /**
     * Create a new stream from an existing resource.
     *
     * The stream must be readable, and can optionally be writable.
     *
     * @param resource $resource PHP resource to use as basis of stream
     *
     * @return \Psr\Http\Message\StreamInterface
     */
    public function createStreamFromResource($resource);
}
