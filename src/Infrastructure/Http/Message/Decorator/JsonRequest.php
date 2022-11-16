<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator;

use Inpsyde\BackWPup\Infrastructure\Http\Message\Exception\CouldNotDecodeJsonData;
use Inpsyde\BackWPup\Infrastructure\Http\Message\Exception\CouldNotEncodeJsonData;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * JSON request decorator.
 *
 * Turns an array of data into a JSON request body.
 *
 * @author Brandon Olivares <b.olivares@inpsyde.com>
 */
final class JsonRequest extends RequestDecorator
{
    /**
     * Get JSON array from request.
     *
     * Request body must be formatted as JSON data.
     *
     * @throws CouldNotDecodeJsonData
     *
     * @return mixed|null
     */
    public function getJsonData()
    {
        $data = json_decode((string) $this->request->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw CouldNotDecodeJsonData::withError(json_last_error_msg());
        }

        return $data;
    }

    /**
     * Sets JSON data in the request body.
     *
     * @param mixed $data The data to use as the JSON body
     *
     * @throws CouldNotEncodeJsonData
     */
    public function withJsonData($data, StreamFactoryInterface $streamFactory): self
    {
        $data = json_encode($data);
        if ($data === false) {
            throw CouldNotEncodeJsonData::withError(json_last_error_msg());
        }

        return $this
            ->withHeader('Content-Type', 'application/json')
            ->withBody($streamFactory->createStream($data))
        ;
    }
}
