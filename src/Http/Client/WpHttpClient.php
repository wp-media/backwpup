<?php

namespace Inpsyde\BackWPup\Http\Client;

use Inpsyde\BackWPup\Http\Client\Exception\NetworkException;
use Inpsyde\BackWPup\Http\Client\Exception\RequestException;
use Inpsyde\BackWPup\Http\Message\ResponseFactoryInterface;
use Inpsyde\BackWPup\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A wrapper of WP_Http.
 */
class WpHttpClient implements ClientInterface
{
    /**
     * List of allowable options.
     *
     * Not all options are included, because some are taken from the request.
     *
     * Excluded options: method, httpversion, headers, cookies, body
     *
     * The keys are the options, and the values are the allowed types.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_http/request/#parameters
     */
    const WP_HTTP_OPTIONS = [
        'timeout' => 'numeric',
        'redirection' => 'int',
        'user-agent' => 'string',
        'reject_unsafe_urls' => 'bool',
        'blocking' => 'bool',
        'compress' => 'bool',
        'decompress' => 'bool',
        'sslverify' => 'bool',
        'sslcertificates' => 'string',
        'stream' => 'bool',
        'filename' => 'string',
        'limit_response_size' => 'int',
    ];

    /**
     * The factory for creating responses.
     *
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * The factory for creating streams.
     *
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * Options to pass to the client when sending the request.
     *
     * @var array
     */
    private $options;

    /**
     * Construct a new WpHttpClient.
     */
    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, array $options = [])
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request)
    {
        $this->assertUri($request);

        $response = wp_remote_request(
            (string) $request->getUri(),
            $this->buildHttpOptions($request)
        );

        if (is_wp_error($response)) {
            if ('http_request_not_executed' === $response->get_error_code()) {
                // Not a network error, so throw RequestException
                throw new RequestException($response->get_error_message(), $request);
            } else {
                throw new NetworkException($response->get_error_message(), $request);
            }
        }

        return $this->prepareResponse($response);
    }

    /**
     * Configure allowed options.
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(array_keys(self::WP_HTTP_OPTIONS));

        foreach (self::WP_HTTP_OPTIONS as $option => $type) {
            $resolver->setAllowedTypes($option, $type);
        }

        $resolver->setDefaults([
            // Do not reject unsafe URLs
            // This only causes WordPress to call wp_http_validate_url(), which we call manually
            'reject_unsafe_urls' => false,
            // Always enable blocking, as we currently do not support async requests
            'blocking' => true,
        ]);

        $resolver->setAllowedValues('reject_unsafe_urls', false);
        $resolver->setAllowedValues('blocking', true);
        $resolver->setAllowedValues('sslcertificates', function ($value) {
            return file_exists($value);
        });
        $resolver->setAllowedValues('filename', function ($value) {
            return wp_is_writable(dirname($value));
        });
    }

    /**
     * Builds the options to pass to `wp_remote_request()`.
     *
     * @param RequestInterface $request The HTTP request being sent
     *
     * @return array The HTTP options
     */
    protected function buildHttpOptions(RequestInterface $request)
    {
        $options = [
            'method' => $request->getMethod(),
            'httpversion' => $request->getProtocolVersion(),
            'headers' => $this->buildHeadersFromRequest($request),
            'cookies' => $this->buildCookieArray($request),
            'body' => (string) $request->getBody(),
        ] + $this->options;

        return $options;
    }

    /**
     * Build the array of headers.
     *
     * @param RequestInterface $request The HTTP request being sent
     *
     * @return array The array of headers taken from the request
     */
    protected function buildHeadersFromRequest(RequestInterface $request)
    {
        $headers = $request
            ->withoutHeader('Host')
            ->withoutHeader('Cookie')
            ->getHeaders();

        array_walk($headers, function (&$value, $name) use ($request) {
            $value = $request->getHeaderLine($name);
        });

        return $headers;
    }

    /**
     * Builds an array of cookies taken from the given request.
     *
     * @param RequestInterface $request The HTTP request being sent
     *
     * @return array An array of cookies where the key is the cookie name,
     *               and the value is the cookie value
     */
    protected function buildCookieArray(RequestInterface $request)
    {
        $cookies = [];
        $cookieHeader = trim($request->getHeaderLine('Cookie'));
        if (empty($cookieHeader)) {
            return $cookies;
        }

        $cookiePairs = explode(';', $cookieHeader);

        foreach ($cookiePairs as $cookie) {
            if (empty(trim($cookie))) {
                continue;
            }

            if (false === strpos($cookie, '=')) {
                // Technically invalid but should handle anyway
                $cookies[trim($cookie)] = '';
            } else {
                list($name, $value) = explode('=', $cookie, 2);
                $cookies[trim($name)] = trim($value);
            }
        }

        return $cookies;
    }

    /**
     * Prepares the response object from the WP response.
     *
     * @param array $result The response from `wp_remote_request()`
     *
     * @return \Psr\Http\Message\ResponseInterface The response object
     */
    protected function prepareResponse(array $result)
    {
        $code = wp_remote_retrieve_response_code($result);
        $message = wp_remote_retrieve_response_message($result);

        $response = $this->responseFactory->createResponse($code, $message)
            ->withBody($this->streamFactory->createStream(wp_remote_retrieve_body($result)));

        // Check if we can get the response object
        if (isset($result['http_response'])) {
            $protocolVersion = $result['http_response']->get_response_object()->protocol_version;
            // WordPress formats as a float, so convert to string
            $response = $response->withProtocolVersion(sprintf('%0.1f', $protocolVersion));
        }

        $headers = wp_remote_retrieve_headers($result);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * Asserts that the URI is valid.
     *
     * @param RequestInterface $request The HTTP request being sent
     *
     * @throws RequestException if the URI is not valid
     */
    protected function assertUri(RequestInterface $request)
    {
        $uri = (string) $request->getUri();

        if (empty($uri)) {
            throw new RequestException(__('URI must not be empty.', 'backwpup'), $request);
        }

        if (false === wp_http_validate_url($uri)) {
            throw new RequestException(__('The given URI is invalid.', 'backwpup'), $request);
        }
    }
}
