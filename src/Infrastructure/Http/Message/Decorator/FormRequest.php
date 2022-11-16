<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator;

use Psr\Http\Message\StreamFactoryInterface;

/**
 * Form params request decorator.
 *
 * Sets an array of form parameters on a request as an application/x-www-form-urlencoded request body.
 */
final class FormRequest extends RequestDecorator
{
    /**
     * Gets array of form params from the request.
     *
     * Request body must be formatted as application/x-www-form-urlencoded.
     *
     * @return array<string, mixed> The array of form params
     */
    public function getFormParams(): array
    {
        $body = (string) $this->request->getBody();
        parse_str($body, $params);

        return $params;
    }

    /**
     * Turns an array of form parameters into a URL-encoded request body.
     *
     * Also sets the Content-Type header to application/x-www-form-url-encoded.
     *
     * @param array<string, mixed> $params The form parameters
     */
    public function withFormParams(array $params, StreamFactoryInterface $streamFactory): self
    {
        return $this
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($streamFactory->createStream(http_build_query($params)))
        ;
    }
}
