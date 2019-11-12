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

/**
 * HTTP client object interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
interface ClientObjectInterface
{

    /**
     * Set a request header
     *
     * @param  string $name
     * @param  string $value
     * @return ClientObjectInterface
     */
    public function setHeader($name, $value);

    /**
     * Set all request headers
     *
     * @param  array $headers
     * @return ClientObjectInterface
     */
    public function setHeaders(array $headers);

    /**
     * Get a request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader($name);

    /**
     * Get all request headers
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Determine if there are request headers
     *
     * @return boolean
     */
    public function hasHeaders();

    /**
     * Has a request header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasHeader($name);

}