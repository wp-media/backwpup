<?php

declare(strict_types=1);

namespace Inpsyde\BackWPup\Infrastructure\Http\Client;

use Inpsyde\BackWPup\Infrastructure\Http\Client\Exception\NetworkException;
use Inpsyde\BackWPup\Infrastructure\Http\Client\Exception\RequestException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A wrapper of WP_Http.
 *
 * @phpstan-type WpOptions array{timeout?: int, redirection?: int,
 *                               user-agent?: string,
 *                               reject_unsafe_urls?: bool,
 *                               blocking?: bool, compress?: bool,
 *                               decompress?: bool, sslverify?: bool,
 *                               sslcertificates?: string, stream?: bool, filename?: string,
 *                               limit_response_size?: int}
 *
 * @phpstan-type HttpOptions array{method: string, httpversion: string,
 *                                 headers: array<string[]>,
 *                                 cookies: array<string, string>, body: string,
 *                                 timeout?: int, redirection?: int,
 *                                 user-agent?: string,
 *                                 reject_unsafe_urls?: bool,
 *                                 blocking?: bool, compress?: bool,
 *                                 decompress?: bool, sslverify?: bool,
 *                                 sslcertificates?: string, stream?: bool, filename?: string,
 *                                 limit_response_size?: int}
 */
final class WpHttpClient implements ClientInterface
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
     *
     * @var array<string, string>
     */
    private const WP_HTTP_OPTIONS = [
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
     * @var string
     */
    private const RESPONSE_KEY = 'http_response';

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
     * @var WpOptions
     */
    private $options;

    /**
     * @phpstan-param WpOptions $options
     *
     * @throws NoSuchOptionException Thrown if an invalid option is given
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
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->assertUri($request);

        $response = wp_remote_request(
            (string) $request->getUri(),
            $this->buildHttpOptions($request)
        );

        if (is_wp_error($response)) {
            if ($response->get_error_code() === 'http_request_not_executed') {
                // Not a network error, so throw RequestException
                throw new RequestException($response->get_error_message(), $request);
            }

            throw new NetworkException($response->get_error_message(), $request);
        }

        return $this->prepareResponse($response);
    }

    /**
     * Configure allowed options.
     */
    private function configureOptions(OptionsResolver $resolver): void
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
        $resolver->setAllowedValues('sslcertificates', static function ($value): bool {
            return file_exists($value);
        });
        $resolver->setAllowedValues('filename', static function ($value): bool {
            return wp_is_writable(\dirname($value));
        });
    }

    /**
     * Builds the options to pass to `wp_remote_request()`.
     *
     * @param RequestInterface $request The HTTP request being sent
     *
     * @phpstan-return HttpOptions
     */
    private function buildHttpOptions(RequestInterface $request): array
    {
        return [
            'method' => $request->getMethod(),
            'httpversion' => $request->getProtocolVersion(),
            'headers' => $this->buildHeadersFromRequest($request),
            'cookies' => $this->buildCookieArray($request),
            'body' => (string) $request->getBody(),
        ] + $this->options;
    }

    /**
     * Build the array of headers.
     *
     * @param RequestInterface $request The HTTP request being sent
     *
     * @return array<string[]> The array of headers taken from the request
     */
    private function buildHeadersFromRequest(RequestInterface $request): array
    {
        $headers = $request
            ->withoutHeader('Host')
            ->withoutHeader('Cookie')
            ->getHeaders()
        ;

        array_walk($headers, static function (&$value, $name) use ($request): void {
            $value = $request->getHeaderLine($name);
        });

        return $headers;
    }

    /**
     * Builds an array of cookies taken from the given request.
     *
     * @param RequestInterface $request The HTTP request being sent
     *
     * @return array<string, string>
     */
    private function buildCookieArray(RequestInterface $request): array
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

            if (strpos($cookie, '=') === false) {
                // Technically invalid but should handle anyway
                $cookies[trim($cookie)] = '';
            } else {
                [$name, $value] = explode('=', $cookie, 2);
                $cookies[trim($name)] = trim($value);
            }
        }

        return $cookies;
    }

    /**
     * Prepares the response object from the WP response.
     *
     * @param mixed[] $result
     *
     * @return ResponseInterface The response object
     */
    private function prepareResponse(array $result): ResponseInterface
    {
        $code = (int) wp_remote_retrieve_response_code($result);
        $message = wp_remote_retrieve_response_message($result);

        $response = $this->responseFactory->createResponse($code, $message)
            ->withBody($this->streamFactory->createStream(wp_remote_retrieve_body($result)))
        ;

        // Check if we can get the response object
        if (
            isset($result[self::RESPONSE_KEY])
            && $result[self::RESPONSE_KEY] instanceof \WP_HTTP_Requests_Response
        ) {
            $protocolVersion = $result[self::RESPONSE_KEY]->get_response_object()->protocol_version;
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
    private function assertUri(RequestInterface $request): void
    {
        $uri = (string) $request->getUri();

        if (empty($uri)) {
            throw new RequestException(__('URI must not be empty.', 'backwpup'), $request);
        }

        if (wp_http_validate_url($uri) === false) {
            throw new RequestException(__('The given URI is invalid.', 'backwpup'), $request);
        }
    }
}
