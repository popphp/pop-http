<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client;

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * HTTP client object interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.5.0
 */
interface ClientObjectInterface
{

    /**
     * Set a header
     *
     * @param  Header|string $header
     * @param  string $value
     * @return ClientObjectInterface
     */
    public function addHeader($header, $value);

    /**
     * Set all headers
     *
     * @param  array $headers
     * @return ClientObjectInterface
     */
    public function addHeaders(array $headers);

    /**
     * Get a header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader($name);

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders();

    /**
     * Determine if there are headers
     *
     * @return boolean
     */
    public function hasHeaders();

    /**
     * Has a header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasHeader($name);

    /**
     * Set the body
     *
     * @param  string|Body $body
     * @return ClientObjectInterface
     */
    public function setBody($body = null);

    /**
     * Get the body
     *
     * @return Body
     */
    public function getBody();

    /**
     * Has a body
     *
     * @return boolean
     */
    public function hasBody();

}