<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Security;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\Random;
use phpseclib3\Crypt\RSA;
use Psr\Http\Message\StreamInterface;

final class EncryptionStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private const HEADER = "\x42\x41\x43\x4b\x57\x50\x55\x50";
    private const VERSION = 2;
    private const TYPE_SYMMETRIC = 1;
    private const TYPE_ASYMMETRIC = 2;
    private const BLOCK_SIZE = 16;

    /**
     * @var AES
     */
    private $aesEncryptor;

    /**
     * @var RSA\PublicKey|null
     */
    private $rsaEncryptor;

    /**
     * @var self::TYPE_*
     */
    private $type;

    /**
     * @var string
     */
    private $iv;

    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * @psalm-param self::TYPE_* $type
     *
     * @throws \RuntimeException If the IV cannot be generated
     */
    private function __construct(int $type, string $key, StreamInterface $output)
    {
        $this->type = $type;
        $this->iv = Random::string(self::BLOCK_SIZE);
        $this->stream = $output;

        $this->aesEncryptor = new AES('CBC');
        $this->aesEncryptor->enableContinuousBuffer();
        $this->aesEncryptor->disablePadding();
        $this->aesEncryptor->setIV($this->iv);

        if ($type === self::TYPE_SYMMETRIC) {
            $this->aesEncryptor->setKey($key);
        } else {
            $rsa = PublicKeyLoader::load($key);
            if (!($rsa instanceof RSA\PublicKey)) {
                throw new \InvalidArgumentException('Expected an RSA public key');
            }

            $this->rsaEncryptor = $rsa;
        }
    }

    /**
     * Initializes the class with an AES key.
     *
     * The key will be used to encrypt the data written to the output stream.
     *
     * A random initialization vector is also generated.
     *
     * @param string          $key    An AES key for encrypting data
     * @param StreamInterface $output The output stream to write the data
     *
     * @throws \RuntimeException If the IV cannot be generated
     */
    public static function fromSymmetric(string $key, StreamInterface $output): self
    {
        return new self(self::TYPE_SYMMETRIC, $key, $output);
    }

    /**
     * Initializes the class with an RSA public key.
     *
     * The key is used to encrypt a generated AES key, which is what is actually used to encrypt the data.
     *
     * A random initialization vector is also generated.
     *
     * @param string          $key    An RSA public key
     * @param StreamInterface $output The output stream to write the data
     *
     * @throws \RuntimeException If the IV cannot be generated
     */
    public static function fromAsymmetric(string $key, StreamInterface $output): self
    {
        return new self(self::TYPE_ASYMMETRIC, $key, $output);
    }

    /**
     * Encrypts and then writes the provided string.
     *
     * The string is AES-encrypted, either using the provided key
     * (see {@see EncryptionStream::fromSymmetric()}), or a key generated and
     * RSA-encrypted on initialization (see {@see EncryptionStream::fromAsymmetric()}).
     *
     * If this is the first data being written, then the encryption header is written first
     * (see {@see EncryptionStream::writeHeader()}).
     *
     * Each block of data is padded to the block size (16 bytes) as necessary, using PKCS7 padding.
     *
     * Note that if a block is an exact multiple of 16 bytes, 16 additional bytes of padding will be added.
     *
     * @param string $string The string to encrypt and write
     *
     * @return int The number of bytes written
     */
    public function write($string): int
    {
        $bytes = $this->writeHeader();
        $bytes += $this->stream->write($this->aesEncryptor->encrypt(self::addPadding($string)));

        return $bytes;
    }

    /**
     * Write the header of the encrypted message.
     *
     * If AES-encrypted, then the format is:
     *
     * * 8 byte header (0x4241434b57505550).
     * * 1 byte version (\x02). This is for the new format, supporting a custom IV.
     *   The old format only supported a null IV.
     * * 1 byte type (\x01). This specifies symmetric key encryption.
     * * 16 bytes containing the clear-text IV.
     *
     * If RSA-encrypted, then the format is:
     *
     * * 8 byte header (0x4241434b57505550).
     * * 1 byte version (\x02). This is for the new format, supporting a custom IV
     *   and larger RSA keys. The old format only supported a null IV and RSA keys
     *   of less than 2048-bits.
     * * 1 byte type (\x02). This specifies asymmetric key encryption.
     * * 2 byte encoded key length (length in bytes, not bits).
     * * AES key encrypted with the given RSA public key. Key length must be equal to
     *   number in the encoded length.
     * * 16 bytes containing the clear-text IV.
     *
     * @throws \RuntimeException If the header cannot be written
     *
     * @return int The number of bytes written in the header (0 if header already written)
     */
    private function writeHeader(): int
    {
        if ($this->tell() !== 0) {
            return 0;
        }

        $prefix = self::HEADER . \chr(self::VERSION) . \chr($this->type);

        if ($this->type === self::TYPE_SYMMETRIC) {
            return $this->stream->write($prefix . $this->iv);
        }

        // Otherwise, RSA
        \assert($this->rsaEncryptor !== null);

        $key = Random::string(32);
        $this->aesEncryptor->setKey($key);
        $encryptedKey = $this->rsaEncryptor->encrypt($key);
        if (!\is_string($encryptedKey)) {
            throw new \RuntimeException('Could not encrypt key');
        }

        return $this->stream->write(
            $prefix . pack('n', \strlen($encryptedKey)) . $encryptedKey . $this->iv
        );
    }

    /**
     * Add PKCS7-style padding.
     */
    private static function addPadding(string $string): string
    {
        $length = \strlen($string);
        $paddingNeeded = $length % 16;

        if ($paddingNeeded > 0) {
            $pad = 16 - ($paddingNeeded);
            $string = str_pad($string, $length + $pad, \chr($pad));
        }

        return $string;
    }
}
