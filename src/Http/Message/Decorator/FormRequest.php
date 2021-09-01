<?php

namespace Inpsyde\BackWPup\Http\Message\Decorator;

/**
 * Form params request decorator.
 *
 * Sets an array of form parameters on a request as an application/x-www-form-urlencoded request body.
 */
class FormRequest extends RequestDecorator
{
    use StreamRequestTrait;

    /**
     * Gets array of form params from the request.
     *
     * Request body must be formatted as application/x-www-form-urlencoded.
     *
     * @return array The array of form params
     */
    public function getFormParams()
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
     * @param array $params The form parameters
     *
     * @return FormRequest
     */
    public function withFormParams(array $params)
    {
        return $this
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream(http_build_query($params)));
    }
}
