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
 * Abstract HTTP client object class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
abstract class AbstractClientObject implements ClientObjectInterface
{

    /**
     * Headers
     * @var array
     */
    protected $headers = [];

    /**
     * Set a request header
     *
     * @param  string $name
     * @param  string $value
     * @return AbstractClientObject
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set all request headers
     *
     * @param  array $headers
     * @return AbstractClientObject
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Get a request header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader($name)
    {
        return (isset($this->headers[$name])) ? $this->headers[$name] : null;
    }

    /**
     * Get all request headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Determine if there are request headers
     *
     * @return boolean
     */
    public function hasHeaders()
    {
        return (count($this->headers) > 0);
    }

    /**
     * Has a request header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasHeader($name)
    {
        return (isset($this->headers[$name]));
    }

    /**
     * Magic method to get a value from the headers
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'headers':
                return $this->headers;
                break;
            default:
                return null;
        }
    }

}