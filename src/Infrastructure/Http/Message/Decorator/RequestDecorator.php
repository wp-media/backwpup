<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Message\Decorator;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Webmozart\Assert\Assert;

/**
 * Decorates a request with additional functionality.
 *
 * All calls to the decorator will also be passed through to the original object
 * (or nested decorators as appropriate).
 *
 * @author Brandon Olivares <b.olivares@inpsyde.com>
 */
abstract class RequestDecorator implements RequestInterface
{
    /**
     * The decorated request.
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * Constructs a request decorator.
     *
     * @param RequestInterface $request The decorated request
     */
    final public function __construct(RequestInterface $request)
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
     * @param string  $method
     * @param mixed[] $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $callback = [$this->request, $method];
        Assert::isCallable($callback);

        $return = $callback(...$args);

        return $return instanceof RequestInterface
            ? $this->createInstance($return)
            : $return;
    }

    /**
     * Checks if this request is decorated by the given decorator.
     *
     * @param class-string $decorator Fully-qualified class name of the decorator to check
     */
    final public function isDecoratedBy($decorator): bool
    {
        if ($this instanceof $decorator) {
            return true;
        }
        if ($this->request instanceof self) {
            return $this->request->isDecoratedBy($decorator);
        }

        // Is the undecorated request, so return false
        return false;
    }

    /**
     * {@inheritdoc}
     */
    final public function getRequestTarget(): string
    {
        return $this->request->getRequestTarget();
    }

    /**
     * {@inheritdoc}
     */
    final public function withRequestTarget($requestTarget)
    {
        return $this->createInstance($this->request->withRequestTarget($requestTarget));
    }

    /**
     * {@inheritdoc}
     */
    final public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    final public function withMethod($method)
    {
        return $this->createInstance($this->request->withMethod($method));
    }

    /**
     * {@inheritdoc}
     */
    final public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    /**
     * {@inheritdoc}
     */
    final public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->createInstance($this->request->withUri($uri, $preserveHost));
    }

    /**
     * {@inheritdoc}
     */
    final public function getProtocolVersion(): string
    {
        return $this->request->getProtocolVersion();
    }

    /**
     * {@inheritdoc}
     */
    final public function withProtocolVersion($version)
    {
        return $this->createInstance($this->request->withProtocolVersion($version));
    }

    /**
     * {@inheritdoc}
     */
    final public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    /**
     * {@inheritdoc}
     */
    final public function hasHeader($name): bool
    {
        return $this->request->hasHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function getHeader($name): array
    {
        return $this->request->getHeader($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function getHeaderLine($name): string
    {
        return $this->request->getHeaderLine($name);
    }

    /**
     * {@inheritdoc}
     */
    final public function withHeader($name, $value)
    {
        return $this->createInstance($this->request->withHeader($name, $value));
    }

    /**
     * {@inheritdoc}
     */
    final public function withAddedHeader($name, $value)
    {
        return $this->createInstance($this->request->withAddedHeader($name, $value));
    }

    /**
     * {@inheritdoc}
     */
    final public function withoutHeader($name)
    {
        return $this->createInstance($this->request->withoutHeader($name));
    }

    /**
     * {@inheritdoc}
     */
    final public function getBody(): StreamInterface
    {
        return $this->request->getBody();
    }

    /**
     * {@inheritdoc}
     */
    final public function withBody(StreamInterface $body)
    {
        return $this->createInstance($this->request->withBody($body));
    }

    /**
     * Creates an instance of the decorator with the given request.
     *
     * Allows for dynamically creating an instance of the decorator.
     * This allows concrete decorators to have other dependencies passed in the constructor.
     * They can override ::createInstance() and take over proper instantiation.
     *
     * @param RequestInterface $request The request to decorate
     *
     * @return static
     */
    protected function createInstance(RequestInterface $request)
    {
        return new static($request);
    }
}
