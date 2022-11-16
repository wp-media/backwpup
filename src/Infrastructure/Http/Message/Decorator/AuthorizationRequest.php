<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator;

use Inpsyde\BackWPup\Infrastructure\Http\Authentication\BasicAuthCredentials;
use Inpsyde\BackWPup\Infrastructure\Http\Authentication\Exception\CouldNotDecodeBasicAuthenticationToken;

/**
 * Authorization request decorator.
 *
 * Adds authorization headers to a request
 */
final class AuthorizationRequest extends RequestDecorator
{
    /**
     * @var string
     */
    private const AUTHORIZATION_HEADER = 'Authorization';

    /**
     * Gets the username and password from a basic authentication header.
     */
    public function getBasicAuth(): ?BasicAuthCredentials
    {
        if (!$this->hasHeader(self::AUTHORIZATION_HEADER)) {
            return null;
        }

        $authorization = explode(' ', $this->getHeaderLine(self::AUTHORIZATION_HEADER), 2);
        if (strtolower($authorization[0]) !== 'basic') {
            return null;
        }

        try {
            return BasicAuthCredentials::fromToken($authorization[1]);
        } catch (CouldNotDecodeBasicAuthenticationToken $exception) {
            return null;
        }
    }

    /**
     * Sets basic authentication on a request.
     */
    public function withBasicAuth(BasicAuthCredentials $auth): self
    {
        return $this->withHeader(self::AUTHORIZATION_HEADER, 'Basic ' . $auth->asToken());
    }

    /**
     * Gets the OAuth token from the Authorization header.
     */
    public function getOAuthToken(): ?string
    {
        if (!$this->hasHeader(self::AUTHORIZATION_HEADER)) {
            return null;
        }

        $authorization = explode(' ', $this->getHeaderLine(self::AUTHORIZATION_HEADER), 2);
        if (strtolower($authorization[0]) !== 'bearer') {
            return null;
        }

        return trim($authorization[1]);
    }

    /**
     * Sets the OAuth token on a request.
     *
     * @param string $token The OAuth token
     */
    public function withOAuthToken(string $token): self
    {
        return $this->withHeader(self::AUTHORIZATION_HEADER, sprintf('Bearer %s', $token));
    }
}
