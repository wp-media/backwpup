<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Webmozart\Assert\Assert;

final class GoogleDriveDownloader
{
    /**
     * @var StreamInterface
     */
    private $readStream;
    /**
     * @var StreamInterface
     */
    private $writeStream;

    public function __construct(
        ResponseInterface $response,
        StreamInterface $writeStream
    ) {
        $this->readStream = $response->getBody();
        $this->writeStream = $writeStream;
    }

    /**
     * Downloads a chunk of data from Google Drive.
     *
     * Note that `$startByte` and `$endByte` are zero-based. So if you want to download 1 KB
     * starting at byte 0, specify `$startByte = 0` and `$endByte = 1023`.
     *
     * @throws Exception\CouldNotSeekStream
     * @throws Exception\CouldNotDownloadFile
     */
    public function downloadChunk(int $startByte, int $endByte): void
    {
        Assert::greaterThan($endByte, $startByte, 'End byte must be greater than start byte');

        if ($startByte > 0) {
            try {
                $this->readStream->seek($startByte);
                $this->writeStream->seek($startByte);
            } catch (\RuntimeException $e) {
                throw Exception\CouldNotSeekStream::withError($e);
            }
        }

        try {
            Utils::copyToStream($this->readStream, $this->writeStream, $endByte - $startByte + 1);
        } catch (\RuntimeException $e) {
            throw Exception\CouldNotDownloadFile::withError($e);
        }
    }
}
