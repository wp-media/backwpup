<?php

declare(strict_types=1);

namespace Inpsyde\Restore\Api\Module\Decryption;

use Inpsyde\BackWPup\Archiver\Factory as ArchiveFileOperatorFactory;
use Inpsyde\BackWPupShared\File\MimeTypeExtractor;
use Inpsyde\Restore\Api\Module\Decryption\Exception\DecryptException;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA\PrivateKey;
use Webmozart\Assert\Assert;

/**
 * Decrypt backup archives using AES or RSA.
 */
class Decrypter
{
    private const ENCRYPTED_FILE_SUB_EXTENSION = '.decrypted';
    private const VERSION_1 = 1;
    private const VERSION_2 = 2;
    private const TYPE_AES = 1;
    private const TYPE_RSA = 2;
    private const NULL_IV = "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    private const BLOCK_SIZE = 16;

    /**
     * @var ArchiveFileOperatorFactory
     */
    private $archiveFileOperatorFactory;

    /**
     * @var string|null
     */
    private $key;

    /**
     * @var int|null
     *
     * @psalm-var self::VERSION_*|null
     */
    private $version;

    public function __construct(
        ArchiveFileOperatorFactory $archiveFileOperatorFactory
    ) {
        $this->archiveFileOperatorFactory = $archiveFileOperatorFactory;
    }

    /**
     * Checks if file is encrypted.
     */
    public function isEncrypted(string $backupFile): bool
    {
        $handle = @fopen($backupFile, 'r');
        if ($handle === false) {
            throw new DecryptException(
                __('Cannot open the archive for reading.', 'backwpup')
            );
        }

        // Read first byte to know what encryption method was used
        // If old-style, 0 == Symmetric, 1 == Asymmetric.
        // If new-style, then should begin with BACKWPUP.
        $type = fread($handle, 1);

        $encrypted = false;

        switch ($type) {
            case \chr(0):
            case \chr(1):
                $encrypted = true;
                break;

            case 'B':
                // Read the next 7 bytes to check if it is the new style
                $bytes = fread($handle, 7);
                $encrypted = $bytes === 'ACKWPUP';
                break;
        }

        fclose($handle);

        return $encrypted;
    }

    /**
     * Decrypt the file.
     *
     * @throws DecryptException
     */
    public function decrypt(string $key, string $backupFile): bool
    {
        if ($key === '') {
            throw new DecryptException(__('Private key must be provided.', 'backwpup'));
        }

        $sourceHandle = fopen($backupFile, 'r');
        if (!\is_resource($sourceHandle)) {
            throw new DecryptException(
                __('Cannot open the archive for reading.', 'backwpup')
            );
        }

        $this->readEncryptionHeader($key, $sourceHandle);
        if ($this->key === null || $this->version === null) {
            return false;
        }

        $decryptedFilePath = $backupFile . self::ENCRYPTED_FILE_SUB_EXTENSION;
        if (file_exists($decryptedFilePath)) {
            unlink($decryptedFilePath);
        }

        $targetHandle = fopen($decryptedFilePath, 'a+');
        if (!\is_resource($targetHandle)) {
            throw new DecryptException(
                __('Cannot write the decrypted archive.', 'backwpup')
            );
        }

        $this->decryptData($sourceHandle, $targetHandle);
        fclose($sourceHandle);
        fclose($targetHandle);

        if (filesize($decryptedFilePath) === 0) {
            return false;
        }
        if (!$this->test($decryptedFilePath)) {
            return false;
        }

        unlink($backupFile);
        rename($decryptedFilePath, $backupFile);

        return true;
    }

    /**
     * Gets the key for decrypting the file.
     *
     * If old-style:
     *
     * * If first byte is \x00, symmetric encryption is used and key is passed back as-is.
     * * If first byte is \x01, asymmetric encryption is used and the RSA-encrypted
     *   AES key is read from the file.
     *
     * If new-style:
     *
     * * If type (byte 10) is 1, symmetric encryption is used as described above.
     * * If type (byte 10) is 2, asymmetric encryption is used as described above.
     *
     * @param resource $sourceHandle
     */
    private function readEncryptionHeader(string $key, $sourceHandle): void // phpcs:ignore
    {
        $this->key = null;
        $this->version = null;

        $type = fread($sourceHandle, 1);

        switch ($type) {
            case \chr(0):
                if (!ctype_xdigit($key)) {
                    throw new DecryptException(
                        __('An invalid key was provided', 'backwpup')
                    );
                }

                $this->version = self::VERSION_1;
                $this->key = pack('H*', $key);
                break;

            case \chr(1):
                $this->version = self::VERSION_1;
                $this->key = $this->decryptRsaKey($key, $sourceHandle);
                break;

            case 'B':
                $this->readNewEncryptionHeader($key, $sourceHandle);
        }
    }

