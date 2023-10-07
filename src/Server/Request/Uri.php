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
     * @var string
     */
    protected $requestUri = null;

    /**
     * Path segments
     * @var array
     */
    protected $segments = [];

    /**
     * Base path
     * @var string
     */
    protected $basePath = null;

    /**
     * Constructor
     *
     * Instantiate the request URI object
     *
     * @param  string $uri
     * @param  string $basePath
     */
    public function __construct($uri = null, $basePath = null)
    {
        $this->setRequestUri($uri, $basePath);
    }

    /**
     * Get the base path
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Get the request URI
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri;
    }

    /**
     * Get the full request URI, including base path
     *
     * @return string
     */
    public function getFullRequestUri()
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
     * @return string
     */
    public function getSegment($i)
    {
        return (isset($this->segments[(int)$i])) ? $this->segments[(int)$i] : null;
    }

    /**
     * Get all path segments
     *
     * @return array
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * Set the base path
     *
     * @param  string $path
     * @return Uri
     */
    public function setBasePath($path = null)
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Set the request URI
     *
     * @param  string $uri
     * @param  string $basePath
     * @return Uri
     */
    public function setRequestUri($uri = null, $basePath = null)
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
            if (substr($uri, 0, strlen($realBasePath)) == $realBasePath) {
                $this->requestUri = substr($uri, strlen($realBasePath));
            }
        }

        $this->basePath = ($basePath === null) ? str_replace($docRoot, '', $dir) : $basePath;

        if (strpos($this->requestUri, '?') !== false) {
            $this->requestUri = substr($this->requestUri, 0, strpos($this->requestUri, '?'));
        }

        if (($this->requestUri != '/') && (strpos($this->requestUri, '/') !== false)) {
            $uri = (substr($this->requestUri, 0, 1) == '/') ? substr($this->requestUri, 1) : $this->requestUri;
            $this->segments = explode('/', $uri);
        }

        return $this;
    }

}