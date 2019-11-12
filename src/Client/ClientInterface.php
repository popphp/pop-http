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