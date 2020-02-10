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
namespace Pop\Http;

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * Abstract HTTP class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.5.0
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
     * Get all headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeadersAsArray()
    {
        $headers = [];

        foreach ($this->headers as $name => $header) {
            $headers[$name] = $header->getValue();
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
     * Set the body
     *
     * @param  string|Body $body
     * @return AbstractHttp
     */
    public function setBody($body = null)
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
     * Has a body
     *
     * @return boolean
     */
    public function hasBody()
    {
        return (null !== $this->body);
    }


    /**
     * Parse a request or response string based on content type
     *
     * @param  string $rawData
     * @param  string $contentType
     * @return mixed
     */
    public static function parseByContentType($rawData, $contentType)
    {
        $parsedResult = null;
        $contentType    = strtolower($contentType);

        if (strpos($contentType, 'json') !== false) {
            $parsedResult = json_decode($rawData, true);
        } else if (strpos($contentType, 'xml') !== false) {
            $matches = [];
            preg_match_all('/<!\[cdata\[(.*?)\]\]>/is', $rawData, $matches);

            foreach ($matches[0] as $match) {
                $strip = str_replace(
                    ['<![CDATA[', ']]>', '<', '>'],
                    ['', '', '&lt;', '&gt;'],
                    $match
                );
                $rawData = str_replace($match, $strip, $rawData);
            }

            $parsedResult = json_decode(json_encode((array)simplexml_load_string($rawData)), true);
        } else if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($rawData, $parsedResult);
        } else if (strpos($contentType, 'multipart/form-data') !== false) {
            $formContent = (strpos($rawData, 'Content-Type:') === false) ?
                'Content-Type: ' . $contentType . "\r\n\r\n" . $rawData : $rawData;
            $parsedResult = \Pop\Mime\Message::parseForm($formContent);
        }

        return $parsedResult;
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