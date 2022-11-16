<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Server\Request;

use Pop\Http\AbstractRequest;
use Pop\Http\Parser;

/**
 * HTTP server request data class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.1.0
 */
class Data extends AbstractRequest
{

    /**
     * GET array
     * @var array
     */
    protected $get = [];

    /**
     * POST array
     * @var array
     */
    protected $post = [];

    /**
     * FILES array
     * @var array
     */
    protected $files = [];

    /**
     * PUT array
     * @var array
     */
    protected $put = [];

    /**
     * PATCH array
     * @var array
     */
    protected $patch = [];

    /**
     * DELETE array
     * @var array
     */
    protected $delete = [];

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
     * Constructor
     *
     * Instantiate the request data object
     *
     * @param  string $contentType
     * @param  string $encoding
     * @param  mixed  $filters
     * @param  mixed  $streamToFile
     */
    public function __construct($contentType = null, $encoding = null, $filters = null, $streamToFile = null)
    {
        parent::__construct($filters);

        $this->get   = (isset($_GET))   ? $_GET   : [];
        $this->post  = (isset($_POST))  ? $_POST  : [];
        $this->files = (isset($_FILES)) ? $_FILES : [];

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->processData($contentType, $encoding, $streamToFile);
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
     * Has query data
     *
     * @return boolean
     */
    public function hasQueryData()
    {
        return !empty($this->queryData);
    }

    /**
     * Has parsed data
     *
     * @return boolean
     */
    public function hasParsedData()
    {
        return !empty($this->parsedData);
    }

    /**
     * Has raw data
     *
     * @return boolean
     */
    public function hasRawData()
    {
        return !empty($this->rawData);
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
     * @return Data
     */
    public function processStreamToFile()
    {
        if (($this->streamToFile) && file_exists($this->streamToFileLocation)) {
            $contentType      = $this->getHeaderValue('Content-Type');
            $contentEncoding  = $this->getHeaderValue('Content-Encoding');
            $this->rawData    = file_get_contents($this->streamToFileLocation);
            $this->parsedData = Parser::parseDataByContentType($this->rawData, $contentType, $contentEncoding);
        }

        return $this;
    }

    /**
     * Process any data that came with the request
     *
     * @param  string $contentType
     * @param  string $encoding
     * @param  mixed  $streamToFile
     * @return void
     */
    public function processData($contentType = null, $encoding = null, $streamToFile = null)
    {
        // Stream raw data to file location
        if (null !== $streamToFile) {
            $this->prepareStreamToFile($streamToFile);
        } else {
            /**
             * $_SERVER['X_POP_HTTP_RAW_DATA'] is for testing purposes only
             */
            $this->rawData = (isset($_SERVER['X_POP_HTTP_RAW_DATA'])) ?
                $_SERVER['X_POP_HTTP_RAW_DATA'] : file_get_contents('php://input');
        }

        // Process query string
        if (isset($_SERVER['QUERY_STRING'])) {
            $this->queryData = rawurldecode($_SERVER['QUERY_STRING']);
            $this->queryData = ((null !== $contentType) && ((stripos($contentType, 'json') !== false) || (stripos($contentType, 'xml') !== false))) ?
                Parser::parseDataByContentType($this->queryData, $contentType, $encoding) :
                Parser::parseDataByContentType($this->queryData, 'application/x-www-form-urlencoded', $encoding);

            if (empty($this->rawData)) {
                $this->rawData = $_SERVER['QUERY_STRING'];
            }
        }

        // Process raw data
        if ((null !== $contentType) && (null !== $this->rawData)) {
            $this->parsedData = Parser::parseDataByContentType($this->rawData, $contentType, $encoding);
        }

        // If the query string had a processed custom data string
        if ((strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') && ($this->get != $this->queryData) && !empty($this->queryData)) {
            $this->get = $this->queryData;
        // If the request was POST and had processed custom data
        } else if ((strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') && ($this->post != $this->parsedData) && !empty($this->parsedData)) {
            $this->post = $this->parsedData;
        }

        if (empty($this->parsedData)) {
            if (!empty($this->get)) {
                $this->parsedData = $this->get;
            } else if (!empty($this->post)) {
                $this->parsedData = $this->post;
            }
        }

        // If request data has filters, filter parsed input data
        if ($this->hasFilters()) {
            if (!empty($this->parsedData)) {
                $this->parsedData = $this->filter($this->parsedData);
            }
            if (!empty($this->post)) {
                $this->post = $this->filter($this->post);
            }
            if (!empty($this->get)) {
                $this->get = $this->filter($this->get);
            }
        }

        // Set parsed data to the proper method-based array
        switch (strtoupper($_SERVER['REQUEST_METHOD'])) {
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

        if (null !== $contentType) {
            $this->addHeader('Content-Type', $contentType);
        }
        if (null !== $encoding) {
            $this->addHeader('Content-Encoding', $encoding);
        }
    }

    /**
     * Prepare stream to file
     *
     * @param  mixed $streamToFile
     * @throws Exception
     * @return void
     */
    public function prepareStreamToFile($streamToFile)
    {
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

        /**
         * $_SERVER['X_POP_HTTP_RAW_DATA'] is for testing purposes only
         */
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
    }

    /**
     * Clear stream to file
     *
     * @return Data
     */
    public function clearStreamToFile()
    {
        if (file_exists($this->streamToFileLocation) && is_writable($this->streamToFileLocation)) {
            unlink($this->streamToFileLocation);
        }
        return $this;
    }

    /**
     * Magic method to get a value from one of the data arrays
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
            case 'parsed':
                return $this->parsedData;
                break;
            case 'raw':
                return $this->rawData;
                break;
            default:
                return null;
        }
    }

}