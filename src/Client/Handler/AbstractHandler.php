<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Uri;

/**
 * HTTP client handler interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
abstract class AbstractHandler implements HandlerInterface
{

    /**
     * URI string
     * @var ?string
     */
    protected ?string $uri = null;

    /**
     * HTTP version
     * @var string
     */
    protected string $httpVersion = '1.1';

    /**
     * Client resource object
     * @var mixed
     */
    protected mixed $resource = null;

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
     * Determine whether or not there is a URI string
     *
     * @return bool
     */
    public function hasUri(): bool
    {
        return ($this->uri !== null);
    }

    /**
     * Get the URI string
     *
     * @return ?string
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Get the URI string
     *
     * @return string
     */
    public function getHttpVersion(): string
    {
        return $this->httpVersion;
    }

    /**
     * Get the URI as an object
     *
     * @return Uri
     */
    public function getUriObject(): Uri
    {
        return new Uri($this->uri);
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
     * Method to send the request
     */
    abstract public function send();

    /**
     * Method to reset the handler
     *
     * @return AbstractHandler
     */
    abstract public function reset(): AbstractHandler;

    /**
     * Close the handler connection
     *
     * @return void
     */
    abstract public function disconnect(): void;

}
