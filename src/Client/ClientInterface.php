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
     * @param  string  $method
     * @param  boolean $strict
     * @throws Exception
     * @return AbstractClient
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