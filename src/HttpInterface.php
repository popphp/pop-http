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
namespace Pop\Http;

/**
 * HTTP interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.3
 */
interface HttpInterface
{

    /**
     * Set the request
     *
     * @param  AbstractRequest $request
     * @return HttpInterface
     */
    public function setRequest(AbstractRequest $request): HttpInterface;

    /**
     * Set the response
     *
     * @param  AbstractResponse $response
     * @return HttpInterface
     */
    public function setResponse(AbstractResponse $response): HttpInterface;

    /**
     * Get the request
     *
     * @return AbstractRequest
     */
    public function getRequest(): AbstractRequest;

    /**
     * Get the response
     *
     * @return AbstractResponse
     */
    public function getResponse(): AbstractResponse;

    /**
     * Has request
     *
     * @return bool
     */
    public function hasRequest(): bool;

    /**
     * Get the response
     *
     * @return bool
     */
    public function hasResponse(): bool;

    /**
     * Send the request/response
     */
    public function send();

    /**
     * Render the request/response to string
     */
    public function render(): string;

    /**
     * Render the request/response to string
     */
    public function __toString(): string;

}
