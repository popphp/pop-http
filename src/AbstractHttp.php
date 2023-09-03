<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http;

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * Abstract HTTP class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.2.0
 */
abstract class AbstractHttp implements HttpInterface
{

    /**
     * Headers
     * @var array
     */
    protected $headers = [];

    /**
     * Body
     * @var Body
     */
    protected $body = null;

    /**
     * Add a header
     *
     * @param  Header|string $header
     * @param  string $value
     * @return AbstractHttp
     */
    public function addHeader($header, $value = null)
    {
        if ($header instanceof Header) {
            $this->headers[$header->getName()] = $header;
        } else {
            $this->headers[$header] = new Header($header, $value);
        }

        return $this;
    }

    /**
     * Add all headers
     *
     * @param  array $headers
     * @return AbstractHttp
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $header => $value) {
            if ($value instanceof Header) {
                $this->addHeader($value);
            } else {
                $this->addHeader($header, $value);
            }
        }
        return $this;
    }

    /**
     * Get a header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader($name)
    {
        return (isset($this->headers[$name])) ? $this->headers[$name] : null;
    }

    /**
     * Get header value
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeaderValue($name)
    {
        return (isset($this->headers[$name]) && (count($this->headers[$name]->getValues()) == 1)) ?
            $this->headers[$name]->getValue(0) : null;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get all header values as associative array
     *
     * @return array
     */
    public function getHeadersAsArray()
    {
        $headers = [];

        foreach ($this->headers as $name => $header) {
            if (count($header->getValues()) == 1) {
                $headers[$name] = $header->getValue(0);
            } else {
                $headers[$name] = $header->getValuesAsStrings();
            }

        }
        return $headers;
    }

    /**
     * Get all header values formatted string
     *
     * @param  string $status
     * @param  string $eol
     * @return string
     */
    public function getHeadersAsString($status = null, $eol = "\r\n")
    {
        $headers = '';

        if (null !== $status) {
            $headers = $status . $eol;
        }

        foreach ($this->headers as $header) {
            $headers .= $header . $eol;
        }

        return $headers;
    }

    /**
     * Determine if there are headers
     *
     * @return boolean
     */
    public function hasHeaders()
    {
        return (count($this->headers) > 0);
    }

    /**
     * Has a header
     *
     * @param  string $name
     * @return boolean
     */
    public function hasHeader($name)
    {
        return (isset($this->headers[$name]));
    }

    /**
     * Remove a header
     *
     * @param  string $name
     * @return AbstractHttp
     */
    public function removeHeader($name)
    {
        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Remove all headers
     *
     * @return AbstractHttp
     */
    public function removeHeaders()
    {
        $this->headers = [];
        return $this;
    }

    /**
     * Set the body
     *
     * @param  string|Body $body
     * @return AbstractHttp
     */
    public function setBody($body)
    {
        $this->body = ($body instanceof Body) ? $body : new Body($body);
        return $this;
    }

    /**
     * Get the body
     *
     * @return Body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get body content
     *
     * @return mixed
     */
    public function getBodyContent()
    {
        return (null !== $this->body) ? $this->body->getContent() : null;
    }

    /**
     * Has a body
     *
     * @return boolean
     */
    public function hasBody()
    {
        return (null !== $this->body);
    }

    /**
     * Has body content
     *
     * @return boolean
     */
    public function hasBodyContent()
    {
        return ((null !== $this->body) && $this->body->hasContent());
    }

    /**
     * Decode the body
     *
     * @param  string $body
     * @return Body
     */
    public function decodeBodyContent($body = null)
    {
        if (null !== $body) {
            $this->setBody($body);
        }
        if (($this->hasHeader('Transfer-Encoding')) && (count($this->getHeader('Transfer-Encoding')->getValues()) == 1) &&
            (strtolower($this->getHeader('Transfer-Encoding')->getValue(0)) == 'chunked')) {
            $this->body->setContent(Parser::decodeChunkedData($this->body->getContent()));
        }
        $contentEncoding = ($this->hasHeader('Content-Encoding') && (count($this->getHeader('Content-Encoding')->getValues()) == 1)) ?
            $this->getHeader('Content-Encoding')->getValue(0) : null;
        $this->body->setContent(Parser::decodeData($this->body->getContent(), $contentEncoding));

        return $this->body;
    }

    /**
     * Remove the body
     *
     * @return AbstractHttp
     */
    public function removeBody()
    {
        $this->body = null;
        return $this;
    }

    /**
     * Magic method to get either the headers or body
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
            case 'body':
                return $this->body;
                break;
            default:
                return null;
        }
    }

}