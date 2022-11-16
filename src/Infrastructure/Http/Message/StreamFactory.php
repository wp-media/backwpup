<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message;

use GuzzleHttp\Psr7\Utils;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Exception\CouldNotCreateStream;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Webmozart\Assert\Assert;

/**
 * Factory for creating streams.
 *
 * Uses GuzzleHttp\Psr7 objects for implementation.
 */
final class StreamFactory implements StreamFactoryInterface
{
    /**
     * @var string[]
     */
    private const VALID_MODES = ['r', 'w', 'a', 'x', 'c'];

    /**
     * {@inheritdoc}
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return Utils::streamFor($content);
    }

    /**
     * {@inheritdoc}
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        Assert::inArray(strtolower($mode[0]), self::VALID_MODES, __('Invalid mode provided for fopen: %s', 'backwpup'));

        $resource = Utils::tryFopen($filename, $mode);

        return Utils::streamFor($resource);
    }

    /**
     * {@inheritdoc}
     *
     * @throws CouldNotCreateStream If the stream is not readable
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        Assert::resource($resource, null, __('Stream must be a resource', 'backwpup'));

        $stream = Utils::streamFor($resource);

        if (!$stream->isReadable()) {
            throw CouldNotCreateStream::becauseItIsNotReadable();
        }

        return $stream;
    }
}
