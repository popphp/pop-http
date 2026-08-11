<?php
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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Abstract HTTP request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
abstract class AbstractRequest extends AbstractRequestResponse implements RequestInterface
{

    /**
     * Request URI object
     * @var ?Uri
     */
    protected ?Uri $uri = null;

    /**
     * HTTP protocol version, per PSR-7
     * @var string
     */
    protected string $protocolVersion = '1.1';

    /**
     * Explicit request-target override, per PSR-7 (null = derive from the URI)
     * @var ?string
     */
    protected ?string $requestTargetOverride = null;

    /**
     * Constructor
     *
     * Instantiate the request object
     *
     * @param  Uri|string|null $uri
     * @throws Exception
     */
    public function __construct(Uri|string|null $uri = null)
    {
        if ($uri !== null) {
            $this->setUri($uri);
        }
    }

    /**
     * Set URI
     *
     * @param  Uri|string $uri
     * @throws Exception
     * @return AbstractRequest
     */
    public function setUri(Uri|string $uri): AbstractRequest
    {
        $this->uri = (is_string($uri)) ? new Uri($uri) : $uri;
        return $this;
    }

    /**
     * Get URI, per PSR-7 (a transient, never-stored empty Uri when none is set,
     * so hasUri() remains unaffected by calling this)
     *
     * @return Uri
     */
    public function getUri(): Uri
    {
        return $this->uri ?? new Uri();
    }

    /**
     * Get URI as string
     *
     * @return string
     */
    public function getUriAsString(): string
    {
        return (string)$this->uri?->render();
    }

    /**
     * Has URI
     *
     * @return bool
     */
    public function hasUri(): bool
    {
        return ($this->uri !== null);
    }

    /**
     * Clear URI
     *
     * @return AbstractRequest
     */
    public function clearUri(): AbstractRequest
    {
        $this->uri = null;
        return $this;
    }

    /**
     * Get the HTTP protocol version, per PSR-7
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version, per PSR-7
     *
     * @param  string $version
     * @return static
     */
    public function withProtocolVersion(string $version): static
    {
        $clone                  = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    /**
     * Get the request target, per PSR-7 (path + query, or '/' with no URI, unless overridden)
     *
     * @return string
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTargetOverride !== null) {
            return $this->requestTargetOverride;
        }

        if (!$this->hasUri()) {
            return '/';
        }

        $target = $this->uri->getPath();
        if ($target === '') {
            $target = '/';
        }

        if ($this->uri->hasQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        return $target;
    }

    /**
     * Return an instance with the specified request target, per PSR-7
     *
     * @param  string $requestTarget
     * @return static
     */
    public function withRequestTarget(string $requestTarget): static
    {
        $clone                        = clone $this;
        $clone->requestTargetOverride = $requestTarget;
        return $clone;
    }

    /**
     * Deep-clone the URI so a with*() clone never shares a mutable Uri
     * instance with the instance it was cloned from
     *
     * @return void
     */
    public function __clone(): void
    {
        parent::__clone();

        if ($this->uri !== null) {
            $this->uri = clone $this->uri;
        }
    }

    /**
     * Return an instance with the specified URI, per PSR-7
     *
     * @param  UriInterface $uri
     * @param  bool         $preserveHost
     * @return static
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $clone      = clone $this;
        $clone->uri = ($uri instanceof Uri) ? $uri : new Uri((string)$uri);

        if ((!$preserveHost || !$clone->hasHeader('Host')) && $clone->uri->hasHost()) {
            $host = $clone->uri->getHost();
            if ($clone->uri->hasPort()) {
                $host .= ':' . $clone->uri->getPort();
            }
            $clone = $clone->withHeader('Host', $host);
        }

        return $clone;
    }

}
