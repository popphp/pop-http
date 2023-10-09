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
 * HTTP client curl multi handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class CurlMulti extends AbstractCurl
{

    /**
     * Get info about the Curl multi-handler
     *
     * @return array|false
     */
    public function getInfo(): array|false
    {
        return curl_multi_info_read($this->resource);
    }

    /**
     * Set a wait time until there is any activity on a connection
     *
     * @return int
     */
    public function setWait(float $timeout = 1.0): int
    {
        return curl_multi_select($this->resource, $timeout);
    }

    /**
     * Method to prepare the handler
     *
     * @param  Request $request
     * @param  ?Auth   $auth
     * @return CurlMulti
     */
    public function prepare(Request $request, ?Auth $auth = null): CurlMulti
    {
        return $this;
    }

    /**
     * Method to send the multiple Curl connections
     *
     * @param  ?int $active
     * @return int
     */
    public function send(?int &$active = null): int
    {
        return curl_multi_exec($this->resource, $active);
    }

    /**
     * Parse the response
     *
     * @return Response
     */
    public function parseResponse(): Response
    {

    }

    /**
     * Method to reset the handler
     *
     * @return CurlMulti
     */
    public function reset(): CurlMulti
    {
        return $this;
    }

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void
    {

    }

}
