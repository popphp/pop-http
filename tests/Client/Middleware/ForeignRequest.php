<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Body;
use Pop\Http\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Minimal, deliberately non-Pop\Http\Client\Request PSR-7 RequestInterface
 * implementation, used to prove that a middleware substituting a foreign
 * PSR-7 request is honored rather than silently dropped.
 */
class ForeignRequest implements RequestInterface
{

    protected string $method;
    protected UriInterface $uri;
    protected array $headers = [];
    protected string $body;
    protected string $protocolVersion = '1.1';

    public function __construct(string $method, string $uri, array $headers = [], string $body = '')
    {
        $this->method  = $method;
        $this->uri     = new Uri($uri);
        $this->headers = $headers;
        $this->body    = $body;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): static
    {
        $clone                  = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->headers as $name => $value) {
            $headers[$name] = [$value];
        }
        return $headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return isset($this->headers[$name]) ? [$this->headers[$name]] : [];
    }

    public function getHeaderLine(string $name): string
    {
        return $this->headers[$name] ?? '';
    }

    public function withHeader(string $name, $value): static
    {
        $clone                  = clone $this;
        $clone->headers[$name]  = is_array($value) ? implode(', ', $value) : $value;
        return $clone;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $clone = clone $this;
        $clone->headers[$name] = isset($clone->headers[$name])
            ? $clone->headers[$name] . ', ' . $value : $value;
        return $clone;
    }

    public function withoutHeader(string $name): static
    {
        $clone = clone $this;
        unset($clone->headers[$name]);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return new Body($this->body);
    }

    public function withBody(StreamInterface $body): static
    {
        $clone       = clone $this;
        $clone->body = (string)$body;
        return $clone;
    }

    public function getRequestTarget(): string
    {
        return $this->uri->getPath() ?: '/';
    }

    public function withRequestTarget(string $requestTarget): static
    {
        return clone $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): static
    {
        $clone         = clone $this;
        $clone->method = $method;
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone      = clone $this;
        $clone->uri = $uri;
        return $clone;
    }

    public function getBodyContent(): string
    {
        return $this->body;
    }

}
