<?php

namespace Inpsyde\BackWPup\Http\Message\Decorator;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Decorates a request with additional functionality.
 *
 * All calls to the decorator will also be passed through to the original object
 * (or nested decorators as appropriate).
 */
abstract class RequestDecorator implements RequestInterface
{
    /**
     * The decorated request.
     *
     * @var \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * Constructs a request decorator.
     *
     * @param \Psr\Http\Message\RequestInterface $request The decorated request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Call custom methods on request.
     *
     * If the method returns an instance of \Psr\Http\Message\RequestInterface,
     * an instance of the current decorator is returned, wrapping the result.
     * Otherwise the result is returned as-is.
     *
     * @param string $method
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $return = call_user_func_array([$this->request, $method], $args);

        return $return instanceof RequestInterface
            ? $this->createInstance($return)
            : $return;
    }

    /**
     * Creates an instance of the decorator with the given request.
     *
     * Allows for dynamically creating an instance of the decorator.
     * This allows concrete decorators to have other dependencies passed in the constructor.
     * They can override ::createInstance() and take over proper instantiation.
     *
     * @param \Psr\Http\Message\RequestInterface $request The request to decorate
     *
     * @return RequestDecorator
     */
    protected function createInstance(RequestInterface $request)
    {
        return new static($request);
    }

    /**
     * Checks if this request is decorated by the given decorator.
     *
     * @param string Fully-qualified class name of the decorator to check
     *
     * @return bool
     */
    public function isDecoratedBy($decorator)
    {
        if ($this instanceof $decorator) {
            return true;
        } elseif ($this->request instanceof RequestDecorator) {
            return $this->request->isDecoratedBy($decorator);
        }

        // Is the undecorated request, so return false
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTarget()
    {
        return $this->request->getRequestTarget();
    }

    /**
     * {@inheritdoc}
     */
    public function withRequestTarget($requestTarget)
    {
        return $this->createInstance($this->request->withRequestTarget($requestTarget));
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod()
    {
        return $this->request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function withMethod($method)
    {
        return $this->createInstance($this->request->withMethod($method));
    }

    /**
     * {@inheritdoc}
     */
    public function getUri()
    {
        return $this->request->getUri();
    }

    /**
     * {@inheritdoc}
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->createInstance($this->request->withUri($uri, $preserveHost));
    }

    /**
     * {@inheritdoc}
     */
    public function getProtocolVersion()
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * {@inheritdoc}
     */
    public function withProtocolVersion($version)
    {
        return $this->createInstance($this->request->withProtocolVersion($version));
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->request->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($name)
    {
        return $this->request->hasHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name)
    {
        return $this->request->getHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name)
    {
        return $this->request->getHeaderLine($name);
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader($name, $value)
    {
        return $this->createInstance($this->request->withHeader($name, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function withAddedHeader($name, $value)
    {
        return $this->createInstance($this->request->withAddedHeader($name, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function withoutHeader($name)
    {
        return $this->createInstance($this->request->withoutHeader($name));
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->request->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function withBody(StreamInterface $body)
    {
        return $this->createInstance($this->request->withBody($body));
    }
}
