<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Body;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Minimal, deliberately non-Pop\Http\Client\Response PSR-7 ResponseInterface
 * implementation, used to prove that a middleware short-circuiting with a
 * foreign ResponseInterface produces a clear Client\Exception rather than a
 * raw TypeError.
 */
class ForeignResponse implements ResponseInterface
{

    protected int $statusCode;
    protected string $reasonPhrase;
    protected array $headers = [];
    protected string $body;
    protected string $protocolVersion = '1.1';

    public function __construct(int $statusCode = 200, string $reasonPhrase = 'OK', string $body = '')
    {
        $this->statusCode   = $statusCode;
        $this->reasonPhrase = $reasonPhrase;
        $this->body         = $body;
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
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->headers[$name] ?? []);
    }

    public function withHeader(string $name, $value): static
    {
        $clone                 = clone $this;
        $clone->headers[$name] = (array)$value;
        return $clone;
    }

    public function withAddedHeader(string $name, $value): static
    {
        $clone                   = clone $this;
        $clone->headers[$name][] = $value;
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

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        $clone               = clone $this;
        $clone->statusCode   = $code;
        $clone->reasonPhrase = $reasonPhrase;
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

}
