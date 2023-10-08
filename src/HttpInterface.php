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
 * HTTP interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
interface HttpInterface
{

    /**
     * Set a header
     *
     * @param  Header|string $header
     * @param  ?string       $value
     * @return HttpInterface
     */
    public function addHeader(Header|string $header, ?string $value = null): HttpInterface;

    /**
     * Set all headers
     *
     * @param  array $headers
     * @return HttpInterface
     */
    public function addHeaders(array $headers): HttpInterface;

    /**
     * Get a header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader(string $name): mixed;

    /**
     * Get header value
     *
     * @param  string $name
     * @param  int    $i
     * @return mixed
     */
    public function getHeaderValue(string $name, int $i = 0): mixed;

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
     * @return HttpInterface
     */
    public function removeHeader(string $name): HttpInterface;

    /**
     * Remove all headers
     *
     * @return HttpInterface
     */
    public function removeHeaders(): HttpInterface;

    /**
     * Set the body
     *
     * @param  string|Body $body
     * @return HttpInterface
     */
    public function setBody(string|Body $body): HttpInterface;

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
     * @return HttpInterface
     */
    public function removeBody(): HttpInterface;

}