<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive;

use Google\Http\MediaFileUpload;
use Google\Service\Drive\DriveFile;
use Psr\Http\Message\StreamInterface;
use Webmozart\Assert\Assert;

final class GoogleDriveUploader
{
    /**
     * @var positive-int
     */
    private const DEFAULT_CHUNK_SIZE = 4 * 1024 * 1024;
    /**
     * @var int
     */
    private const CHUNK_SIZE_MULTIPLE = 256 * 1024;

    /**
     * @var MediaFileUpload
     */
    private $media;
    /**
     * @var StreamInterface
     */
    private $stream;
    /**
     * @var positive-int
     */
    private $chunkSize = self::DEFAULT_CHUNK_SIZE;
    /**
     * @var DriveFile|false
     */
    private $uploaded = false;

    private function __construct(MediaFileUpload $media, StreamInterface $stream)
    {
        $fileSize = $stream->getSize();
        Assert::notNull($fileSize, 'Cannot calculate size of file');

        $this->media = $media;
        $this->stream = $stream;

        $this->media->setChunkSize($this->chunkSize);
        $this->media->setFileSize(($fileSize));
    }

    public static function fromNewRequest(
        MediaFileUpload $media,
        StreamInterface $stream
    ): self {
        return new self($media, $stream);
    }

    /**
     * @throws Exception\CouldNotSeekStream If the stream is not seekable
     */
    public static function fromResumedRequest(
        MediaFileUpload $media,
        StreamInterface $stream,
        string $resumeUri
    ): self {
        $uploader = new self($media, $stream);
        $media->resume($resumeUri);

        try {
            $stream->seek($media->getProgress());
        } catch (\RuntimeException $e) {
            throw Exception\CouldNotSeekStream::withError($e);
        }

        return $uploader;
    }

    /**
     * @psalm-param int<262144, max> $chunkSize
     */
    public function setChunkSize(int $chunkSize): void
    {
        Assert::true($chunkSize % self::CHUNK_SIZE_MULTIPLE === 0, 'Chunk size must be a multiple of 256KB');

        $this->chunkSize = $chunkSize;
    }

    /**
     * @throws Exception\CouldNotReadStream
     * @throws Exception\ReceivedUnexpectedResponse If response is not false or DriveFile
     */
    public function sendChunk(): void
    {
        $uploaded = $this->media->nextChunk($this->readChunk());

        if ($uploaded !== false && !$uploaded instanceof DriveFile) {
            throw Exception\ReceivedUnexpectedResponse::withResponse($uploaded);
        }

        $this->uploaded = $uploaded;
    }

    public function isUploaded(): bool
    {
        return $this->uploaded !== false;
    }

    /**
     * @return false|DriveFile
     */
    public function uploadedFile()
    {
        return $this->uploaded;
    }

    public function getProgress(): int
    {
        return $this->media->getProgress();
    }

    /**
     * @return non-empty-string
     */
    public function getResumeUri(): string
    {
        return $this->media->getResumeUri();
    }

    /**
     * @throws Exception\CouldNotReadStream
     */
    private function readChunk(): string
    {
        $bytesRead = 0;
        $data = '';

        try {
            while (!$this->stream->eof() && $bytesRead < $this->chunkSize) {
                $chunk = $this->stream->read($this->chunkSize);
                $bytesRead += \strlen($chunk);
                $data .= $chunk;
            }
        } catch (\RuntimeException $e) {
            throw Exception\CouldNotReadStream::withError($e);
        }

        return $data;
    }
}
