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
namespace Pop\Http\Server\Request;

/**
 * HTTP server request URI class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Uri
{

    /**
     * Request URI
     * @var ?string
     */
    protected ?string $requestUri = null;

    /**
     * Path segments
     * @var array
     */
    protected array $segments = [];

    /**
     * Base path
     * @var ?string
     */
    protected ?string $basePath = null;

    /**
     * Constructor
     *
     * Instantiate the request URI object
     *
     * @param ?string $uri
     * @param ?string $basePath
     */
    public function __construct(?string $uri = null, ?string $basePath = null)
    {
        $this->setRequestUri($uri, $basePath);
    }

    /**
     * Get the base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the request URI
     *
     * @return string
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * Get the full request URI, including base path
     *
     * @return string
     */
    public function getFullRequestUri(): string
    {
        return $this->basePath . $this->requestUri;
    }

    /**
     * Get a path segment, divided by the forward slash,
     * where $i refers to the array key index, i.e.,
     *    0     1     2
     * /hello/world/page
     *
     * @param  int $i
     * @return string|null
     */
    public function getSegment(int $i): string|null
    {
        return $this->segments[(int)$i] ?? null;
    }

    /**
     * Get all path segments
     *
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * Set the base path
     *
     * @param  ?string $path
     * @return Uri
     */
    public function setBasePath(?string $path = null): Uri
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Set the request URI
     *
     * @param  ?string $uri
     * @param  ?string $basePath
     * @return Uri
     */
    public function setRequestUri(?string $uri = null, ?string $basePath = null)
    {
        if (($uri === null) && isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        if (!empty($basePath)) {
            if (substr($uri, 0, (strlen($basePath) + 1)) == $basePath . '/') {
                $uri = substr($uri, (strpos($uri, $basePath) + strlen($basePath)));
            } else if (substr($uri, 0, (strlen($basePath) + 1)) == $basePath . '?') {
                $uri = '/' . substr($uri, (strpos($uri, $basePath) + strlen($basePath)));
            }
        }

        if (($uri == '') || ($uri == $basePath)) {
            $uri = '/';
        }

        // Some slash clean up
        $this->requestUri = $uri;
        $docRoot          = (isset($_SERVER['DOCUMENT_ROOT'])) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : null;
        $dir              = str_replace('\\', '/', getcwd());

        if (($dir != $docRoot) && (strlen($dir) > strlen($docRoot))) {
            $realBasePath = str_replace($docRoot, '', $dir);
            if (str_starts_with($uri, $realBasePath)) {
                $this->requestUri = substr($uri, strlen($realBasePath));
            }
        }

        $this->basePath = ($basePath === null) ? str_replace($docRoot, '', $dir) : $basePath;

        if (str_contains($this->requestUri, '?')) {
            $this->requestUri = substr($this->requestUri, 0, strpos($this->requestUri, '?'));
        }

        if (($this->requestUri != '/') && (str_contains($this->requestUri, '/'))) {
            $uri = (str_starts_with($this->requestUri, '/')) ? substr($this->requestUri, 1) : $this->requestUri;
            $this->segments = explode('/', $uri);
        }

        return $this;
    }

}