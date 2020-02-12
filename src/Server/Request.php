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
namespace Pop\Http\Server;

use Pop\Http\AbstractRequest;
use Pop\Http\Parser;
use Pop\Mime\Part\Body;

/**
 * HTTP server request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Request extends AbstractRequest
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
     * Query data
     * @var mixed
     */
    protected $queryData = null;

    /**
     * Parsed data
     * @var mixed
     */
    protected $parsedData = null;

    /**
     * Raw data
     * @var mixed
     */
    protected $rawData = null;

    /**
     * Stream to file
     * @var boolean
     */
    protected $streamToFile = false;

    /**
     * Stream to file
     * @var string
     */
    protected $streamToFileLocation = null;

    /**
     * GET array
     */
    protected $get    = [];

    /**
     * POST array
     */
    protected $post   = [];

    /**
     * FILES array
     */
    protected $files  = [];

    /**
     * PUT array
     */
    protected $put    = [];

    /**
     * PATCH array
     */
    protected $patch  = [];

    /**
     * DELETE array
     */
    protected $delete = [];

    /**
     * COOKIE array
     */
    protected $cookie = [];

    /**
     * SERVER array
     */
    protected $server = [];

    /**
     * ENV array
     */
    protected $env    = [];

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

        $this->setRequestUri($uri, $basePath);

        $this->get    = (isset($_GET))    ? $_GET    : [];
        $this->post   = (isset($_POST))   ? $_POST   : [];
        $this->files  = (isset($_FILES))  ? $_FILES  : [];
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

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->parseData($streamToFile);
        }
    }

    /**
     * Return whether or not the request has FILES
     *
     * @return boolean
     */
    public function hasFiles()
    {
        return (count($this->files) > 0);
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
     * Get a value from $_GET, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getQuery($key = null)
    {
        if (null === $key) {
            return $this->get;
        } else {
            return (isset($this->get[$key])) ? $this->get[$key] : null;
        }
    }

    /**
     * Get a value from $_POST, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getPost($key = null)
    {
        if (null === $key) {
            return $this->post;
        } else {
            return (isset($this->post[$key])) ? $this->post[$key] : null;
        }
    }

    /**
     * Get a value from $_FILES, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getFiles($key = null)
    {
        if (null === $key) {
            return $this->files;
        } else {
            return (isset($this->files[$key])) ? $this->files[$key] : null;
        }
    }

    /**
     * Get a value from PUT query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getPut($key = null)
    {
        if (null === $key) {
            return $this->put;
        } else {
            return (isset($this->put[$key])) ? $this->put[$key] : null;
        }
    }

    /**
     * Get a value from PATCH query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getPatch($key = null)
    {
        if (null === $key) {
            return $this->patch;
        } else {
            return (isset($this->patch[$key])) ? $this->patch[$key] : null;
        }
    }

    /**
     * Get a value from DELETE query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getDelete($key = null)
    {
        if (null === $key) {
            return $this->delete;
        } else {
            return (isset($this->delete[$key])) ? $this->delete[$key] : null;
        }
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
     * Get a value from query data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getQueryData($key = null)
    {
        $result = null;

        if ((null !== $this->queryData) && is_array($this->queryData)) {
            if (null === $key) {
                $result = $this->queryData;
            } else {
                $result = (isset($this->queryData[$key])) ? $this->queryData[$key] : null;
            }
        }

        return $result;
    }

    /**
     * Get a value from parsed data, or the whole array
     *
     * @param  string $key
     * @return string|array
     */
    public function getParsedData($key = null)
    {
        $result = null;

        if ((null !== $this->parsedData) && is_array($this->parsedData)) {
            if (null === $key) {
                $result = $this->parsedData;
            } else {
                $result = (isset($this->parsedData[$key])) ? $this->parsedData[$key] : null;
            }
        }

        return $result;
    }

    /**
     * Get the raw data
     *
     * @return string
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Is the request stream to file
     *
     * @return boolean
     */
    public function isStreamToFile()
    {
        return $this->streamToFile;
    }

    /**
     * Get stream to file location
     *
     * @return string
     */
    public function getStreamToFileLocation()
    {
        return $this->streamToFileLocation;
    }

    /**
     * Process stream to file
     *
     * @return Request
     */
    public function processStreamToFile()
    {
        if (($this->streamToFile) && file_exists($this->streamToFileLocation)) {
            $contentType      = $this->getHeaderValue('Content-Type');
            $contentEncoding  = $this->getHeaderValue('Content-Encoding');
            $this->rawData    = file_get_contents($this->streamToFileLocation);
            $this->parsedData = Parser::parseDataByContentType($this->rawData, $contentType, strtoupper($contentEncoding));
        }

        return $this;
    }

    /**
     * Set the base path
     *
     * @param  string $path
     * @return Request
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
     * @return Request
     */
    public function setRequestUri($uri = null, $basePath = null)
    {
        if ((null === $uri) && isset($_SERVER['REQUEST_URI'])) {
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

        $this->basePath = (null === $basePath) ? str_replace($docRoot, '', $dir) : $basePath;

        if (strpos($this->requestUri, '?') !== false) {
            $this->requestUri = substr($this->requestUri, 0, strpos($this->requestUri, '?'));
        }

        if (($this->requestUri != '/') && (strpos($this->requestUri, '/') !== false)) {
            $uri = (substr($this->requestUri, 0, 1) == '/') ? substr($this->requestUri, 1) : $this->requestUri;
            $this->segments = explode('/', $uri);
        }

        return $this;
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
                return $this->get;
                break;
            case 'post':
                return $this->post;
                break;
            case 'files':
                return $this->files;
                break;
            case 'put':
                return $this->put;
                break;
            case 'patch':
                return $this->patch;
                break;
            case 'delete':
                return $this->delete;
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
            case 'parsed':
                return $this->parsedData;
                break;
            case 'raw':
                return $this->getRawData();
                break;
            default:
                return null;
        }
    }

    /**
     * Parse any data that came with the request
     *
     * @param  mixed $streamToFile
     * @return void
     */
    protected function parseData($streamToFile = null)
    {
        $contentType     = $this->getHeaderValue('Content-Type');
        $contentEncoding = $this->getHeaderValue('Content-Encoding');

        /**
         * $_SERVER['X_POP_HTTP_RAW_DATA'] is for testing purposes only
         */
        // Stream raw data to file location
        if (null !== $streamToFile) {
            $this->streamToFile = true;
            // Stream raw data to system temp folder with auto-generated filename
            if ($streamToFile === true) {
                $this->streamToFileLocation = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pop-http-' . uniqid();
            // Else, stream raw data to user-specified file location
            } else if (!is_dir($streamToFile) && is_dir(dirname($streamToFile)) && is_writable(dirname($streamToFile))) {
                $this->streamToFileLocation = $streamToFile;
            // Else, stream raw data to user-specified direction with auto-generated filename
            } else if (is_dir($streamToFile) && is_writable($streamToFile)) {
                $filename = 'pop-http-' . uniqid();
                $this->streamToFileLocation = $streamToFile .
                    ((substr($streamToFile, -1) == DIRECTORY_SEPARATOR) ? $filename : DIRECTORY_SEPARATOR . $filename);
            } else {
                throw new Exception('Error: Unable to determine an acceptable file location in which to stream the data.');
            }

            if (!empty($this->streamToFileLocation)) {
                file_put_contents(
                    $this->streamToFileLocation,
                    (isset($_SERVER['X_POP_HTTP_RAW_DATA']) ?
                        $_SERVER['X_POP_HTTP_RAW_DATA'] : file_get_contents('php://input'))
                );

                clearstatcache();

                // Clear out if no raw data was stored
                if (filesize($this->streamToFileLocation) == 0) {
                    unlink($this->streamToFileLocation);
                    $this->streamToFileLocation = null;
                }
            }
        // Else, store raw data stream in memory
        } else {
            $this->rawData = (isset($_SERVER['X_POP_HTTP_RAW_DATA'])) ?
                $_SERVER['X_POP_HTTP_RAW_DATA'] : file_get_contents('php://input');
        }

        // Process query string
        if (isset($_SERVER['QUERY_STRING'])) {
            if ((stripos($contentType, 'json') !== false) || (stripos($contentType, 'xml') !== false) ||
                (strpos($contentType, 'application/x-www-form-urlencoded') !== false)) {
                $this->queryData = Parser::parseDataByContentType($_SERVER['QUERY_STRING'], $contentType, strtoupper($contentEncoding));
            } else {
                $this->queryData = rawurldecode($_SERVER['QUERY_STRING']);
            }
        }

        if ((null !== $contentType) && (null !== $this->rawData)) {
            $this->parsedData = Parser::parseDataByContentType($this->rawData, $contentType, strtoupper($contentEncoding));
        }

        if (empty($this->parsedData)) {
            if (!empty($this->get)) {
                $this->parsedData = $this->get;
            } else if (!empty($this->post)) {
                $this->parsedData = $this->post;
            }
        }

        // If request has filters, filter parsed input data
        if ($this->hasFilters()) {
            $this->parsedData = $this->filter($this->parsedData);

            if (!empty($this->post)) {
                $this->post = $this->filter($this->post);
            }
            if (!empty($this->get)) {
                $this->get = $this->filter($this->get);
            }
        }

        switch (strtoupper($this->getMethod())) {
            case 'PUT':
                $this->put = $this->parsedData;
                break;

            case 'PATCH':
                $this->patch = $this->parsedData;
                break;

            case 'DELETE':
                $this->delete = $this->parsedData;
                break;
        }

        if (null !== $this->rawData) {
            $this->body = new Body($this->rawData);
        }
    }

}
