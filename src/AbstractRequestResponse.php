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

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * Abstract HTTP request/response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
abstract class AbstractRequestResponse implements RequestResponseInterface
{

    /**
     * Headers
     * @var array
     */
    protected array $headers = [];

    /**
     * Body
     * @var ?Body
     */
    protected ?Body $body = null;

    /**
     * Set all headers (clear out any existing headers)
     *
     * @param  array $headers
     * @return AbstractRequestResponse
     */
    public function setHeaders(array $headers): AbstractRequestResponse
    {
        $this->headers = [];
        $this->addHeaders($headers);

        return $this;
    }

    /**
     * Add a header
     *
     * @param  Header|string|int $header
     * @param  ?string           $value
     * @return AbstractRequestResponse
     */
    public function addHeader(Header|string|int $header, ?string $value = null): AbstractRequestResponse
    {
        if ($header instanceof Header) {
            $this->headers[$header->getName()] = $header;
        } else {
            if (is_numeric($header) && str_contains($value, ':')) {
                $header = Header::parse($value);
                $this->headers[$header->getName()] = $header;
            } else {
                $this->headers[$header] = new Header($header, $value);
            }
        }

        return $this;
    }

    /**
     * Add all headers
     *
     * @param  array $headers
     * @return AbstractRequestResponse
     */
    public function addHeaders(array $headers): AbstractRequestResponse
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
    public function getHeader(string $name): mixed
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get a header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeaderAsString(string $name): mixed
    {
        return (string)$this->headers[$name] ?? null;
    }

    /**
     * Get header value
     *
     * @param  string $name
     * @param  int    $i
     * @return mixed
     */
    public function getHeaderValue(string $name, int $i = 0): mixed
    {
        return (isset($this->headers[$name])) ? $this->headers[$name]?->getValue($i) : null;
    }

    /**
     * Get header value as string
     *
     * @param  string $name
     * @param  int    $i
     * @return string|null
     */
    public function getHeaderValueAsString(string $name, int $i = 0): string|null
    {
        return (isset($this->headers[$name])) ? $this->headers[$name]?->getValueAsString($i) : null;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get all header values as associative array
     *
     * @param  bool $asStrings
     * @return array
     */
    public function getHeadersAsArray(bool $asStrings = true): array
    {
        $headers = [];

        foreach ($this->headers as $name => $header) {
            if (count($header->getValues()) == 1) {
                $headers[$name] = ($asStrings) ? $header->getValueAsString(0) : $header->getValue(0);
            } else {
                $headers[$name] = ($asStrings) ? $header->getValuesAsStrings() : $header->getValues();
            }
        }
        return $headers;
    }

    /**
     * Get all header values formatted string
     *
     * @param  mixed  $status
     * @param  string $eol
     * @return string
     */
    public function getHeadersAsString(mixed $status = null, string $eol = "\r\n"): string
    {
        $headers = '';

        if (is_string($status)) {
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
     * @return bool
     */
    public function hasHeaders(): bool
    {
        return (count($this->headers) > 0);
    }

    /**
     * Has a header
     *
     * @param  string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return (isset($this->headers[$name]));
    }

    /**
     * Remove a header
     *
     * @param  string $name
     * @return AbstractRequestResponse
     */
    public function removeHeader(string $name): AbstractRequestResponse
    {
        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Remove all headers
     *
     * @return AbstractRequestResponse
     */
    public function removeHeaders(): AbstractRequestResponse
    {
        $this->headers = [];
        return $this;
    }

    /**
     * Set the body
     *
     * @param  string|Body $body
     * @return AbstractRequestResponse
     */
    public function setBody(string|Body $body): AbstractRequestResponse
    {
        $this->body = ($body instanceof Body) ? $body : new Body($body);
        return $this;
    }

    /**
     * Get the body
     *
     * @return Body
     */
    public function getBody(): Body
    {
        return $this->body;
    }

    /**
     * Get body content
     *
     * @return mixed
     */
    public function getBodyContent(): mixed
    {
        return ($this->body !== null) ? $this->body->getContent() : null;
    }

    /**
     * Get body content length
     *
     * @param  bool $mb
     * @return int
     */
    public function getBodyContentLength(bool $mb = false): int
    {
        if ($this->body !== null) {
            return ($mb) ? mb_strlen($this->body->getContent()) : strlen($this->body->getContent());
        } else {
            return 0;
        }
    }

    /**
     * Has a body
     *
     * @return bool
     */
    public function hasBody(): bool
    {
        return ($this->body !== null);
    }

    /**
     * Has body content
     *
     * @return bool
     */
    public function hasBodyContent(): bool
    {
        return (($this->body !== null) && $this->body->hasContent());
    }

    /**
     * Decode the body
     *
     * @param  ?string $body
     * @return Body
     */
    public function decodeBodyContent(?string $body = null): body
    {
        if ($body !== null) {
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
     * @return AbstractRequestResponse
     */
    public function removeBody(): AbstractRequestResponse
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
    public function __get(string $name): mixed
    {
        return match ($name) {
            'headers' => $this->headers,
            'body'    => $this->body,
            default   => null,
        };
    }

}
