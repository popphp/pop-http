<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Uri;

/**
 * HTTP client handler interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
abstract class AbstractHandler implements HandlerInterface
{

    /**
     * URI string
     * @var ?string
     */
    protected ?string $uri = null;

    /**
     * HTTP version
     * @var string
     */
    protected string $httpVersion = '1.1';

    /**
     * Client resource object
     * @var mixed
     */
    protected mixed $resource = null;

    /**
     * The request currently being (or last) prepared, carried so a thrown
     * Client\Handler\Exception can implement NetworkExceptionInterface::getRequest()
     * @var ?Request
     */
    protected ?Request $request = null;

    /**
     * Determine whether or not resource is available
     *
     * @return bool
     */
    public function hasResource(): bool
    {
        return ($this->resource !== null);
    }

    /**
     * Get the resource
     *
     * @return mixed
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * Determine whether or not there is a URI string
     *
     * @return bool
     */
    public function hasUri(): bool
    {
        return ($this->uri !== null);
    }

    /**
     * Get the URI string
     *
     * @return ?string
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Get the URI string
     *
     * @return string
     */
    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }

    /**
     * Get the URI as an object
     *
     * @return Uri
     */
    public function getUriObject(): Uri
    {
        return new Uri($this->uri);
    }

    /**
     * Get the resource (alias method)
     *
     * @return mixed
     */
    public function resource(): mixed
    {
        return $this->resource;
    }

    /**
     * Method to send the request
     */
    abstract public function send();

    /**
     * Method to reset the handler
     *
     * @return AbstractHandler
     */
    abstract public function reset(): AbstractHandler;

    /**
     * Close the handler connection
     *
     * @return void
     */
    abstract public function disconnect(): void;

    /**
     * Collect the flat list of header strings for a request, shared by every handler
     *
     * @param  \Pop\Http\Client\Request $request
     * @return array
     */
    protected function collectRequestHeaders(\Pop\Http\Client\Request $request): array
    {
        $headers = [];

        if (!$request->hasHeaders()) {
            return $headers;
        }

        foreach ($request->getHeaderObjects() as $header => $value) {
            if (($header === 'Content-Length') && ($request->isNoContentLength() || ($request->getMethod() === 'GET'))) {
                continue;
            }
            foreach (is_array($value) ? $value : [$value] as $val) {
                $headers[] = (string)$val;
            }
        }

        return $headers;
    }

    /**
     * Resolve what a request's outbound body should be, shared by every handler.
     * Multipart requests resolve to the curl-native array shape (scalars + CURLFile),
     * which Curl can pass directly to CURLOPT_POSTFIELDS for zero-copy file streaming;
     * handlers without that capability (Stream) fall back to Body\Multipart::build().
     *
     * @param  \Pop\Http\Client\Request $request
     * @return array
     */
    protected function resolveRequestBody(\Pop\Http\Client\Request $request): array
    {
        $queryString = null;
        $body        = null;

        if ($request->hasQuery()) {
            $queryString = '?' . $request->getQuery()->prepare()->getDataContent();
        }

        if ($request->hasData()) {
            if ($request->isMultipart()) {
                $body = \Pop\Http\Body\Multipart::toArray($request->getData()->getData());
            } else if ($request->hasDataContent()) {
                if (($queryString === null) && ($request->getMethod() === 'GET') && (!$request->hasRequestType() || $request->isUrlEncoded())) {
                    $queryString = '?' . $request->getDataContent();
                } else {
                    $body = $request->useRawData() ? $request->getData()->getData() : $request->getDataContent();
                }
            }
        } else if ($request->hasBodyContent()) {
            $request->addHeader('Content-Length', (string)$request->getBodyContentLength());
            $body = $request->getBodyContent();
        }

        return ['queryString' => $queryString, 'body' => $body];
    }

}
