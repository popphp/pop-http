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

use Pop\Http\Response\Parser;

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
     * Client resource object
     * @var resource
     */
    protected $resource = null;

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
     * Fields
     * @var array
     */
    protected $fields = [];

    /**
     * Query
     * @var string
     */
    protected $query = null;

    /**
     * Request headers
     * @var array
     */
    protected $requestHeaders = [];

    /**
     * HTTP version from response
     * @var string
     */
    protected $version = null;

    /**
     * Response code
     * @var int
     */
    protected $code = null;

    /**
     * Response message
     * @var string
     */
    protected $message = null;

    /**
     * Raw response string
     * @var string
     */
    protected $response = null;

    /**
     * Raw response header
     * @var string
     */
    protected $responseHeader = null;

    /**
     * Response headers
     * @var array
     */
    protected $responseHeaders = [];

    /**
     * Response body
     * @var string
     */
    protected $body = null;

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
     * @param  string $method
     * @throws Exception
     * @return AbstractClient
     */
    public function setMethod($method)
    {
        $valid  = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'];
        $method = strtoupper($method);

        if (!in_array($method, $valid)) {
            throw new Exception('Error: That request method is not valid.');
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
     * Set a field
     *
     * @param  string $name
     * @param  mixed  $value
     * @return AbstractClient
     */
    public function setField($name, $value)
    {
        $this->fields[$name] = $value;
        $this->prepareQuery();

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
        foreach ($fields as $name => $value) {
            $this->setField($name, $value);
        }

        $this->prepareQuery();

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
        return (isset($this->fields[$name])) ? $this->fields[$name] : null;
    }

    /**
     * Get all field
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Remove a field
     *
     * @param  string $name
     * @return AbstractClient
     */
    public function removeField($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        $this->prepareQuery();

        return $this;
    }

    /**
     * Prepare the HTTP query
     *
     * @return string
     */
    public function prepareQuery()
    {
        $this->query = http_build_query($this->fields);
        return $this->query;
    }

    /**
     * Get HTTP query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get HTTP query length
     *
     * @param  boolean $mb
     * @return int
     */
    public function getQueryLength($mb = true)
    {
        return ($mb) ? mb_strlen($this->query) : strlen($this->query);
    }

    /**
     * Set a request header
     *
     * @param  string $name
     * @param  string $value
     * @return AbstractClient
     */
    public function setRequestHeader($name, $value)
    {
        $this->requestHeaders[$name] = $value;
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
        $this->requestHeaders = $headers;
        return $this;
    }

    /**
     * Get a request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getRequestHeader($name)
    {
        return (isset($this->requestHeaders[$name])) ? $this->requestHeaders[$name] : null;
    }

    /**
     * Get all request headers
     *
     * @return array
     */
    public function getRequestHeaders()
    {
        return $this->requestHeaders;
    }

    /**
     * Determine if there are request headers
     *
     * @return boolean
     */
    public function hasRequestHeaders()
    {
        return (count($this->requestHeaders) > 0);
    }

    /**
     * Get a response header
     *
     * @param  string $name
     * @return mixed
     */
    public function getResponseHeader($name)
    {
        return (isset($this->responseHeaders[$name])) ? $this->responseHeaders[$name] : null;
    }

    /**
     * Get all response headers
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * Determine if there are response headers
     *
     * @return boolean
     */
    public function hasResponseHeaders()
    {
        return (count($this->responseHeaders) > 0);
    }

    /**
     * Get raw response header
     *
     * @return string
     */
    public function getRawResponseHeader()
    {
        return $this->responseHeader;
    }

    /**
     * Get the response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get the response code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Determine if the response is a success
     *
     * @return boolean
     */
    public function isSuccess()
    {
        $type = floor($this->code / 100);
        return (($type == 1) || ($type == 2) || ($type == 3));
    }

    /**
     * Determine if the response is a redirect
     *
     * @return boolean
     */
    public function isRedirect()
    {
        $type = floor($this->code / 100);
        return ($type == 3);
    }

    /**
     * Determine if the response is an error
     *
     * @return boolean
     */
    public function isError()
    {
        $type = floor($this->code / 100);
        return (($type == 4) || ($type == 5));
    }

    /**
     * Determine if the response is a client error
     *
     * @return boolean
     */
    public function isClientError()
    {
        $type = floor($this->code / 100);
        return ($type == 4);
    }

    /**
     * Determine if the response is a server error
     *
     * @return boolean
     */
    public function isServerError()
    {
        $type = floor($this->code / 100);
        return ($type == 5);
    }

    /**
     * Get the response HTTP version
     *
     * @return string
     */
    public function getHttpVersion()
    {
        return $this->version;
    }

    /**
     * Get the response HTTP message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the raw response
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
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
     * Decode the body
     *
     * @return void
     */
    public function decodeBody()
    {
        if (isset($this->responseHeaders['Transfer-Encoding']) && ($this->responseHeaders['Transfer-Encoding'] == 'chunked')) {
            $this->body = Parser::decodeChunkedBody($this->body);
        }
        $this->body = Parser::decodeBody($this->body, $this->responseHeaders['Content-Encoding']);
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