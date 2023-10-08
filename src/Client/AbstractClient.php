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
use Pop\Http\Parser;
use Pop\Mime\Part\Body;

/**
 * Abstract HTTP client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractClient implements ClientInterface
{

    /**
     * URL
     * @var ?string
     */
    protected ?string $url = null;

    /**
     * Method
     * @var ?string
     */
    protected ?string $method = null;

    /**
     * Client resource object
     * @var mixed
     */
    protected mixed $resource = null;

    /**
     * Client request object
     * @var ?Request
     */
    protected ?Request $request = null;

    /**
     * Client response object
     * @var ?Response
     */
    protected ?Response $response = null;

    /**
     * HTTP auth object
     * @var ?Auth
     */
    protected ?Auth $auth = null;

    /**
     * Constructor
     *
     * Instantiate the client object
     *
     * @param  ?string $url
     * @throws Exception
     * @param  string $method
     */
    public function __construct(?string $url = null, string $method = 'GET')
    {
        if (!empty($url)) {
            $this->setUrl($url);
        }
        if (!empty($method)) {
            $this->setMethod($method);
        }
    }

    /**
     * Set the auth object
     *
     * @param  Auth $auth
     * @return AbstractClient
     */
    public function setAuth(Auth $auth): AbstractClient
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Get the auth object
     *
     * @return Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * Has auth object
     *
     * @return bool
     */
    public function hasAuth(): bool
    {
        return ($this->auth !== null);
    }

    /**
     * Get the URL
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set the URL
     *
     * @param  string $url
     * @return AbstractClient
     */
    public function setUrl(string $url): AbstractClient
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Append to the URL
     *
     * @param  string $url
     * @return AbstractClient
     */
    public function appendToUrl(string $url): AbstractClient
    {
        $this->url .= $url;
        return $this;
    }

    /**
     * Set the method
     *
     * @param  string $method
     * @param  bool   $strict
     * @throws Exception
     * @return AbstractClient
     */
    public function setMethod(string $method, bool $strict = true): AbstractClient
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
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Determine whether or not resource is available
     *
     * @return bool
     */
    public function hasResource(): bool
    {
        return ($this->resource !== null);
    }

    /**
     * Get the resource
     *
     * @return mixed
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * Get the resource (alias method)
     *
     * @return mixed
     */
    public function resource(): mixed
    {
        return $this->resource;
    }

    /**
     * Set the request object
     *
     * @param  Request $request
     * @return AbstractClient
     */
    public function setRequest(Request $request): AbstractClient
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Has request object
     *
     * @return bool
     */
    public function hasRequest(): bool
    {
        return ($this->request !== null);
    }

    /**
     * Get the request object
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        if ($this->request === null) {
            $this->request = new Request();
        }
        return $this->request;
    }

    /**
     * Get the request object (alias method)
     *
     * @return Request
     */
    public function request(): Request
    {
        return $this->getRequest();
    }

    /**
     * Set the response object
     *
     * @param  Response $response
     * @return AbstractClient
     */
    public function setResponse(Response $response): AbstractClient
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Has response object
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return ($this->response !== null);
    }

    /**
     * Get the response object
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        if ($this->response === null) {
            $this->response = new Response();
        }
        return $this->response;
    }

    /**
     * Get the parsed response
     *
     * @return mixed
     */
    public function getParsedResponse(): mixed
    {
        $parsedResponse = null;

        if (($this->hasResponse()) && ($this->getResponse()->hasBody()) && ($this->getResponse()->hasHeader('Content-Type')) &&
            (count($this->getResponse()->getHeader('Content-Type')->getValues()) == 1)) {
            $rawResponse     = $this->getResponse()->getBody()->getContent();
            $contentType     = $this->getResponse()->getHeader('Content-Type')->getValue(0);
            $contentEncoding = ($this->getResponse()->hasHeader('Content-Encoding') && (count($this->getResponse()->getHeader('Content-Encoding')->getValues()) == 1)) ?
                $this->getResponse()->getHeader('Content-Encoding')->getValue(0) : null;
            $parsedResponse  = Parser::parseDataByContentType($rawResponse, $contentType, $contentEncoding);
        }

        return $parsedResponse;
    }

    /**
     * Get the response object (alias method)
     *
     * @return Response
     */
    public function response(): Response
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
    public function setField(string $name, mixed $value): AbstractClient
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
    public function setFields(array $fields): AbstractClient
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
    public function getField(string $name): mixed
    {
        return $this->getRequest()->getField($name);
    }

    /**
     * Get all fields
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->getRequest()->getFields();
    }

    /**
     * Has fields
     *
     * @return bool
     */
    public function hasFields(): bool
    {
        return $this->getRequest()->hasFields();
    }

    /**
     * Has field
     *
     * @param  string $name
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return $this->getRequest()->hasField($name);
    }

    /**
     * Remove a field
     *
     * @param  string $name
     * @return AbstractClient
     */
    public function removeField(string $name): AbstractClient
    {
        $this->getRequest()->removeField($name);
        return $this;
    }

    /**
     * Remove all fields
     *
     * @return AbstractClient
     */
    public function removeFields(): AbstractClient
    {
        $this->getRequest()->removeFields();
        return $this;
    }

    /**
     * Add request headers
     *
     * @param  array $headers
     * @return AbstractClient
     */
    public function addRequestHeaders(array $headers): AbstractClient
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
    public function addRequestHeader(string $name, string $value): AbstractClient
    {
        $this->getRequest()->addHeader($name, $value);
        return $this;
    }

    /**
     * Has request headers
     *
     * @return bool
     */
    public function hasRequestHeaders(): bool
    {
        return $this->getRequest()->hasHeaders();
    }

    /**
     * Has request header
     *
     * @param  string $name
     * @return bool
     */
    public function hasRequestHeader(string $name): bool
    {
        return $this->getRequest()->hasHeader($name);
    }

    /**
     * Get the request headers
     *
     * @return array
     */
    public function getRequestHeaders(): array
    {
        return $this->getRequest()->getHeaders();
    }

    /**
     * Get the request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getRequestHeader(string $name): mixed
    {
        return $this->getRequest()->getHeader($name);
    }

    /**
     * Get the request body
     *
     * @return Body
     */
    public function getRequestBody(): Body
    {
        return $this->getRequest()->getBody();
    }

    /**
     * Create request as JSON
     *
     * @return AbstractClient
     */
    public function createAsJson(): AbstractClient
    {
        $this->getRequest()->createAsJson();
        return $this;
    }

    /**
     * Check if request is JSON
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return $this->getRequest()->isJson();
    }

    /**
     * Create request as XML
     *
     * @return AbstractClient
     */
    public function createAsXml(): AbstractClient
    {
        $this->getRequest()->createAsXml();
        return $this;
    }

    /**
     * Check if request is XML
     *
     * @return bool
     */
    public function isXml(): bool
    {
        return $this->getRequest()->isXml();
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return AbstractClient
     */
    public function createUrlEncodedForm(): AbstractClient
    {
        $this->getRequest()->createUrlEncodedForm();
        return $this;
    }

    /**
     * Check if request is a URL-encoded form
     *
     * @return bool
     */
    public function isUrlEncodedForm(): bool
    {
        return $this->getRequest()->isUrlEncodedForm();
    }

    /**
     * Create request as a multipart form
     *
     * @return AbstractClient
     */
    public function createMultipartForm(): AbstractClient
    {
        $this->getRequest()->createMultipartForm();
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return bool
     */
    public function isMultipartForm(): bool
    {
        return $this->getRequest()->isMultipartForm();
    }

    /**
     * Add response headers
     *
     * @param  array $headers
     * @return AbstractClient
     */
    public function addResponseHeaders(array $headers): AbstractClient
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
    public function addResponseHeader(string $name, string $value): AbstractClient
    {
        $this->getResponse()->addHeader($name, $value);
        return $this;
    }

    /**
     * Has response headers
     *
     * @return bool
     */
    public function hasResponseHeaders(): bool
    {
        return $this->getResponse()->hasHeaders();
    }

    /**
     * Has response header
     *
     * @param  string $name
     * @return bool
     */
    public function hasResponseHeader(string $name): bool
    {
        return $this->getResponse()->hasHeader($name);
    }

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->getResponse()->getHeaders();
    }

    /**
     * Get the response header
     *
     * @param  string $name
     * @return mixed
     */
    public function getResponseHeader(string $name): mixed
    {
        return $this->getResponse()->getHeader($name);
    }

    /**
     * Get the response body
     *
     * @return Body
     */
    public function getResponseBody(): Body
    {
        return $this->getResponse()->getBody();
    }

    /**
     * Get the response code
     *
     * @return string
     */
    public function getResponseCode(): string
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
    public function throwError(string $error): void
    {
        throw new Exception($error);
    }

    /**
     * Create and open the client resource
     *
     * @return AbstractClient
     */
    abstract public function open(): AbstractClient;

    /**
     * Method to send the request and get the response
     *
     * @return void
     */
    abstract public function send(): void;

    /**
     * Method to reset the client object
     *
     * @return AbstractClient
     */
    abstract public function reset(): AbstractClient;

}