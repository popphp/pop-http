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
namespace Pop\Http\Server;

use Pop\Http\Parser;
use Pop\Http\HttpFilterableTrait;

/**
 * HTTP server request data class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Data
{

    use HttpFilterableTrait;

    /**
     * GET array
     * @var array
     */
    protected array $get = [];

    /**
     * POST array
     * @var array
     */
    protected array $post = [];

    /**
     * FILES array
     * @var array
     */
    protected array $files = [];

    /**
     * PUT array
     * @var array
     */
    protected array $put = [];

    /**
     * PATCH array
     * @var array
     */
    protected array $patch = [];

    /**
     * DELETE array
     * @var array
     */
    protected array $delete = [];

    /**
     * Query data
     * @var mixed
     *
     * Permanently unset (always null) now that the QUERY_STRING re-parse that populated it has been
     * removed; kept only so the deprecated getQueryData()/hasQueryData() accessors below have something
     * to return. Use getQuery() instead, which reads directly from PHP's native $_GET.
     */
    protected mixed $queryData = null;

    /**
     * Parsed data
     * @var mixed
     */
    protected mixed $parsedData = null;

    /**
     * Raw data
     * @var mixed
     */
    protected mixed $rawData = null;

    /**
     * Stream to file
     * @var bool
     */
    protected bool $streamToFile = false;

    /**
     * Stream to file
     * @var ?string
     */
    protected ?string $streamToFileLocation = null;

    /**
     * Constructor
     *
     * Instantiate the request data object
     *
     * @param  ?string $contentType
     * @param  ?string $encoding
     * @param  mixed $filters
     * @param  mixed $streamToFile
     * @param  bool $populateFromGlobals
     * @throws Exception
     */
    public function __construct(
        ?string $contentType = null, ?string $encoding = null, mixed $filters = null,
        mixed $streamToFile = null, bool $populateFromGlobals = true
    )
    {
        if ($filters !== null) {
            if (is_array($filters)) {
                $this->addFilters($filters);
            } else {
                $this->addFilter($filters);
            }
        }

        if ($populateFromGlobals) {
            $this->get   = (isset($_GET))   ? $_GET   : [];
            $this->post  = (isset($_POST))  ? $_POST  : [];
            $this->files = (isset($_FILES)) ? $_FILES : [];

            if (isset($_SERVER['REQUEST_METHOD'])) {
                $this->processData($contentType, $encoding, $streamToFile);
            }
        }
    }

    /**
     * Return whether or not the request has FILES
     *
     * @return bool
     */
    public function hasFiles(): bool
    {
        return (count($this->files) > 0);
    }

    /**
     * Get a value from $_GET, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getQuery(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->get;
        } else {
            return $this->get[$key] ?? null;
        }
    }

    /**
     * Get a value from $_POST, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getPost(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->post;
        } else {
            return $this->post[$key] ?? null;
        }
    }

    /**
     * Get a value from $_FILES, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getFiles(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->files;
        } else {
            return $this->files[$key] ?? null;
        }
    }

    /**
     * Get a value from PUT query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getPut(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->put;
        } else {
            return $this->put[$key] ?? null;
        }
    }

    /**
     * Get a value from PATCH query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getPatch(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->patch;
        } else {
            return $this->patch[$key] ?? null;
        }
    }

    /**
     * Get a value from DELETE query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getDelete(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->delete;
        } else {
            return $this->delete[$key] ?? null;
        }
    }

    /**
     * Get a value from query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     * @deprecated This always returns null now: the QUERY_STRING re-parse that used to populate
     *             $queryData was removed. Use getQuery() instead, which reads directly from PHP's
     *             native $_GET.
     */
    public function getQueryData(?string $key = null): string|array|null
    {
        $result = null;

        if (is_array($this->queryData)) {
            if ($key === null) {
                $result = $this->queryData;
            } else {
                $result = $this->queryData[$key] ?? null;
            }
        }

        return $result;
    }

    /**
     * Get a value from parsed data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getParsedData(?string $key = null): string|array|null
    {
        $result = null;

        if (is_array($this->parsedData)) {
            if ($key === null) {
                $result = $this->parsedData;
            } else {
                $result = $this->parsedData[$key] ?? null;
            }
        }

        return $result;
    }

    /**
     * Get the raw data
     *
     * @return string|null
     */
    public function getRawData(): string|null
    {
        return $this->rawData;
    }

    /**
     * Has query data
     *
     * @return bool
     * @deprecated This always returns false now: the QUERY_STRING re-parse that used to populate
     *             $queryData was removed. Check getQuery() instead (e.g. !empty($this->getQuery())),
     *             which reads directly from PHP's native $_GET.
     */
    public function hasQueryData(): bool
    {
        return !empty($this->queryData);
    }

    /**
     * Has parsed data
     *
     * @return bool
     */
    public function hasParsedData(): bool
    {
        return !empty($this->parsedData);
    }

    /**
     * Has raw data
     *
     * @return bool
     */
    public function hasRawData(): bool
    {
        return !empty($this->rawData);
    }

    /**
     * Is the request stream to file
     *
     * @return bool
     */
    public function isStreamToFile(): bool
    {
        return $this->streamToFile;
    }

    /**
     * Get stream to file location
     *
     * @return string
     */
    public function getStreamToFileLocation(): string
    {
        return $this->streamToFileLocation;
    }

    /**
     * Process stream to file
     *
     * @param  ?string $contentType
     * @param  ?string $contentEncoding
     * @return Data
     */
    public function processStreamToFile(?string $contentType = null, ?string $contentEncoding = null): Data
    {
        if (($this->streamToFile) && file_exists($this->streamToFileLocation)) {
            $this->rawData    = file_get_contents($this->streamToFileLocation);
            $this->parsedData = Parser::parseDataByContentType($this->rawData, $contentType, $contentEncoding);
        }

        return $this;
    }

    /**
     * Process any data that came with the request
     *
     * @param  ?string $contentType
     * @param  ?string $encoding
     * @param  mixed  $streamToFile
     * @throws Exception
     * @return void
     */
    public function processData(?string $contentType = null, ?string $encoding = null, mixed $streamToFile = null)
    {
        $method   = strtoupper($_SERVER['REQUEST_METHOD']);
        $isMultipart = ($contentType !== null) && (stripos($contentType, 'multipart/form-data') !== false);

        // Multipart POST requests: PHP has already correctly parsed $_POST/$_FILES natively.
        // php://input is documented to be empty for this content type, so there is
        // nothing useful to re-parse - trust PHP's own parse directly. Multipart bodies on
        // other methods (PUT/PATCH/DELETE) are NOT natively parsed by PHP into any
        // superglobal, so those still need the manual raw-body parse below.
        if ($isMultipart && $method === 'POST') {
            if ($streamToFile !== null) {
                $this->prepareStreamToFile($streamToFile);
            }
            $this->parsedData = $this->post;
            $this->applyFilters();
            return;
        }

        // Stream raw data to file location
        if ($streamToFile !== null) {
            $this->prepareStreamToFile($streamToFile);
        } else {
            /**
             * $_SERVER['X_POP_HTTP_RAW_DATA'] is for testing purposes only
             */
            $this->rawData = (isset($_SERVER['X_POP_HTTP_RAW_DATA'])) ?
                $_SERVER['X_POP_HTTP_RAW_DATA'] : file_get_contents('php://input');
        }

        // GET and POST with a body PHP natively parses (url-encoded, or no content type
        // at all): trust PHP's own native $_GET/$_POST parse directly, no redundant
        // re-parse of QUERY_STRING/raw input needed for these.
        $isNativelyParsedPost = ($method === 'POST') &&
            (($contentType === null) || (stripos($contentType, 'application/x-www-form-urlencoded') !== false));

        if ($method === 'GET') {
            $this->parsedData = $this->get;
            // A GET request carries its data in the query string, so that IS its raw data.
            // php://input (read above) is empty for GET, so fall back to QUERY_STRING - but only
            // if nothing was already captured above (streamToFile, or the X_POP_HTTP_RAW_DATA
            // test override), which must not be clobbered. This is a straight raw-value copy,
            // not a re-parse: $this->get is still PHP's own native parse of the query string.
            if (empty($this->rawData)) {
                $this->rawData = $_SERVER['QUERY_STRING'] ?? '';
            }
        } else if ($isNativelyParsedPost) {
            $this->parsedData = $this->post;
        } else if (($contentType !== null) && ($this->rawData !== null)) {
            // PUT/PATCH/DELETE, POST bodies PHP doesn't natively parse (JSON/XML/multipart),
            // and any other method: a manual parse against the raw body is required.
            $this->parsedData = Parser::parseDataByContentType($this->rawData, $contentType, $encoding);
        }

        $this->applyFilters();

        switch ($method) {
            case 'PUT':
                $this->put = (!empty($this->parsedData)) ? $this->parsedData : [];
                break;
            case 'PATCH':
                $this->patch = (!empty($this->parsedData)) ? $this->parsedData : [];
                break;
            case 'DELETE':
                $this->delete = (!empty($this->parsedData)) ? $this->parsedData : [];
                break;
        }
    }

    /**
     * Apply any configured filters to the parsed, POST and GET data arrays
     *
     * Shared by both processData() exit paths (the multipart-POST early return and the
     * general path) so filtering behavior can't drift between them.
     *
     * @return void
     */
    private function applyFilters(): void
    {
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
    }

    /**
     * Prepare stream to file
     *
     * @param  mixed $streamToFile
     * @throws Exception
     * @return void
     */
    public function prepareStreamToFile(mixed $streamToFile): void
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
            $this->rawData = ($_SERVER['X_POP_HTTP_RAW_DATA'] ?? file_get_contents('php://input'));
            file_put_contents($this->streamToFileLocation, $this->rawData);

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
    public function clearStreamToFile(): Data
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
    public function __get(string $name): mixed
    {
        return match ($name) {
            'get'    => $this->get,
            'post'   => $this->post,
            'files'  => $this->files,
            'put'    => $this->put,
            'patch'  => $this->patch,
            'delete' => $this->delete,
            'parsed' => $this->parsedData,
            'raw'    => $this->rawData,
            default  => null,
        };
    }

}
