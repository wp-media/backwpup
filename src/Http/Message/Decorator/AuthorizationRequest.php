<?php

namespace Inpsyde\BackWPup\Http\Message\Decorator;

/**
 * Authorization request decorator.
 *
 * Adds authorization headers to a request
 */
class AuthorizationRequest extends RequestDecorator
{
    /**
     * Gets the username and password from a basic authentication header.
     *
     * @return array|null The first element is the username, and the second is the password.
     *                    null if basic authentication was not set in this request.
     */
    public function getBasicAuth()
    {
        if (!$this->hasHeader('Authorization')) {
            return null;
        }

        $authorization = explode(' ', $this->getHeaderLine('Authorization'), 2);
        if ('basic' !== strtolower($authorization[0])) {
            return null;
        }

        $credentials = explode(':', base64_decode(trim($authorization[1])), 2);
        if (2 !== count($credentials)) {
            return null;
        }

        return $credentials;
    }

    /**
     * Sets basic authentication on a request.
     *
     * @param string $username
     * @param string $password
     *
     * @return AuthorizationRequest
     */
    public function withBasicAuth($username, $password)
    {
        return $this->withHeader('Authorization', 'Basic '.base64_encode("$username:$password"));
    }

    /**
     * Gets the OAuth token from the Authorization header.
     *
     * @return string|null returns the token if it exists, or null otherwise
     */
    public function getOAuthToken()
    {
        if (!$this->hasHeader('Authorization')) {
            return null;
        }

        $authorization = explode(' ', $this->getHeaderLine('Authorization'), 2);
        if ('bearer' !== strtolower($authorization[0])) {
            return null;
        }

        return trim($authorization[1]);
    }

    /**
     * Sets the OAuth token on a request.
     *
     * @param string $token The OAuth token
     *
     * @return AuthorizationRequest
     */
    public function withOAuthToken($token)
    {
        return $this->withHeader('Authorization', "Bearer $token");
    }
}
