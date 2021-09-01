<?php

namespace Inpsyde\BackWPup\Http\Message\Decorator;

use Inpsyde\BackWPup\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestInterface;

trait StreamRequestTrait
{
    /**
     * The stream factory for creating streams.
     *
     * @var StreamFactoryInterface
     */
    protected $streamFactory;

    /**
     * Constructs a FormRequest.
     *
     * @param RequestInterface       $request       The request to decorate
     * @param StreamFactoryInterface $streamFactory The factory for creating streams
     */
    public function __construct(RequestInterface $request, StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
        parent::__construct($request);
    }

    /**
     * {@inheritdoc}
     */
    protected function createInstance(RequestInterface $request)
    {
        return new static($request, $this->streamFactory);
    }
}
