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
 * Abstract HTTP client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
abstract class AbstractClient implements ClientInterface
{

    /**
     * URL
     * @var string
     */
    protected $url = null;

    /**
     * Method
     * @var string
     */
    protected $method = null;

    /**
     * Client resource object
     * @var resource
     */
    protected $resource = null;

    /**
     * Client request object
     * @var Request
     */
    protected $request = null;

    /**
     * Client response object
     * @var Response
     */
    protected $response = null;

    /**
     * Set the URL
     *
     * @param  string $url
     * @return AbstractClient
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get the URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the method
     *
     * @param  string  $method
     * @param  boolean $strict
     * @throws Exception
     * @return AbstractClient
     */
    public function setMethod($method, $strict = true)
    {
        $method = strtoupper($method);

        if ($strict) {
            if (!in_array($method, ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE'])) {
                throw new Exception('Error: That request method is not valid.');
            }
        }

        $this->method = $method;
        return $this;
    }

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Determine whether or not resource is available
     *
     * @return boolean
     */
    public function hasResource()
    {
        return is_resource($this->resource);
    }

    /**
     * Get the resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Get the resource (alias method)
     *
     * @return resource
     */
    public function resource()
    {
        return $this->resource;
    }

    /**
     * Set the request object
     *
     * @param  Request $request
     * @return AbstractClient
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Has request object
     *
     * @return boolean
     */
    public function hasRequest()
    {
        return (null !== $this->request);
    }

    /**
     * Get the request object
     *
     * @return Request
     */
    public function getRequest()
    {
        if (null === $this->request) {
            $this->request = new Request();
        }
        return $this->request;
    }

    /**
     * Get the request object (alias method)
     *
     * @return Request
     */
    public function request()
    {
        return $this->getRequest();
    }

    /**
     * Set the response object
     *
     * @param  Response $response
     * @return AbstractClient
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Has response object
     *
     * @return boolean
     */
    public function hasResponse()
    {
        return (null !== $this->response);
    }

    /**
     * Get the response object
     *
     * @return Response
     */
    public function getResponse()
    {
        if (null === $this->response) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * Get the response object (alias method)
     *
     * @return Response
     */
    public function response()
    {
        return $this->getResponse();
    }

    /**
     * Throw an exception upon an error.
     *
     * @param  string $error
     * @throws Exception
     * @return void
     */
    public function throwError($error)
    {
        throw new Exception($error);
    }

    /**
     * Create and open the client resource
     *
     * @return AbstractClient
     */
    abstract public function open();

    /**
     * Method to send the request and get the response
     *
     * @return void
     */
    abstract public function send();

}