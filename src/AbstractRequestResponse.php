<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http;

use Pop\Mime\Part\Header;
use Pop\Http\Body;
use Psr\Http\Message\MessageInterface;

/**
 * Abstract HTTP request/response class
 *
 * Declares `implements MessageInterface` here (in addition to the concrete
 * `implements RequestInterface`/`ResponseInterface` added to AbstractRequest/
 * AbstractResponse) because PHP's covariant-return check for `static` requires
 * the class where a method is physically defined to already be provably
 * compatible with the interface's return type; without this, PHP fatals on
 * `withHeader()`/`withAddedHeader()`/`withoutHeader()`/`withBody()` as soon as
 * a subclass implements MessageInterface, even though those methods'
 * behavior already satisfies it. `getProtocolVersion()`/`withProtocolVersion()`
 * remain unimplemented here (legal since this class stays abstract) — added by
 * AbstractRequest/AbstractResponse per PSR-7 message methods task.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
abstract class AbstractRequestResponse implements RequestResponseInterface, MessageInterface
{

    /**
     * Headers
     * @var array
     */
    protected array $headers = [];

    /**
     * Case-folded header name index (lowercase name => actual stored key), kept in sync by
     * addHeader()/removeHeader()/setHeaders()/removeHeaders() so resolveHeaderName() - used by
     * every PSR-7 case-insensitive lookup (getHeader()/withHeader()/withAddedHeader()/
     * withoutHeader()) - is an O(1) lookup instead of a linear strcasecmp() scan of every header.
     * @var array
     */
    protected array $headerIndex = [];

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
        $this->headers     = [];
        $this->headerIndex = [];
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
            $name = $header->getName();
        } else {
            if (is_numeric($header) && str_contains($value, ':')) {
                $header = Header::parse($value);
                $this->headers[$header->getName()] = $header;
                $name = $header->getName();
            } else {
                $this->headers[$header] = new Header($header, $value);
                $name = $header;
            }
        }

        $this->headerIndex[strtolower((string)$name)] = (string)$name;

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
    public function getHeaderObject(string $name): mixed
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
        return (isset($this->headers[$name])) ? (string)$this->headers[$name] : null;
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
        return (isset($this->headers[$name])) ? $this->headers[$name]->getValue($i) : null;
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
        return (isset($this->headers[$name])) ? $this->headers[$name]->getValueAsString($i) : null;
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaderObjects(): array
    {
        return $this->headers;
    }

    /**
     * Resolve a header name to whatever key is actually stored, matching
     * case-insensitively per PSR-7. Returns the input unchanged if no
     * existing header matches. Only PSR-7-facing methods route through
     * this - the non-PSR addHeader()/removeHeader()/hasHeader()/
     * getHeaderObject()/getHeaderObjects() methods keep their existing
     * exact-match storage keying.
     *
     * @param  string $name
     * @return string
     */
    private function resolveHeaderName(string $name): string
    {
        if (isset($this->headers[$name])) {
            return $name;
        }

        return $this->headerIndex[strtolower($name)] ?? $name;
    }

    /**
     * Get all string values for a header, per PSR-7 (empty array if absent, case-insensitive)
     *
     * @param  string $name
     * @return array
     */
    public function getHeader(string $name): array
    {
        $name = $this->resolveHeaderName($name);

        if (!isset($this->headers[$name])) {
            return [];
        }

        $values = $this->headers[$name]->getValuesAsStrings();

        return is_array($values) ? $values : [$values];
    }

    /**
     * Get all headers, per PSR-7 (each header's values array-wrapped)
     *
     * @return array
     */
    public function getHeaders(): array
    {
        $headers = [];

        foreach ($this->headers as $name => $header) {
            $headers[$name] = $this->getHeader($name);
        }

        return $headers;
    }

    /**
     * Get a header's values comma-joined into one string, per PSR-7
     *
     * @param  string $name
     * @return string
     */
    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
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

        $lower = strtolower($name);
        if (isset($this->headerIndex[$lower]) && ($this->headerIndex[$lower] === $name)) {
            unset($this->headerIndex[$lower]);
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
        $this->headers     = [];
        $this->headerIndex = [];
        return $this;
    }

    /**
     * Return an instance with the specified header, replacing any existing values, per PSR-7
     *
     * Parameter is typed `mixed` (rather than `string|array`) only to satisfy PSR-7's
     * untyped `MessageInterface::withHeader()` signature per PHP's LSP variance rules;
     * the supported values remain a string or an array of strings.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return static
     */
    public function withHeader(string $name, mixed $value): static
    {
        $name  = $this->resolveHeaderName($name);
        $clone = clone $this;
        $clone->removeHeader($name);

        $values = is_array($value) ? $value : [$value];
        $clone->addHeader($name, array_shift($values));
        foreach ($values as $v) {
            $clone->headers[$name]->addValue($v);
        }

        return $clone;
    }

    /**
     * Return an instance with the specified header value(s) appended, per PSR-7
     *
     * Parameter is typed `mixed` (rather than `string|array`) only to satisfy PSR-7's
     * untyped `MessageInterface::withAddedHeader()` signature per PHP's LSP variance
     * rules; the supported values remain a string or an array of strings.
     *
     * @param  string $name
     * @param  mixed  $value
     * @return static
     */
    public function withAddedHeader(string $name, mixed $value): static
    {
        $name   = $this->resolveHeaderName($name);
        $clone  = clone $this;
        $values = is_array($value) ? $value : [$value];

        if ($clone->hasHeader($name)) {
            foreach ($values as $v) {
                $clone->headers[$name]->addValue($v);
            }
        } else {
            $clone->addHeader($name, array_shift($values));
            foreach ($values as $v) {
                $clone->headers[$name]->addValue($v);
            }
        }

        return $clone;
    }

    /**
     * Return an instance without the specified header, per PSR-7
     *
     * @param  string $name
     * @return static
     */
    public function withoutHeader(string $name): static
    {
        $name  = $this->resolveHeaderName($name);
        $clone = clone $this;
        $clone->removeHeader($name);
        return $clone;
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
     * Get the body, per PSR-7 (a transient, never-stored empty Body when none is set,
     * so hasBody() remains unaffected by calling this)
     *
     * @return Body
     */
    public function getBody(): Body
    {
        return $this->body ?? new Body();
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
        if ($this->body === null) {
            return 0;
        }
        return ($mb) ? mb_strlen($this->body->getContent()) : ($this->body->getSize() ?? 0);
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
    public function decodeBodyContent(?string $body = null): Body
    {
        if ($body !== null) {
            $this->setBody($body);
        }
        if (($this->hasHeader('Transfer-Encoding')) && (count($this->getHeaderObject('Transfer-Encoding')->getValues()) == 1) &&
            (strtolower((string)$this->getHeaderObject('Transfer-Encoding')->getValueAsString(0)) == 'chunked')) {
            $this->body->setContent(Parser::decodeChunkedData($this->body->getContent()));
        }
        $contentEncoding = ($this->hasHeader('Content-Encoding') && (count($this->getHeaderObject('Content-Encoding')->getValues()) == 1)) ?
            $this->getHeaderObject('Content-Encoding')->getValueAsString(0) : null;
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
     * Return an instance with the specified body, per PSR-7
     *
     * @param  Body|\Psr\Http\Message\StreamInterface $body
     * @return static
     */
    public function withBody(Body|\Psr\Http\Message\StreamInterface $body): static
    {
        $clone       = clone $this;
        $clone->body = ($body instanceof Body) ? $body : new Body((string)$body);
        return $clone;
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

    /**
     * Deep-clone headers and body so a with*() clone never shares mutable state
     * with the instance it was cloned from
     *
     * @return void
     */
    public function __clone(): void
    {
        foreach ($this->headers as $name => $header) {
            $this->headers[$name] = clone $header;
        }

        if ($this->body !== null) {
            $this->body = clone $this->body;
        }
    }

}
