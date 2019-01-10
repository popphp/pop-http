<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client;

/**
 * HTTP client interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
interface ClientInterface
{

    /**
     * Set the URL
     *
     * @param  string $url
     * @return ClientInterface
     */
    public function setUrl($url);

    /**
     * Get the URL
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set the method
     *
     * @param  string $method
     * @return ClientInterface
     */
    public function setMethod($method);

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod();

    /**
     * Set a field
     *
     * @param  string $name
     * @param  mixed  $value
     * @return ClientInterface
     */
    public function setField($name, $value);

    /**
     * Set all fields
     *
     * @param  array $fields
     * @return ClientInterface
     */
    public function setFields(array $fields);

    /**
     * Get a field
     *
     * @param  string $name
     * @return mixed
     */
    public function getField($name);

    /**
     * Get all field
     *
     * @return array
     */
    public function getFields();

    /**
     * Remove a field
     *
     * @param  string $name
     * @return ClientInterface
     */
    public function removeField($name);

    /**
     * Prepare the HTTP query
     *
     * @return string
     */
    public function prepareQuery();

    /**
     * Get HTTP query
     *
     * @return string
     */
    public function getQuery();

    /**
     * Get HTTP query length
     *
     * @param  boolean $mb
     * @return int
     */
    public function getQueryLength($mb = true);

    /**
     * Set a request header
     *
     * @param  string $name
     * @param  string $value
     * @return ClientInterface
     */
    public function setRequestHeader($name, $value);

    /**
     * Set all request headers
     *
     * @param  array $headers
     * @return ClientInterface
     */
    public function setRequestHeaders(array $headers);

    /**
     * Get a request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getRequestHeader($name);

    /**
     * Get all request headers
     *
     * @return array
     */
    public function getRequestHeaders();

    /**
     * Determine if there are request headers
     *
     * @return boolean
     */
    public function hasRequestHeaders();

    /**
     * Get a response header
     *
     * @param  string $name
     * @return mixed
     */
    public function getResponseHeader($name);

    /**
     * Get all response headers
     *
     * @return array
     */
    public function getResponseHeaders();

    /**
     * Determine if there are response headers
     *
     * @return boolean
     */
    public function hasResponseHeaders();

    /**
     * Get raw response header
     *
     * @return string
     */
    public function getRawResponseHeader();

    /**
     * Get the response body
     *
     * @return string
     */
    public function getBody();

    /**
     * Get the response code
     *
     * @return string
     */
    public function getCode();

    /**
     * Get the response HTTP version
     *
     * @return string
     */
    public function getHttpVersion();

    /**
     * Get the response HTTP message
     *
     * @return string
     */
    public function getMessage();

    /**
     * Get the raw response
     *
     * @return string
     */
    public function getResponse();

    /**
     * Determine whether or not resource is available
     *
     * @return boolean
     */
    public function hasResource();

    /**
     * Get the resource
     *
     * @return resource
     */
    public function getResource();

    /**
     * Decode the body
     *
     * @return resource
     */
    public function decodeBody();

    /**
     * Throw an exception upon an error.
     *
     * @param  string $error
     * @throws Exception
     * @return void
     */
    public function throwError($error);

    /**
     * Create and open the client resource
     *
     * @return ClientInterface
     */
    public function open();

    /**
     * Method to send the request and get the response
     *
     * @return void
     */
    public function send();

}