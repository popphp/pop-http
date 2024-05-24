<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http;

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * HTTP request/response interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
interface RequestResponseInterface
{

    /**
     * Set all headers (clear out any existing headers)
     *
     * @param  array $headers
     * @return RequestResponseInterface
     */
    public function setHeaders(array $headers): RequestResponseInterface;

    /**
     * Set a header
     *
     * @param  Header|string|int $header
     * @param  ?string           $value
     * @return RequestResponseInterface
     */
    public function addHeader(Header|string|int $header, ?string $value = null): RequestResponseInterface;

    /**
     * Add all headers
     *
     * @param  array $headers
     * @return RequestResponseInterface
     */
    public function addHeaders(array $headers): RequestResponseInterface;

    /**
     * Get a header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader(string $name): mixed;

    /**
     * Get a header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeaderAsString(string $name): mixed;

    /**
     * Get header value
     *
     * @param  string $name
     * @param  int    $i
     * @return mixed
     */
    public function getHeaderValue(string $name, int $i = 0): mixed;

    /**
     * Get header value as string
     *
     * @param  string $name
     * @param  int    $i
     * @return string|null
     */
    public function getHeaderValueAsString(string $name, int $i = 0): string|null;

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Get all header values as associative array
     *
     * @return array
     */
    public function getHeadersAsArray(): array;

    /**
     * Get all header values formatted string
     *
     * @param  ?string $status
     * @param  string  $eol
     * @return string
     */
    public function getHeadersAsString(?string $status = null, string $eol = "\r\n"): string;

    /**
     * Determine if there are headers
     *
     * @return bool
     */
    public function hasHeaders(): bool;

    /**
     * Has a header
     *
     * @param  string $name
     * @return bool
     */
    public function hasHeader(string $name): bool;

    /**
     * Remove a header
     *
     * @param  string $name
     * @return RequestResponseInterface
     */
    public function removeHeader(string $name): RequestResponseInterface;

    /**
     * Remove all headers
     *
     * @return RequestResponseInterface
     */
    public function removeHeaders(): RequestResponseInterface;

    /**
     * Set the body
     *
     * @param  string|Body $body
     * @return RequestResponseInterface
     */
    public function setBody(string|Body $body): RequestResponseInterface;

    /**
     * Get the body
     *
     * @return Body
     */
    public function getBody(): Body;

    /**
     * Get body content
     *
     * @return mixed
     */
    public function getBodyContent(): mixed;

    /**
     * Get body content length
     *
     * @param  bool $mb
     * @return int
     */
    public function getBodyContentLength(bool $mb = false): int;

    /**
     * Has a body
     *
     * @return bool
     */
    public function hasBody(): bool;

    /**
     * Has body content
     *
     * @return bool
     */
    public function hasBodyContent(): bool;

    /**
     * Decode the body content
     *
     * @param  ?string $body
     * @return Body
     */
    public function decodeBodyContent(?string $body = null): Body;

    /**
     * Remove the body
     *
     * @return RequestResponseInterface
     */
    public function removeBody(): RequestResponseInterface;

}
