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
namespace Pop\Http;

/**
 * HTTP interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
interface HttpInterface
{

    /**
     * Set the request
     *
     * @param  RequestResponseInterface $request
     * @return HttpInterface
     */
    public function setRequest(RequestResponseInterface $request): HttpInterface;

    /**
     * Set the response
     *
     * @param  RequestResponseInterface $response
     * @return HttpInterface
     */
    public function setResponse(RequestResponseInterface $response): HttpInterface;

    /**
     * Get the request
     *
     * @return RequestResponseInterface
     */
    public function getRequest(): RequestResponseInterface;

    /**
     * Get the response
     *
     * @return RequestResponseInterface
     */
    public function getResponse(): RequestResponseInterface;

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

}
