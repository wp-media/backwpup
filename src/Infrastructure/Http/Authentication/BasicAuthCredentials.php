<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Authentication;

use Inpsyde\BackWPup\Infrastructure\Http\Authentication\Exception\CouldNotDecodeBasicAuthenticationToken;
use Webmozart\Assert\Assert;

final class BasicAuthCredentials
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @param non-empty-string $username
     * @param non-empty-string $password
     */
    private function __construct(string $username, string $password)
    {
        Assert::stringNotEmpty($username, __('Username cannot be empty'));
        Assert::stringNotEmpty($password, __('Password cannot be empty'));

        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @param non-empty-string $username
     * @param non-empty-string $password
     */
    public static function fromUsernameAndPassword(string $username, string $password): self
    {
        return new self($username, $password);
    }

    /**
     * @throws CouldNotDecodeBasicAuthenticationToken
     */
    public static function fromToken(string $token): self
    {
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            throw CouldNotDecodeBasicAuthenticationToken::withToken($token);
        }

        $credentials = explode(':', $decoded, 2);
        if (
            \count($credentials) !== 2
            || empty($credentials[0])
            || empty($credentials[1])
        ) {
            throw CouldNotDecodeBasicAuthenticationToken::withToken($token);
        }

        return new self($credentials[0], $credentials[1]);
    }

    public function asToken(): string
    {
        return base64_encode(sprintf('%s:%s', $this->username, $this->password));
    }
}