    /**
     * @param resource $sourceHandle
     */
    private function readNewEncryptionHeader(string $key, $sourceHandle): void // phpcs:ignore
    {
        $bytes = fread($sourceHandle, 7);
        if ($bytes !== "\x41\x43\x4b\x57\x50\x55\x50") {
            // Not an encrypted file
            return;
        }

        $version = ord(fread($sourceHandle, 1));
        if ($version !== self::VERSION_2) {
            throw new DecryptException(
                sprintf(
                    __("Expected version 2, but got %s", 'backwpup'),
                    $this->version
                )
            );
        }

        $this->version = $version;

        $encryptionType = ord(fread($sourceHandle, 1));
        if ($encryptionType === self::TYPE_AES) {
            if (!ctype_xdigit($key)) {
                throw new DecryptException(__('An invalid key was provided', 'backwpup'));
            }

            $this->key = pack('H*', $key);
        } elseif ($encryptionType === self::TYPE_RSA) {
            $this->key = $this->decryptRsaKey($key, $sourceHandle);
        }
    }

    /**
     * Uses RSA to decrypt the key.
     *
     * @param resource $handle
     */
    private function decryptRsaKey(string $key, $handle): string // phpcs:ignore
    {
        /** @var PrivateKey $rsa */
        $rsa = PublicKeyLoader::loadPrivateKey($key);

        if ($this->version === self::VERSION_2) {
            $encodedLength = unpack('n', fread($handle, 2));
        } elseif ($this->version === self::VERSION_1) {
            $encodedLength = unpack('C', fread($handle, 1));
        }

        if (empty($encodedLength)) {
            throw new DecryptException(__('Could not extract RSA key', 'backwpup'));
        }

        $length = $encodedLength[1];

        $key = fread($handle, $length);

        $decrypted = $rsa->decrypt($key);
        if (!is_string($decrypted)) {
            throw new DecryptException(__('Could not extract RSA key', 'backwpup'));
        }

        return $decrypted;
    }

    /**
     * @param resource $sourceHandle
     * @param resource $targetHandle
     */
    private function decryptData($sourceHandle, $targetHandle): void // phpcs:ignore
    {
        Assert::string($this->key, 'Key was not provided');

        $aes = new AES('cbc');
        $aes->enableContinuousBuffer();
        $aes->disablePadding();
        $aes->setKey($this->key);
        if ($this->version === self::VERSION_1) {
            $aes->setIV(self::NULL_IV);
        } elseif ($this->version === self::VERSION_2) {
            $aes->setIV(fread($sourceHandle, self::BLOCK_SIZE));
        }

        $blockSize = self::BLOCK_SIZE * 8192; // 128 KB

        while (!feof($sourceHandle)) {
            $data = fread($sourceHandle, $blockSize);
            $packet = $aes->decrypt($data);

            if (feof($sourceHandle)) {
                $packet = self::stripPadding($packet);
            }

            fwrite($targetHandle, $packet);
        }
    }

    /**
     * Verify we have decrypted the file.
     */
    private function test(string $decryptedFilePath): bool
    {
        $valid = false;
        $mime_type = MimeTypeExtractor::fromFilePath($decryptedFilePath);
        if ($mime_type === MimeTypeExtractor::DEFAULT_MIME_TYPE) {
            if (false !== stripos($decryptedFilePath, '.zip' . self::ENCRYPTED_FILE_SUB_EXTENSION)) {
                $mime_type = 'application/zip';
            } elseif (false !== stripos($decryptedFilePath, '.tar' . self::ENCRYPTED_FILE_SUB_EXTENSION)) {
                $mime_type = 'application/x-tar';
            } elseif (false !== stripos($decryptedFilePath, '.tar.gz' . self::ENCRYPTED_FILE_SUB_EXTENSION)) {
                $mime_type = 'application/x-gzip';
            } elseif (false !== stripos($decryptedFilePath, '.tar.bz2' . self::ENCRYPTED_FILE_SUB_EXTENSION)) {
                $mime_type = 'application/x-bzip2';
            }
        }

        switch ($mime_type) {
            case 'application/zip':
            case 'application/x-zip':
            case 'application/x-zip-compressed':
            case 'application/x-winzip':
                $operator = $this->archiveFileOperatorFactory->create($decryptedFilePath);
                $valid = $operator->isValid();
                break;
            case 'application/x-tar':
            case 'application/tar':
                $tar = new \Archive_Tar($decryptedFilePath);
                $valid = $tar->listContent();
                break;
            case 'application/x-gzip':
            case 'application/gzip':
            case 'application/x-gtar':
            case 'application/x-tgz':
                $tar = new \Archive_Tar($decryptedFilePath, 'gz');
                $valid = $tar->listContent();
                break;
            case 'application/x-bzip2':
            case 'application/bzip2':
                $tar = new \Archive_Tar($decryptedFilePath, 'bz2');
                $valid = $tar->listContent();
                break;
        }

        return (bool) $valid;
    }

    /**
     * Strips padding from decrypted string according to PKCS7-style padding.
     */
    private static function stripPadding(string $packet): string
    {
        // Get last character of decrypted string to detect padding length
        $paddingLength = ord($packet[strlen($packet) - 1]);
        if ($paddingLength <= self::BLOCK_SIZE) {
            $packet = substr($packet, 0, -$paddingLength);
        }

        return $packet;
    }
}
