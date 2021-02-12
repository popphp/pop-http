<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2021 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Server;

use Pop\Http\AbstractRequest;
use Pop\Http\Server\Request\Data;
use Pop\Http\Server\Request\Uri;
use Pop\Mime\Part\Body;

/**
 * HTTP server request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2021 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.1.0
 */
class Request extends AbstractRequest
{

    /**
     * Request URI object
     * @var Uri
     */
    protected $requestUri = null;

    /**
     * Request data object
     * @var Data
     */
    protected $requestData = null;

    /**
     * COOKIE array
     * @var array
     */
    protected $cookie = [];

    /**
     * SERVER array
     * @var array
     */
    protected $server = [];

    /**
     * ENV array
     * @var array
     */
    protected $env = [];

    /**
     * Constructor
     *
     * Instantiate the request object
     *
     * @param  string $uri
     * @param  string $basePath
     * @param  mixed  $filters
     * @param  mixed  $streamToFile
     */
    public function __construct($uri = null, $basePath = null, $filters = null, $streamToFile = null)
    {
        parent::__construct($filters);

        $this->cookie = (isset($_COOKIE)) ? $_COOKIE : [];
        $this->server = (isset($_SERVER)) ? $_SERVER : [];
        $this->env    = (isset($_ENV))    ? $_ENV    : [];

        // Get any possible request headers
        if (function_exists('getallheaders')) {
            $this->addHeaders(getallheaders());
        } else {
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) == 'HTTP_') {
                    $key = ucfirst(strtolower(str_replace('HTTP_', '', $key)));
                    if (strpos($key, '_') !== false) {
                        $ary = explode('_', $key);
                        foreach ($ary as $k => $v){
                            $ary[$k] = ucfirst(strtolower($v));
                        }
                        $key = implode('-', $ary);
                    }
                    $this->addHeader($key, $value);
                }
            }
        }

        $this->requestUri  = new Uri($uri, $basePath);
        $this->requestData = new Data(
            $this->getHeaderValue('Content-Type'), $this->getHeaderValue('Content-Encoding'), $filters, $streamToFile
        );

        if ($this->requestData->hasRawData()) {
            $this->body = new Body($this->requestData->getRawData());
        }
    }

    /**
     * Return whether or not the method is GET
     *
     * @return boolean
     */
    public function isGet()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'GET'));
    }

    /**
     * Return whether or not the method is HEAD
     *
     * @return boolean
     */
    public function isHead()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'HEAD'));
    }

    /**
     * Return whether or not the method is POST
     *
     * @return boolean
     */
    public function isPost()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'POST'));
    }

    /**
     * Return whether or not the method is PUT
     *
     * @return boolean
     */
    public function isPut()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'PUT'));
    }

    /**
     * Return whether or not the method is DELETE
     *
     * @return boolean
     */
    public function isDelete()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'DELETE'));
    }

    /**
     * Return whether or not the method is TRACE
     *
     * @return boolean
     */
    public function isTrace()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'TRACE'));
    }

    /**
     * Return whether or not the method is OPTIONS
     *
     * @return boolean
     */
    public function isOptions()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'OPTIONS'));
    }

    /**
     * Return whether or not the method is CONNECT
     *
     * @return boolean
     */
    public function isConnect()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'CONNECT'));
    }

    /**
     * Return whether or not the method is PATCH
     *
     * @return boolean
     */
    public function isPatch()
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'PATCH'));
    }

    /**
     * Return whether or not the request is secure
     *
     * @return boolean
     */
    public function isSecure()
    {
        return (isset($this->server['HTTPS']) || (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443')));
    }

    /**
     * Get the document root
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        return (isset($this->server['DOCUMENT_ROOT'])) ? $this->server['DOCUMENT_ROOT'] : null;
    }

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod()
    {
        return (isset($this->server['REQUEST_METHOD'])) ? $this->server['REQUEST_METHOD'] : null;
    }

    /**
     * Get the server port
     *
     * @return string
     */
    public function getPort()
    {
        return (isset($this->server['SERVER_PORT'])) ? $this->server['SERVER_PORT'] : null;
    }

    /**
     * Get scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return ($this->isSecure()) ? 'https' : 'http';
    }

    /**
     * Get host without port)
     *
     * @return string
     */
    public function getHost()
    {
        $hostname = null;

        if (!empty($this->server['HTTP_HOST'])) {
            $hostname = $this->server['HTTP_HOST'];
        } else if (!empty($this->server['SERVER_NAME'])) {
            $hostname = $this->server['SERVER_NAME'];
        }

        if (strpos($hostname, ':') !== false) {
            $hostname = substr($hostname, 0, strpos($hostname, ':'));
        }

        return $hostname;
    }

    /**
     * Get host with port
     *
     * @return string
     */
    public function getFullHost()
    {
        $port     = $this->getPort();
        $hostname = null;

        if (!empty($this->server['HTTP_HOST'])) {
            $hostname = $this->server['HTTP_HOST'];
        } else if (!empty($this->server['SERVER_NAME'])) {
            $hostname = $this->server['SERVER_NAME'];
        }

        if ((strpos($hostname, ':') === false) && (null !== $port)) {
            $hostname .= ':' . $port;
        }

        return $hostname;
    }

    /**
     * Get client's IP
     *
     * @param  boolean $proxy
     * @return string
     */
    public function getIp($proxy = true)
    {
        $ip = null;

        if ($proxy && isset($this->server['HTTP_CLIENT_IP'])) {
            $ip = $this->server['HTTP_CLIENT_IP'];
        } else if ($proxy && isset($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ip = $this->server['HTTP_X_FORWARDED_FOR'];
        } else if (isset($this->server['REMOTE_ADDR'])) {
            $ip = $this->server['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Get a value from $_COOKIE, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getCookie($key = null)
    {
        if (null === $key) {
            return $this->cookie;
        } else {
            return (isset($this->cookie[$key])) ? $this->cookie[$key] : null;
        }
    }

    /**
     * Get a value from $_SERVER, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getServer($key = null)
    {
        if (null === $key) {
            return $this->server;
        } else {
            return (isset($this->server[$key])) ? $this->server[$key] : null;
        }
    }

    /**
     * Get a value from $_ENV, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getEnv($key = null)
    {
        if (null === $key) {
            return $this->env;
        } else {
            return (isset($this->env[$key])) ? $this->env[$key] : null;
        }
    }

    /**
     * Get the base path
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->requestUri->getBasePath();
    }

    /**
     * Get the request URI
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->requestUri->getRequestUri();
    }

    /**
     * Get the full request URI, including base path
     *
     * @return string
     */
    public function getFullRequestUri()
    {
        return $this->requestUri->getFullRequestUri();
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
        return $this->requestUri->getSegment($i);
    }

    /**
     * Get all path segments
     *
     * @return array
     */
    public function getSegments()
    {
        return $this->requestUri->getSegments();
    }

    /**
     * Set the base path
     *
     * @param  string $path
     * @return Request
     */
    public function setBasePath($path = null)
    {
        $this->requestUri->setBasePath($path);
        return $this;
    }

    /**
     * Return whether or not the request has FILES
     *
     * @return boolean
     */
    public function hasFiles()
    {
        return $this->requestData->hasFiles();
    }

    /**
     * Get a value from $_GET, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getQuery($key = null)
    {
        return $this->requestData->getQuery($key);
    }

    /**
     * Get a value from $_POST, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getPost($key = null)
    {
        return $this->requestData->getPost($key);
    }

    /**
     * Get a value from $_FILES, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getFiles($key = null)
    {
        return $this->requestData->getFiles($key);
    }

    /**
     * Get a value from PUT query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getPut($key = null)
    {
        return $this->requestData->getPut($key);
    }

    /**
     * Get a value from PATCH query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getPatch($key = null)
    {
        return $this->requestData->getPatch($key);
    }

    /**
     * Get a value from DELETE query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getDelete($key = null)
    {
        return $this->requestData->getDelete($key);
    }


    /**
     * Get a value from query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getQueryData($key = null)
    {
        return $this->requestData->getQueryData($key);
    }

    /**
     * Has query data
     *
     * @return boolean
     */
    public function hasQueryData()
    {
        return $this->requestData->hasQueryData();
    }

    /**
     * Get a value from parsed data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getParsedData($key = null)
    {
        return $this->requestData->getParsedData($key);
    }

    /**
     * Has parsed data
     *
     * @return boolean
     */
    public function hasParsedData()
    {
        return $this->requestData->hasParsedData();
    }

    /**
     * Get the raw data
     *
     * @return string
     */
    public function getRawData()
    {
        return $this->requestData->getRawData();
    }

    /**
     * Has raw data
     *
     * @return boolean
     */
    public function hasRawData()
    {
        return $this->requestData->hasRawData();
    }

    /**
     * Get request URI object
     *
     * @return Uri
     */
    public function getRequestUriObject()
    {
        return $this->requestUri;
    }

    /**
     * Get request data object
     *
     * @return Data
     */
    public function getRequestDataObject()
    {
        return $this->requestData;
    }

    /**
     * Magic method to get a value from one of the server/environment variables
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'get':
                return $this->requestData->get;
                break;
            case 'post':
                return $this->requestData->post;
                break;
            case 'files':
                return $this->requestData->files;
                break;
            case 'put':
                return $this->requestData->put;
                break;
            case 'patch':
                return $this->requestData->patch;
                break;
            case 'delete':
                return $this->requestData->delete;
                break;
            case 'parsed':
                return $this->requestData->parsed;
                break;
            case 'raw':
                return $this->requestData->raw;
                break;
            case 'cookie':
                return $this->cookie;
                break;
            case 'server':
                return $this->server;
                break;
            case 'env':
                return $this->env;
                break;
            case 'headers':
                return $this->headers;
                break;
            default:
                return null;
        }
    }

}
