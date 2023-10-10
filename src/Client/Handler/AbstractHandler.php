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
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;

/**
 * HTTP client handler interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractHandler implements HandlerInterface
{

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
     * Get the resource (alias method)
     *
     * @return mixed
     */
    public function resource(): mixed
    {
        return $this->resource;
    }

    /**
     * Method to prepare the handler
     *
     * @param  Request $request
     * @param  ?Auth   $auth
     * @return AbstractHandler
     */
    abstract public function prepare(Request $request, ?Auth $auth = null): AbstractHandler;

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