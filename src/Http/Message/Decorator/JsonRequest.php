<?php

namespace Inpsyde\BackWPup\Http\Message\Decorator;

/**
 * JSON request decorator.
 *
 * Turns an array of data into a JSON request body.
 */
class JsonRequest extends RequestDecorator
{
    use StreamRequestTrait;

    /**
     * Get JSON array from request.
     *
     * Request body must be formatted as JSON data.
     *
     * @throws \RuntimeException Thrown if request body is not valid JSON
     */
    public function getJsonData()
    {
        $data = json_decode((string) $this->request->getBody(), true);

        if (null === $data && JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException(__('Request body is not valid JSON', 'backwpup'));
        }

        return $data;
    }

    /**
     * Sets JSON data in the request body.
     *
     * @param mixed $data The data to use as the JSON body
     *
     * @return JsonRequest
     */
    public function withJsonData($data)
    {
        $data = json_encode($data);
        if (false === $data) {
            throw new \RuntimeException(sprintf(__('Cannot encode data into JSON. Got error: %s', 'backwpup'), json_last_error_msg()));
        }

        return $this
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($data));
    }
}
