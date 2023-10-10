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
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void;

}