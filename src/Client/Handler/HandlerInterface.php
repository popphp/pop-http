<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
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
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
interface HandlerInterface
{

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
     * Get the HTTP version
     *
     * @return string
     */
    public function getHttpVersion(): string;

    /**
     * Get the URI as an object
     *
     * @return Uri
     */
    public function getUriObject(): Uri;

    /**
     * Method to send the request
     */
    public function send();

    /**
     * Method to reset the handler
     *
     * @return HandlerInterface
     */
    public function reset(): HandlerInterface;

    /**
     * Prepare the handler with the given request (and optional auth) before sending
     *
     * @param  Request         $request
     * @param  ?\Pop\Http\Auth $auth
     * @return HandlerInterface
     */
    public function prepare(Request $request, ?\Pop\Http\Auth $auth = null): HandlerInterface;

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void;

}
