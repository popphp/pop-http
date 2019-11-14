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
namespace Pop\Http\Client;

/**
 * Abstract HTTP client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.5.0
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
     * Set a field
     *
     * @param  string $name
     * @param  mixed  $value
     * @return AbstractClient
     */
    public function setField($name, $value)
    {
        $this->getRequest()->setField($name, $value);
        return $this;
    }

    /**
     * Set all fields
     *
     * @param  array $fields
     * @return AbstractClient
     */
    public function setFields(array $fields)
    {
        $this->getRequest()->setFields($fields);
        return $this;
    }

    /**
     * Get a field
     *
     * @param  string $name
     * @return mixed
     */
    public function getField($name)
    {
        return $this->getRequest()->getField($name);
    }

    /**
     * Get all field
     *
     * @return array
     */
    public function getFields()
    {
        return $this->getRequest()->getFields();
    }

    /**
     * Remove a field
     *
     * @param  string $name
     * @return AbstractClient
     */
    public function removeField($name)
    {
        $this->getRequest()->removeField($name);
        return $this;
    }

    /**
     * Set all request headers
     *
     * @param  array $headers
     * @return AbstractClient
     */
    public function setRequestHeaders(array $headers)
    {
        $this->getRequest()->setHeaders($headers);
        return $this;
    }

    /**
     * Set request header
     *
     * @param  string $name
     * @param  string $value
     * @return AbstractClient
     */
    public function setRequestHeader($name, $value)
    {
        $this->getRequest()->setHeader($name, $value);
        return $this;
    }

    /**
     * Has request headers
     *
     * @return boolean
     */
    public function hasRequestHeaders()
    {
        return $this->getRequest()->hasHeaders();
    }

    /**
     * Get the request headers
     *
     * @return array
     */
    public function getRequestHeaders()
    {
        return $this->getRequest()->getHeaders();
    }

    /**
     * Get the request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getRequestHeader($name)
    {
        return $this->getRequest()->getHeader($name);
    }

    /**
     * Set all response headers
     *
     * @param  array $headers
     * @return AbstractClient
     */
    public function setResponseHeaders(array $headers)
    {
        $this->getResponse()->setHeaders($headers);
        return $this;
    }

    /**
     * Set response header
     *
     * @param  string $name
     * @param  string $value
     * @return AbstractClient
     */
    public function setResponseHeader($name, $value)
    {
        $this->getResponse()->setHeader($name, $value);
        return $this;
    }

    /**
     * Has response headers
     *
     * @return boolean
     */
    public function hasResponseHeaders()
    {
        return $this->getResponse()->hasHeaders();
    }

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->getResponse()->getHeaders();
    }

    /**
     * Get the response header
     *
     * @param  string $name
     * @return mixed
     */
    public function getResponseHeader($name)
    {
        return $this->getResponse()->getHeader($name);
    }

    /**
     * Get the response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->getResponse()->getBody();
    }
    /**
     * Get the response code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getResponse()->getCode();
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