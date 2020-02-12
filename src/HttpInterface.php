<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
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
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
interface HttpInterface
{

    /**
     * Set a header
     *
     * @param  Header|string $header
     * @param  string $value
     * @return HttpInterface
     */
    public function addHeader($header, $value);

    /**
     * Set all headers
     *
     * @param  array $headers
     * @return HttpInterface
     */
    public function addHeaders(array $headers);

    /**
     * Get a header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader($name);

    /**
     * Get header value
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeaderValue($name);

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Get all header values as associative array
     *
     * @return array
     */
    public function getHeadersAsArray();

    /**
     * Get all header values formatted string
     *
     * @param  string $status
     * @param  string $eol
     * @return string
     */
    public function getHeadersAsString($status = null, $eol = "\r\n");

    /**
     * Determine if there are headers
     *
     * @return boolean
     */
    public function hasHeaders();

    /**
     * Has a header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasHeader($name);

    /**
     * Remove a header
     *
     * @param  string $name
     * @return HttpInterface
     */
    public function removeHeader($name);

    /**
     * Remove all headers
     *
     * @return HttpInterface
     */
    public function removeHeaders();

    /**
     * Set the body
     *
     * @param  string|Body $body
     * @return HttpInterface
     */
    public function setBody($body);

    /**
     * Get the body
     *
     * @return Body
     */
    public function getBody();

    /**
     * Get body content
     *
     * @return mixed
     */
    public function getBodyContent();

    /**
     * Has a body
     *
     * @return boolean
     */
    public function hasBody();

    /**
     * Has body content
     *
     * @return boolean
     */
    public function hasBodyContent();

    /**
     * Decode the body content
     *
     * @param  string $body
     * @return Body
     */
    public function decodeBodyContent($body = null);

    /**
     * Remove the body
     *
     * @return HttpInterface
     */
    public function removeBody();

}