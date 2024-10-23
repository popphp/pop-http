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
namespace Pop\Http\Client;

use Pop\Http\Uri;
use Pop\Http\AbstractRequest;
use Pop\Mime\Message;
use Pop\Mime\Part\Header;

/**
 * HTTP client request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
class Request extends AbstractRequest
{

    /**
     * Request type constants
     * @var string
     */
    const URLENCODED = 'application/x-www-form-urlencoded';
    const JSON       = 'application/json';
    const XML        = 'application/xml';
    const MULTIPART  = 'multipart/form-data';

    /**
     * Request method
     * @var ?string
     */
    protected ?string $method = null;

    /**
     * Request type
     * @var ?string
     */
    protected ?string $requestType = null;

    /**
     * Client request query data object
     *
     * Can only be a URL-encoded query string on the URI
     *
     * @var ?Data
     */
    protected ?Data $query = null;

    /**
     * Client request data object
     *
     * Can be any type of supported request data:
     *     - URL-encoded query string on the URI (GET method)
     *     - URL-encoded body (any method other than GET)
     *     - JSON-encoded body
     *     - XML-encoded body
     *     - Multipart/form body
     *
     * @var ?Data
     */
    protected ?Data $data = null;

    /**
     * Constructor
     *
     * Instantiate the request data object
     *
     * @param  Uri|string|null $uri
     * @param  string          $method
     * @param  mixed           $data
     * @param  ?string         $type
     * @param  bool            $strict
     * @throws Exception|\Pop\Http\Exception
     */
    public function __construct(Uri|string|null $uri = null, string $method = 'GET', mixed $data = null, ?string $type = null, bool $strict = false)
    {
        parent::__construct($uri);

        if ($method !== null) {
            $this->setMethod($method, $strict);
        }
        if ($data !== null) {
            $this->setData($data);
        }
        if ($type !== null) {
            $this->setRequestType($type);
        }
    }

    /**
     * Factory method to create a Request object
     *
     * @param  Uri|string|null $uri
     * @param  string          $method
     * @param  mixed           $data
     * @param  ?string         $type
     * @param  bool            $strict
     * @throws Exception|\Pop\Http\Exception
     * @return Request
     */
    public static function create(Uri|string|null $uri = null, string $method = 'GET', mixed $data = null, ?string $type = null, bool $strict = false): Request
    {
        return new self($uri, $method, $data, $type, $strict);
    }

    /**
     * Factory method to create a JSON Request object
     *
     * @param  Uri|string|null $uri
     * @param  string          $method
     * @param  mixed           $data
     * @param  bool            $strict
     * @throws Exception|\Pop\Http\Exception
     * @return Request
     */
    public static function createJson(Uri|string|null $uri = null, string $method = 'POST', mixed $data = null, bool $strict = false): Request
    {
        return new self($uri, $method, $data, Request::JSON, $strict);
    }

    /**
     * Factory method to create an XML Request object
     *
     * @param  Uri|string|null $uri
     * @param  string          $method
     * @param  mixed           $data
     * @param  bool            $strict
     * @throws Exception|\Pop\Http\Exception
     * @return Request
     */
    public static function createXml(Uri|string|null $uri = null, string $method = 'POST', mixed $data = null, bool $strict = false): Request
    {
        return new self($uri, $method, $data, Request::XML, $strict);
    }

    /**
     * Factory method to create a URL-encoded Request object
     *
     * @param  Uri|string|null $uri
     * @param  string          $method
     * @param  mixed           $data
     * @param  bool            $strict
     * @throws Exception|\Pop\Http\Exception
     * @return Request
     */
    public static function createUrlEncoded(Uri|string|null $uri = null, string $method = 'GET', mixed $data = null, bool $strict = false): Request
    {
        return new self($uri, $method, $data, Request::URLENCODED, $strict);
    }

    /**
     * Factory method to create a multipart Request object
     *
     * @param  Uri|string|null $uri
     * @param  string          $method
     * @param  mixed           $data
     * @param  bool            $strict
     * @throws Exception|\Pop\Http\Exception
     * @return Request
     */
    public static function createMultipart(Uri|string|null $uri = null, string $method = 'POST', mixed $data = null, bool $strict = false): Request
    {
        return new self($uri, $method, $data, Request::MULTIPART, $strict);
    }

    /**
     * Set method
     *
     * @param  string $method
     * @param  bool   $strict
     * @throws Exception
     * @return Request
     */
    public function setMethod(string $method, bool $strict = false): Request
    {
        $method = strtoupper($method);

        if ($strict) {
            if (!$this->isValidMethod($method)) {
                throw new Exception('Error: That request method is not valid.');
            }
        }

        $this->method = $method;
        return $this;
    }

    /**
     * Get method
     *
     * @return ?string
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Has method
     *
     * @return bool
     */
    public function hasMethod(): bool
    {
        return ($this->method !== null);
    }

    /**
     * Add a header
     *
     * @param  Header|string|int $header
     * @param  ?string           $value
     * @return Request
     */
    public function addHeader(Header|string|int $header, ?string $value = null): Request
    {
        $contentType = null;
        if (is_numeric($header) && ($value !== null)) {
            $header = Header::parse($value);
            $value  = null;
        }
        if (is_string($header) && ($header == 'Content-Type')) {
            $contentType = $value;
        } else if (($header instanceof Header) && ($header->getName() == 'Content-Type')) {
            $contentType = $header->getValue()->getValue();
        }

        if (($contentType !== null) && ($this->requestType === null)) {
            $this->requestType = $contentType;
        }

        parent::addHeader($header, $value);
        return $this;
    }

    /**
     * Set query data
     *
     * @param  mixed $query
     * @param  mixed $filters
     * @return Request
     */
    public function setQuery(mixed $query, mixed $filters = null): Request
    {
        $this->setRequestType(Request::URLENCODED);
        $this->query = ($query instanceof Data) ? $query : new Data($query, $filters, $this);
        return $this;
    }

    /**
     * Add query data
     *
     * @param  mixed $name
     * @param  mixed $value
     * @return Request
     */
    public function addQuery(mixed $name, mixed $value): Request
    {
        $this->setRequestType(Request::URLENCODED);
        if ($this->query === null) {
            $this->setRequestType(Request::URLENCODED);
            $this->query = new Data([], null, $this);
        } else if (!$this->query->hasRequest()) {
            $this->query->setRequest($this);
        }
        $this->query->addData($name, $value);

        return $this;
    }

    /**
     * Get query data
     *
     * @return ?Data
     */
    public function getQuery(): ?Data
    {
        return $this->query;
    }

    /**
     * Has query data
     *
     * @return bool
     */
    public function hasQuery(): bool
    {
        return !empty($this->query);
    }

    /**
     * Remove query data
     *
     * @param  string $key
     * @return Request
     */
    public function removeQuery(string $key): Request
    {
        if ($this->query->hasData($key)) {
            $this->query->removeData($key);
        }
        return $this;
    }

    /**
     * Remove all query data
     *
     * @return Request
     */
    public function removeAllQuery(): Request
    {
        $this->query = null;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        return $this;
    }

    /**
     * Set data
     *
     * @param  mixed $data
     * @param  mixed $filters
     * @return Request
     */
    public function setData(mixed $data, mixed $filters = null): Request
    {
        if ($data instanceof Data) {
            if (!$data->hasRequest()) {
                $data->setRequest($this);
            }
        } else {
            $data = new Data($data, $filters, $this);
        }

        $this->data = $data;

        return $this;
    }

    /**
     * Add data
     *
     * @param  mixed $name
     * @param  mixed $value
     * @return Request
     */
    public function addData(mixed $name, mixed $value): Request
    {
        if ($this->data === null) {
            $this->data = new Data([], null, $this);
        } else if (!$this->data->hasRequest()) {
            $this->data->setRequest($this);
        }
        $this->data->addData($name, $value);

        return $this;
    }

    /**
     * Get data
     *
     * @return ?Data
     */
    public function getData(): ?Data
    {
        return $this->data;
    }

    /**
     * Has data
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return !empty($this->data);
    }

    /**
     * Remove data
     *
     * @param  string $key
     * @return Request
     */
    public function removeData(string $key): Request
    {
        if ($this->data->hasData($key)) {
            $this->data->removeData($key);
        }
        return $this;
    }

    /**
     * Remove all data
     *
     * @return Request
     */
    public function removeAllData(): Request
    {
        $this->data = null;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        if ($this->hasHeader('Content-Length')) {
            $this->removeHeader('Content-Length');
        }
        return $this;
    }

    /**
     * Get full URI as string
     *
     * @param  bool $query
     * @return string
     */
    public function getUriAsString(bool $query = true): string
    {
        $uri = parent::getUriAsString();

        if ($query) {
            $queryString = null;

            // If request has generic query data
            if ($this->hasQuery()) {
                $queryString = $this->query->prepare()->getDataContent();
            // Else, if request has explicit data configured to be a query string over GET
            } else if (($this->method == 'GET') && ($this->hasData()) &&
                (($this->requestType === null) || ($this->requestType == self::URLENCODED))) {
                $queryString = $this->data->prepare()->getDataContent();
            }

            if (!empty($queryString) && !str_contains($uri, $queryString)) {
                $uri .= ((str_contains($uri, '?')) ? '&' : '?') . $queryString;
            }
        }

        return $uri;
    }

    /**
     * Set request type
     *
     * @param  ?string $type
     * @param  bool    $handleHeader
     * @return Request
     */
    public function setRequestType(string $type = null, bool $handleHeader = true): Request
    {
        switch ($type) {
            case self::JSON:
                $this->createAsJson($type, $handleHeader);
                break;
            case self::XML:
                $this->createAsXml($type, $handleHeader);
                break;
            case self::URLENCODED:
                $this->createAsUrlEncoded($type, $handleHeader);
                break;
            case self::MULTIPART:
                $this->createAsMultipart($type);
                break;
            default:
                // Custom content-types
                if ($type !== null) {
                    if (strrpos($type, 'json') !== false) {
                        $this->createAsJson($type);
                    } else if (strrpos($type, 'xml') !== false) {
                        $this->createAsXml($type);
                    } else {
                        $this->createAsCustomType($type);
                    }
                } else {
                    $this->removeRequestType($handleHeader);
                }

        }

        return $this;
    }

    /**
     * Get request type
     *
     * @return ?string
     */
    public function getRequestType(): ?string
    {
        return $this->requestType;
    }

    /**
     * Has request type
     *
     * @return bool
     */
    public function hasRequestType(): bool
    {
        return ($this->requestType !== null);
    }

    /**
     * Remove request type
     *
     * @param  bool $removeHeader
     * @return Request
     */
    public function removeRequestType(bool $removeHeader = false): Request
    {
        $this->requestType = null;
        if (($removeHeader) && ($this->hasHeader('Content-Type'))) {
            $this->removeHeader('Content-Type');
        }
        return $this;
    }

    /**
     * Create request as JSON
     *
     * @param  string $type
     * @param  bool   $addHeader
     * @return Request
     */
    public function createAsJson(string $type = self::JSON, bool $addHeader = true): Request
    {
        $this->requestType = $type;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        if ($addHeader) {
            $this->addHeader('Content-Type', $this->requestType);
        }
        $this->addHeader('Content-Type', $this->requestType);

        return $this;
    }

    /**
     * Check if request is JSON
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return str_contains(strtolower($this->requestType), 'json');
    }

    /**
     * Create request as XML
     *
     * @param  string $type
     * @param  bool   $addHeader
     * @return Request
     */
    public function createAsXml(string $type = self::XML, bool $addHeader = true): Request
    {
        $this->requestType = $type;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }

        if ($addHeader) {
            $this->addHeader('Content-Type', $this->requestType);
        }

        return $this;
    }

    /**
     * Check if request is XML
     *
     * @return bool
     */
    public function isXml(): bool
    {
        return str_contains(strtolower($this->requestType), 'xml');
    }

    /**
     * Create request as a URL-encoded form
     *
     * @param  string $type
     * @param  bool   $addHeader
     * @return Request
     */
    public function createAsUrlEncoded(string $type = self::URLENCODED, bool $addHeader = true): Request
    {
        $this->requestType = $type;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }

        if ($addHeader) {
            $this->addHeader('Content-Type', $this->requestType);
        }

        return $this;
    }

    /**
     * Check if request is a URL-encoded form
     *
     * @return bool
     */
    public function isUrlEncoded(): bool
    {
        return ($this->requestType == self::URLENCODED);
    }

    /**
     * Create request as a multipart form
     *
     * @param  string $type
     * @return Request
     */
    public function createAsMultipart(string $type = self::MULTIPART): Request
    {
        $this->requestType = $type;
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return bool
     */
    public function isMultipart(): bool
    {
        return str_contains(strtolower($this->requestType), self::MULTIPART);
    }

    /**
     * Create request as custom content type
     *
     * @param  string $type
     * @param  bool   $addHeader
     * @return Request
     */
    public function createAsCustomType(string $type, bool $addHeader = true): Request
    {
        $this->requestType = $type;

        if ($addHeader) {
            $this->addHeader('Content-Type', $this->requestType);
        }
        return $this;
    }

    /**
     * Check if request is a custom content type
     *
     * @return bool
     */
    public function isCustomType(): bool
    {
        return (!empty($this->requestType) && (strrpos($this->requestType, 'json') !== false) &&
            (strrpos($this->requestType, 'xml') !== false) && ($this->requestType !== self::URLENCODED) &&
            ($this->requestType !== self::MULTIPART));
    }

    /**
     * Is valid method
     *
     * @param  string $method
     * @return bool
     */
    public function isValidMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'CONNECT', 'OPTIONS', 'TRACE']);
    }

    /**
     * Has data content
     *
     * @return bool
     */
    public function hasDataContent(): bool
    {
        return (($this->data !== null) && ($this->data->hasDataContent()));
    }

    /**
     * Get the data content
     *
     * @return ?string
     */
    public function getDataContent(): ?string
    {
        return $this->data?->getDataContent();
    }

    /**
     * Get the data content length
     *
     * @param  bool $mb
     * @return int|null
     */
    public function getDataContentLength(bool $mb = false): int|null
    {
        return $this->data?->getDataContentLength($mb);
    }

    /**
     * Prepare request data
     *
     * @return Request
     */
    public function prepareData(): Request
    {
        if (!$this->data->hasRequest()) {
            $this->data->setRequest($this);
        }

        $this->data->prepare();

        return $this;
    }

    /**
     * Magic method to check the is[Method](), i.e. $request->isPost();
     *
     * @param  string $methodName
     * @param  ?array $arguments
     * @throws Exception
     * @return bool
     */
    public function __call(string $methodName, ?array $arguments = null): bool
    {
        if (str_starts_with($methodName, 'is')) {
            $method = strtoupper(substr($methodName, 2));
            if ($this->isValidMethod($method)) {
                return ($this->method == $method);
            } else {
                throw new Exception('Error: That request method is not valid.');
            }
        } else {
            throw new Exception('Error: That method/function is not valid.');
        }
    }

}
