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

use Pop\Http\Parser;

/**
 * Abstract HTTP client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
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
     * Constructor
     *
     * Instantiate the client object
     *
     * @param  string $url
     * @param  string $method
     */
    public function __construct($url = null, $method = 'GET')
    {
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($method)) {
            $this->setMethod($method);
        }
    }

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
     * Get the parsed response
     *
     * @return mixed
     */
    public function getParsedResponse()
    {
        $parsedResponse = null;

        if (($this->hasResponse()) && ($this->getResponse()->hasBody()) && ($this->getResponse()->hasHeader('Content-Type'))) {
            $rawResponse     = $this->getResponse()->getBody()->getContent();
            $contentType     = $this->getResponse()->getHeader('Content-Type')->getValue();
            $contentEncoding = ($this->getResponse()->hasHeader('Content-Encoding')) ? $this->getResponse()->getHeader('Content-Encoding')->getValue() : null;
            $parsedResponse  = Parser::parseDataByContentType($rawResponse, $contentType, $contentEncoding);
        }

        return $parsedResponse;
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
     * Add request headers
     *
     * @param  array $headers
     * @return AbstractClient
     */
    public function addRequestHeaders(array $headers)
    {
        $this->getRequest()->addHeaders($headers);
        return $this;
    }

    /**
     * Add request header
     *
     * @param  string $name
     * @param  string $value
     * @return AbstractClient
     */
    public function addRequestHeader($name, $value)
    {
        $this->getRequest()->addHeader($name, $value);
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
     * Has request header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasRequestHeader($name)
    {
        return $this->getRequest()->hasHeader($name);
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
     * Get the request body
     *
     * @return string
     */
    public function getRequestBody()
    {
        return $this->getRequest()->getBody();
    }

    /**
     * Create request as JSON
     *
     * @return AbstractClient
     */
    public function createAsJson()
    {
        $this->getRequest()->createAsJson();
        return $this;
    }

    /**
     * Check if request is JSON
     *
     * @return boolean
     */
    public function isJson()
    {
        return $this->getRequest()->isJson();
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return AbstractClient
     */
    public function createUrlEncodedForm()
    {
        $this->getRequest()->createUrlEncodedForm();
        return $this;
    }

    /**
     * Check if request is a URL-encoded form
     *
     * @return boolean
     */
    public function isUrlEncodedForm()
    {
        return $this->getRequest()->isUrlEncodedForm();
    }

    /**
     * Create request as a multipart form
     *
     * @return AbstractClient
     */
    public function createMultipartForm()
    {
        $this->getRequest()->createMultipartForm();
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return boolean
     */
    public function isMultipartForm()
    {
        return $this->getRequest()->isMultipartForm();
    }

    /**
     * Add response headers
     *
     * @param  array $headers
     * @return AbstractClient
     */
    public function addResponseHeaders(array $headers)
    {
        $this->getResponse()->addHeaders($headers);
        return $this;
    }

    /**
     * Add response header
     *
     * @param  string $name
     * @param  string $value
     * @return AbstractClient
     */
    public function addResponseHeader($name, $value)
    {
        $this->getResponse()->addHeader($name, $value);
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
     * Has response header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasResponseHeader($name)
    {
        return $this->getResponse()->hasHeader($name);
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
    public function getResponseBody()
    {
        return $this->getResponse()->getBody();
    }

    /**
     * Get the response code
     *
     * @return string
     */
    public function getResponseCode()
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