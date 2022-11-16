<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive;

use Google\Client;
use Google\Exception as GoogleException;
use Google\Http\MediaFileUpload;
use Google\Service\Drive;
use Google\Service\Exception as GoogleServiceException;
use Inpsyde\BackWPup\Common\PathUtils;
use Inpsyde\BackWPup\Infrastructure\Destinations\GoogleDrive\Exception\CouldNotCreateFolder;
use Inpsyde\BackWPupShared\File\MimeTypeExtractor;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Webmozart\Assert\Assert;

final class GoogleDrive
{
    private const FOLDER_MIME_TYPE = 'application/vnd.google-apps.folder';

    /**
     * @var Drive
     *
     * @readonly
     */
    private $drive;

    public function __construct(Client $client)
    {
        $this->drive = new Drive($client);
    }

    /**
     * @param non-empty-string $folderName
     * @param non-empty-string $parent
     */
    public function folderExists(string $folderName, string $parent = 'root'): bool
    {
        $parts = PathUtils::split($folderName);
        Assert::notEmpty($parts, 'Folder must be specified');
        $topFolder = array_shift($parts);
        $remainingPath = PathUtils::join(...$parts);

        $files = $this->find($topFolder, $parent);

        if (\count($files) === 0) {
            return false;
        }

        // All folders are checked, so return true
        if ($remainingPath === '') {
            return true;
        }

        // There could be multiple folders with this name
        foreach ($files as $file) {
            if ($file->id !== null && $this->folderExists($remainingPath, $file->id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param non-empty-string $folder
     * @param non-empty-string $parent
     *
     * @return non-empty-string|null
     */
    public function getFolder(string $folder, string $parent = 'root'): ?string
    {
        $parts = PathUtils::split($folder);
        Assert::notEmpty($parts, 'Folder must be specified');
        $topLevelFolder = array_shift($parts);
        $remainingPath = PathUtils::join(...$parts);

        $files = $this->find($topLevelFolder, $parent);

        if (\count($files) === 0) {
            return null;
        }

        if ($remainingPath === '') {
            return $files[0]->id;
        }

        // Loop through all results and call on the sub-path
        foreach ($files as $file) {
            if ($file->id === null) {
                continue;
            }

            $subFolder = $this->getFolder($remainingPath, $file->id);

            if ($subFolder !== null) {
                return $subFolder;
            }
        }

        return null;
    }

    /**
     * @return Drive\DriveFile[]
     */
    public function search(string $query): array
    {
        $results = [];
        $pageToken = null;
        $params = [
            'q' => $query,
            'fields' => 'nextPageToken, files(id, name, size, modifiedTime)',
        ];

        do {
            $params['pageToken'] = $pageToken;

            $files = $this->drive->files->listFiles($params);
            $results = array_merge($results, $files->getFiles());
            $pageToken = $files->getNextPageToken();
        } while ($pageToken);

        return $results;
    }

    /**
     * @param non-empty-string $id
     * @param string[]         $fields
     *
     * @throws Exception\CouldNotFindFile
     * @throws GoogleException
     */
    public function get(string $id, array $fields = []): Drive\DriveFile
    {
        $parameters = [];
        if (!empty($fields)) {
            $parameters['fields'] = implode(', ', $fields);
        }

        try {
            return $this->drive->files->get($id, $parameters);
        } catch (GoogleServiceException $e) {
            if (self::hasNotFoundError($e)) {
                throw Exception\CouldNotFindFile::withError($e);
            }

            throw $e;
        }
    }

    /**
     * @param non-empty-string $folder
     *
     * @return Drive\DriveFile[]
     */
    public function getFiles(string $folder): array
    {
        return $this->search(
            sprintf(
                "'%s' in parents and mimeType!='%s' and trashed=false",
                $folder,
                self::FOLDER_MIME_TYPE
            )
        );
    }

    /**
     * @param non-empty-string      $name
     * @param non-empty-string      $parent The ID of the parent folder (or 'root')
     * @param non-empty-string|null $id
     *
     * @throws GoogleException
     */
    public function createFolder(string $name, string $parent = 'root', string $id = null): void
    {
        if ($id === null) {
            $id = $this->generateId();
        }

        $folder = self::getDriveFile($name, $parent, $id, self::FOLDER_MIME_TYPE);

        $this->drive->files->create($folder)->getId();
    }

    /**
     * Recursively create a folder path.
     *
     * Since the passed ID should be expected to be the ID of the created sub-folder, this method
     * will throw an error if the folder already exists. Check with {@link folderExists()} first.
     *
     * @param non-empty-string      $path
     * @param non-empty-string|null $id
     *
     * @throws CouldNotCreateFolder If the full folder path already exists
     * @throws GoogleException
     */
    public function createFoldersRecursively(string $path, string $id = null): void
    {
        $parts = PathUtils::split($path);
        Assert::notEmpty($parts, 'Folder must be specified');

        $numParts = \count($parts);

        $parent = 'root';

        // Loop through all but the last element
        for ($i = 0; $i < $numParts - 1; ++$i) {
            $folder = $this->getFolder($parts[$i], $parent);
            if ($folder === null) {
                $folder = $this->generateId();
                $this->createFolder($parts[$i], $parent, $folder);
            }
            $parent = $folder;
        }

        // Error if child folder already exists
        if ($this->folderExists($parts[$i], $parent)) {
            throw CouldNotCreateFolder::becauseItAlreadyExists($path);
        }

        if ($id === null) {
            $id = $this->generateId();
        }

        $this->createFolder($parts[$i], $parent, $id);
    }

    /**
     * @param non-empty-string $id
     *
     * @throws GoogleException
     * @throws Exception\CouldNotFindFile
     */
    public function delete(string $id): void
    {
        try {
            $this->drive->files->delete($id);
        } catch (GoogleServiceException $e) {
            if (self::hasNotFoundError($e)) {
                throw Exception\CouldNotFindFile::withError($e);
            }

            throw $e;
        }
    }

    /**
     * @param non-empty-string $id
     *
     * @throws Exception\CouldNotFindFile
     * @throws GoogleException
     */
    public function trash(string $id): void
    {
        $file = new Drive\DriveFile();
        $file->setTrashed(true);

        try {
            $this->drive->files->update($id, $file);
        } catch (GoogleServiceException $e) {
            if (self::hasNotFoundError($e)) {
                throw Exception\CouldNotFindFile::withError($e);
            }

            throw $e;
        }
    }

    /**
     * @return non-empty-string
     */
    public function generateId(): string
    {
        $ids = $this->generateIds(1);

        return $ids[0];
    }

    /**
     * @param positive-int $count
     *
     * @return list<non-empty-string>
     */
    public function generateIds(int $count): array
    {
        $idResponse = $this->drive->files->generateIds(['count' => $count]);

        return $idResponse->getIds();
    }

    /**
     * @param non-empty-string|null $id
     * @param non-empty-string      $folder
     *
     * @throws GoogleException
     */
    public function startUpload(
        StreamInterface $stream,
        string $folder = 'root',
        string $id = null
    ): GoogleDriveUploader {
        self::assertStreamIsReadable($stream);

        $uri = self::getUri($stream);

        if ($id === null) {
            $id = $this->generateId();
        }

        $media = $this->createMediaFileUpload(self::getDriveFile($uri, $folder, $id));

        return GoogleDriveUploader::fromNewRequest($media, $stream);
    }

    /**
     * @param non-empty-string $resumeUri
     *
     * @throws Exception\CouldNotSeekStream If the stream is not seekable
     * @throws GoogleException
     */
    public function resumeUpload(StreamInterface $stream, string $resumeUri): GoogleDriveUploader
    {
        self::assertStreamIsReadable($stream);

        $uri = self::getUri($stream);

        $media = $this->createMediaFileUpload(self::getDriveFile($uri));

        return GoogleDriveUploader::fromResumedRequest($media, $stream, $resumeUri);
    }

    /**
     * @param non-empty-string $id
     *
     * @throws GoogleException
     * @throws Exception\CouldNotFindFile
     */
    public function startDownload(string $id, StreamInterface $writeStream): GoogleDriveDownloader
    {
        try {
            /** @var ResponseInterface $response */
            $response = $this->drive->files->get($id, ['alt' => 'media']);
        } catch (GoogleServiceException $e) {
            if (self::hasNotFoundError($e)) {
                throw Exception\CouldNotFindFile::withError($e);
            }

            throw $e;
        }

        return new GoogleDriveDownloader($response, $writeStream);
    }

    /**
     * @param non-empty-string      $parent
     * @param non-empty-string|null $id
     *
     * @throws Exception\CouldNotReadStream
     * @throws GoogleException
     */
    public function createFile(StreamInterface $stream, string $parent = 'root', string $id = null): void
    {
        self::assertStreamIsReadable($stream);
        $uri = self::getUri($stream);

        if ($id === null) {
            $id = $this->generateId();
        }

        $file = self::getDriveFile($uri, $parent, $id);

        try {
            $contents = $stream->getContents();
        } catch (\RuntimeException $e) {
            throw Exception\CouldNotReadStream::withError($e);
        }

        $this->drive->files->create($file, [
            'data' => $contents,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
        ]);
    }

    /**
     * @param non-empty-string $id
     *
     * @throws Exception\CouldNotReadStream
     * @throws GoogleException
     */
    public function updateFile(string $id, StreamInterface $stream): void
    {
        self::assertStreamIsReadable($stream);
        $uri = self::getUri($stream);

        $file = self::getDriveFile($uri);

        try {
            $contents = $stream->getContents();
        } catch (\RuntimeException $e) {
            throw Exception\CouldNotReadStream::withError($e);
        }

        $this->drive->files->update($id, $file, [
            'data' => $contents,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'media',
        ]);
    }

    /**
     * @param non-empty-string $id
     *
     * @throws Exception\CouldNotFindFile
     * @throws GoogleException
     *
     * @return non-empty-string
     */
    public function getPathTo(string $id): string
    {
        $path = [];

        do {
            $file = $this->get($id, ['id', 'name', 'parents']);
            /** @var non-empty-string $file->name */
            $path[] = $file->name;

            if (!empty($file->parents)) {
                $id = $file->parents[0];
            }
        } while ($file->parents !== null);

        $path = array_reverse($path);

        return PathUtils::join(...$path);
    }

    /**
     * @throws GoogleException
     */
    private function createMediaFileUpload(Drive\DriveFile $file): MediaFileUpload
    {
        $client = $this->drive->getClient();
        $client->setDefer(true);
        // Client is deferred, so this will return a request instead of DriveFile
        /** @var RequestInterface $request */
        $request = $this->drive->files->create($file);
        $client->setDefer(false);

        $mimeType = $file->getMimeType() ?? 'application/octet-stream';

        return new MediaFileUpload($client, $request, $mimeType, null, true);
    }

    /**
     * @return Drive\DriveFile[]
     */
    private function find(string $name, string $parent): array
    {
        $query = sprintf(
            "name='%s' and '%s' in parents and mimeType='%s' and trashed=false",
            self::escape($name),
            $parent,
            self::FOLDER_MIME_TYPE
        );

        return $this->search($query);
    }

    /**
     * @param non-empty-string      $uri
     * @param non-empty-string|null $parent
     * @param non-empty-string|null $id
     * @param non-empty-string|null $mimeType If null, the mime type will be extracted from the URI
     */
    private static function getDriveFile(
        string $uri,
        ?string $parent = null,
        ?string $id = null,
        string $mimeType = null
    ): Drive\DriveFile {
        if ($mimeType === null) {
            $mimeType = MimeTypeExtractor::fromFilePath($uri);
        }

        $file = new Drive\DriveFile();
        if (isset($id)) {
            $file->setId($id);
        }
        $file->setName(basename($uri));
        $file->setMimeType($mimeType);
        if (isset($parent)) {
            $file->setParents([$parent]);
        }

        return $file;
    }

    /**
     * @psalm-pure
     */
    private static function escape(string $string): string
    {
        return addcslashes($string, "'\\");
    }

    /**
     * @psalm-pure
     */
    private static function hasNotFoundError(GoogleServiceException $exception): bool
    {
        $errors = $exception->getErrors();

        return !empty($errors) && \in_array('notFound', array_column($errors, 'reason'), true);
    }

    private static function assertStreamIsReadable(StreamInterface $stream): void
    {
        Assert::true($stream->isReadable(), 'Stream must be readable');
    }

    /**
     * @return non-empty-string
     */
    private static function getUri(StreamInterface $stream): string
    {
        $uri = $stream->getMetadata('uri');
        Assert::string($uri, 'Stream must have a URI');
        Assert::stringNotEmpty($uri, 'Stream must have a URI');

        return $uri;
    }
}
