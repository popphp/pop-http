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
namespace Pop\Http\Client;

use Pop\Http\Auth;

/**
 * HTTP client interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
interface ClientInterface
{

    /**
     * Set the auth object
     *
     * @param  Auth $auth
     * @return ClientInterface
     */
    public function setAuth(Auth $auth);

    /**
     * Get the auth object
     *
     * @return Auth
     */
    public function getAuth();

    /**
     * Has auth object
     *
     * @return boolean
     */
    public function hasAuth();

    /**
     * Set the URL
     *
     * @param  string $url
     * @return ClientInterface
     */
    public function setUrl($url);

    /**
     * Append to the URL
     *
     * @param  string $url
     * @return ClientInterface
     */
    public function appendToUrl($url);

    /**
     * Get the URL
     *
     * @return string
     */
    public function getUrl();

    /**
     * Set the method
     *
     * @param  string  $method
     * @param  boolean $strict
     * @throws Exception
     * @return ClientInterface
     */
    public function setMethod($method, $strict = true);

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod();

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
     * Get the resource (alias method)
     *
     * @return resource
     */
    public function resource();

    /**
     * Set the request object
     *
     * @param  Request $request
     * @return ClientInterface
     */
    public function setRequest(Request $request);

    /**
     * Has request object
     *
     * @return boolean
     */
    public function hasRequest();

    /**
     * Get the request object
     *
     * @return Request
     */
    public function getRequest();

    /**
     * Get the request object (alias method)
     *
     * @return Request
     */
    public function request();

    /**
     * Set the response object
     *
     * @param  Response $response
     * @return ClientInterface
     */
    public function setResponse(Response $response);

    /**
     * Has response object
     *
     * @return boolean
     */
    public function hasResponse();

    /**
     * Get the response object
     *
     * @return Response
     */
    public function getResponse();

    /**
     * Get the response object (alias method)
     *
     * @return Response
     */
    public function response();

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
     * Remove all fields
     *
     * @return ClientInterface
     */
    public function removeFields();

    /**
     * Add request headers
     *
     * @param  array $headers
     * @return ClientInterface
     */
    public function addRequestHeaders(array $headers);

    /**
     * Add request header
     *
     * @param  string $name
     * @param  string $value
     * @return ClientInterface
     */
    public function addRequestHeader($name, $value);

    /**
     * Has request headers
     *
     * @return boolean
     */
    public function hasRequestHeaders();

    /**
     * Has request header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasRequestHeader($name);

    /**
     * Get the request headers
     *
     * @return array
     */
    public function getRequestHeaders();

    /**
     * Get the request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getRequestHeader($name);

    /**
     * Get the request body
     *
     * @return string
     */
    public function getRequestBody();


    /**
     * Create request as JSON
     *
     * @return ClientInterface
     */
    public function createAsJson();

    /**
     * Check if request is JSON
     *
     * @return boolean
     */
    public function isJson();

    /**
     * Create request as XML
     *
     * @return ClientInterface
     */
    public function createAsXml();

    /**
     * Check if request is XML
     *
     * @return boolean
     */
    public function isXml();

    /**
     * Create request as a URL-encoded form
     *
     * @return ClientInterface
     */
    public function createUrlEncodedForm();

    /**
     * Check if request is a URL-encoded form
     *
     * @return boolean
     */
    public function isUrlEncodedForm();

    /**
     * Create request as a multipart form
     *
     * @return ClientInterface
     */
    public function createMultipartForm();

    /**
     * Check if request is a multipart form
     *
     * @return boolean
     */
    public function isMultipartForm();

    /**
     * Add response headers
     *
     * @param  array $headers
     * @return ClientInterface
     */
    public function addResponseHeaders(array $headers);

    /**
     * Add response header
     *
     * @param  string $name
     * @param  string $value
     * @return ClientInterface
     */
    public function addResponseHeader($name, $value);

    /**
     * Has response headers
     *
     * @return boolean
     */
    public function hasResponseHeaders();

    /**
     * Has response header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasResponseHeader($name);

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getResponseHeaders();

    /**
     * Get the response header
     *
     * @param  string $name
     * @return mixed
     */
    public function getResponseHeader($name);

    /**
     * Get the response body
     *
     * @return string
     */
    public function getResponseBody();

    /**
     * Get the response code
     *
     * @return string
     */
    public function getResponseCode();

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

    /**
     * Method to reset the client object
     *
     * @return ClientInterface
     */
    public function reset();

}