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
use Pop\Http\Promise\Promise;
use Pop\Mime\Part\Body;

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
    public function setAuth(Auth $auth): ClientInterface;

    /**
     * Get the auth object
     *
     * @return Auth
     */
    public function getAuth(): Auth;

    /**
     * Has auth object
     *
     * @return bool
     */
    public function hasAuth(): bool;

    /**
     * Set the URL
     *
     * @param  string $url
     * @return ClientInterface
     */
    public function setUrl(string $url): ClientInterface;

    /**
     * Append to the URL
     *
     * @param  string $url
     * @return ClientInterface
     */
    public function appendToUrl(string $url): ClientInterface;

    /**
     * Get the URL
     *
     * @return string
     */
    public function getUrl(): string;

    /**
     * Set the method
     *
     * @param  string $method
     * @param  bool   $strict
     * @return ClientInterface
     */
    public function setMethod(string $method, bool $strict = true): ClientInterface;

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod(): string;

    /**
     * Determine whether or not resource is available
     *
     * @return bool
     */
    public function hasResource(): bool;

    /**
     * Get the resource
     *
     * @return mixed
     */
    public function getResource(): mixed;

    /**
     * Get the resource (alias method)
     *
     * @return mixed
     */
    public function resource(): mixed;

    /**
     * Set the request object
     *
     * @param  Request $request
     * @return ClientInterface
     */
    public function setRequest(Request $request): ClientInterface;

    /**
     * Has request object
     *
     * @return bool
     */
    public function hasRequest(): bool;

    /**
     * Get the request object
     *
     * @return Request
     */
    public function getRequest(): Request;

    /**
     * Get the request object (alias method)
     *
     * @return Request
     */
    public function request(): Request;

    /**
     * Set the response object
     *
     * @param  Response $response
     * @return ClientInterface
     */
    public function setResponse(Response $response): ClientInterface;

    /**
     * Has response object
     *
     * @return bool
     */
    public function hasResponse(): bool;

    /**
     * Get the response object
     *
     * @return Response
     */
    public function getResponse(): Response;

    /**
     * Get the response object (alias method)
     *
     * @return Response
     */
    public function response(): Response;

    /**
     * Set a field
     *
     * @param  string $name
     * @param  mixed  $value
     * @return ClientInterface
     */
    public function setField(string $name, mixed $value): ClientInterface;

    /**
     * Set all fields
     *
     * @param  array $fields
     * @return ClientInterface
     */
    public function setFields(array $fields): ClientInterface;

    /**
     * Get a field
     *
     * @param  string $name
     * @return mixed
     */
    public function getField(string $name): mixed;

    /**
     * Get all field
     *
     * @return array
     */
    public function getFields(): array;

    /**
     * Remove a field
     *
     * @param  string $name
     * @return ClientInterface
     */
    public function removeField(string $name): ClientInterface;

    /**
     * Remove all fields
     *
     * @return ClientInterface
     */
    public function removeFields(): ClientInterface;

    /**
     * Add request headers
     *
     * @param  array $headers
     * @return ClientInterface
     */
    public function addRequestHeaders(array $headers): ClientInterface;

    /**
     * Add request header
     *
     * @param  string $name
     * @param  string $value
     * @return ClientInterface
     */
    public function addRequestHeader(string $name, string $value): ClientInterface;

    /**
     * Has request headers
     *
     * @return bool
     */
    public function hasRequestHeaders(): bool;

    /**
     * Has request header
     *
     * @param  string $name
     * @return bool
     */
    public function hasRequestHeader(string $name): bool;

    /**
     * Get the request headers
     *
     * @return array
     */
    public function getRequestHeaders(): array;

    /**
     * Get the request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getRequestHeader(string $name): mixed;

    /**
     * Get the request body
     *
     * @return Body
     */
    public function getRequestBody(): Body;


    /**
     * Create request as JSON
     *
     * @return ClientInterface
     */
    public function createAsJson(): ClientInterface;

    /**
     * Check if request is JSON
     *
     * @return bool
     */
    public function isJson(): bool;

    /**
     * Create request as XML
     *
     * @return ClientInterface
     */
    public function createAsXml(): ClientInterface;

    /**
     * Check if request is XML
     *
     * @return bool
     */
    public function isXml(): bool;

    /**
     * Create request as a URL-encoded form
     *
     * @return ClientInterface
     */
    public function createUrlEncodedForm(): ClientInterface;

    /**
     * Check if request is a URL-encoded form
     *
     * @return bool
     */
    public function isUrlEncodedForm(): bool;

    /**
     * Create request as a multipart form
     *
     * @return ClientInterface
     */
    public function createMultipartForm(): ClientInterface;

    /**
     * Check if request is a multipart form
     *
     * @return bool
     */
    public function isMultipartForm(): bool;

    /**
     * Add response headers
     *
     * @param  array $headers
     * @return ClientInterface
     */
    public function addResponseHeaders(array $headers): ClientInterface;

    /**
     * Add response header
     *
     * @param  string $name
     * @param  string $value
     * @return ClientInterface
     */
    public function addResponseHeader(string $name, string $value): ClientInterface;

    /**
     * Has response headers
     *
     * @return bool
     */
    public function hasResponseHeaders(): bool;

    /**
     * Has response header
     *
     * @param  string $name
     * @return bool
     */
    public function hasResponseHeader(string $name): bool;

    /**
     * Get the response headers
     *
     * @return array
     */
    public function getResponseHeaders(): array;

    /**
     * Get the response header
     *
     * @param  string $name
     * @return mixed
     */
    public function getResponseHeader(string $name): mixed;

    /**
     * Get the response body
     *
     * @return Body
     */
    public function getResponseBody(): Body;

    /**
     * Get the response code
     *
     * @return int
     */
    public function getResponseCode(): int;

    /**
     * Get the response message
     *
     * @return string
     */
    public function getResponseMessage(): string;

    /**
     * Determine if the request is complete
     *
     * @return bool
     */
    public function isComplete(): bool;

    /**
     * Determine if the request is a success
     *
     * @return bool|null
     */
    public function isSuccess(): bool|null;

    /**
     * Determine if the request is an error
     *
     * @return bool|null
     */
    public function isError(): bool|null;

    /**
     * Throw an exception upon an error.
     *
     * @param  string $error
     * @throws Exception
     * @return void
     */
    public function throwError(string $error): void;

    /**
     * Create and open the client resource
     *
     * @return ClientInterface
     */
    public function open(): ClientInterface;

    /**
     * Method to send the request and get the response
     *
     * @return void
     */
    public function send(): void;

    /**
     * Method to send the request asynchronously
     *
     * @return Promise
     */
    public function sendAsync(): Promise;

    /**
     * Method to reset the client object
     *
     * @return ClientInterface
     */
    public function reset(): ClientInterface;

}