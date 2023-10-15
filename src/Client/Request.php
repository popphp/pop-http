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
 * @version    5.0.0
 */
class Request extends AbstractRequest
{

    /**
     * Request type constants
     * @var string
     */
    const JSON      = 'application/json';
    const XML       = 'application/xml';
    const URLFORM   = 'application/x-www-form-urlencoded';
    const MULTIPART = 'multipart/form-data';
  
    /**
     * Request method
     * @var ?string
     */
    protected ?string $method = null;

    /**
     * Client request data object
     * @var ?Data
     */
    protected ?Data $data = null;

    /**
     * Request type
     * @var ?string
     */
    protected ?string $requestType = null;

    /**
     * Request data content
     * @var string|array|null
     */
    protected string|array|null $dataContent = null;

    /**
     * Constructor
     *
     * Instantiate the request data object
     *
     * @param  Uri|string|null $uri
     * @param  string          $method
     * @param  array|Data|null $data
     * @param  ?string         $type
     * @throws Exception
     */
    public function __construct(Uri|string|null $uri = null, string $method = 'GET', array|Data|null $data = null, ?string $type = null)
    {
        parent::__construct($uri);

        if ($method !== null) {
            $this->setMethod($method);
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
     * @param  string $method
     * @param  array|Data|null $data
     * @param  ?string $type
     * @throws Exception
     * @return Request
     */
    public static function create(
        Uri|string|null $uri = null, string $method = 'GET', array|Data|null $data = null, ?string $type = null
    ): Request
    {
        return new self($uri, $method, $data, $type);
    }

    /**
     * Set method
     *
     * @param  string $method
     * @param  bool   $strict
     * @throws Exception
     * @return Request
     */
    public function setMethod(string $method, bool $strict = true): Request
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
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
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
            $contentType = $header->getValueAsString();
        }

        switch ($contentType) {
            case Request::JSON:
                $this->requestType = Request::JSON;
                break;
            case Request::XML:
                $this->requestType = Request::XML;
                break;
            case Request::URLFORM:
                $this->requestType = Request::URLFORM;
                break;
            case Request::MULTIPART:
                $this->requestType = Request::MULTIPART;
                break;
        }

        parent::addHeader($header, $value);
        return $this;
    }

    /**
     * Add all headers
     *
     * @param  array $headers
     * @return Request
     */
    public function addHeaders(array $headers): Request
    {
        foreach ($headers as $header => $value) {
            if ($value instanceof Header) {
                $this->addHeader($value);
            } else {
                $this->addHeader($header, $value);
            }
        }
        return $this;
    }

    /**
     * Set data
     *
     * @param  array|string|Data $data
     * @return Request
     */
    public function setData(array|string|Data $data): Request
    {
        if (is_string($data)) {
            $this->data = new Data();
            $this->data->setData($data);
        } else {
            $this->data = (is_array($data)) ? new Data($data) : $data;
        }
        return $this;
    }

    /**
     * Get data
     *
     * @param  bool $asArray
     * @return Data|array
     */
    public function getData(bool $asArray = false): Data|array
    {
        return ($asArray) ? $this->data->getData() : $this->data;
    }

    /**
     * Has data
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return ($this->data !== null);
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

        if (($this->method == 'GET') && ($query) && ($this->data !== null)) {
            if ($this->dataContent !== null) {
                $uri .= '?' . ((is_array($this->dataContent)) ? http_build_query($this->dataContent) : $this->dataContent);
            } else if (($this->requestType === null) || ($this->requestType == self::URLFORM)) {
                $uri .= $this->data->prepareQueryString(true);
            }
        }

        return $uri;
    }

    /**
     * Set request type
     *
     * @param  string $type
     * @return Request
     */
    public function setRequestType(string $type): Request
    {
        switch ($type) {
            case self::JSON:
                $this->createAsJson();
                break;
            case self::XML:
                $this->createAsXml();
                break;
            case self::URLFORM:
                $this->createUrlEncoded();
                break;
            case self::MULTIPART:
                $this->createMultipart();
                break;
        }

        return $this;
    }

    /**
     * Get request type
     *
     * @return string
     */
    public function getRequestType(): string
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
     * Create request as JSON
     *
     * @return Request
     */
    public function createAsJson(): Request
    {
        $this->requestType = self::JSON;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
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
        return ($this->requestType == self::JSON);
    }

    /**
     * Create request as XML
     *
     * @return Request
     */
    public function createAsXml(): Request
    {
        $this->requestType = self::XML;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        $this->addHeader('Content-Type', $this->requestType);

        return $this;
    }

    /**
     * Check if request is XML
     *
     * @return bool
     */
    public function isXml(): bool
    {
        return ($this->requestType == self::XML);
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return Request
     */
    public function createUrlEncoded(): Request
    {
        $this->requestType = self::URLFORM;

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        $this->addHeader('Content-Type', $this->requestType);

        return $this;
    }

    /**
     * Check if request is a URL-encoded form
     *
     * @return bool
     */
    public function isUrlEncoded(): bool
    {
        return ($this->requestType == self::URLFORM);
    }

    /**
     * Create request as a multipart form
     *
     * @return Request
     */
    public function createMultipart(): Request
    {
        $this->requestType = self::MULTIPART;
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return bool
     */
    public function isMultipart(): bool
    {
        return ($this->requestType == self::MULTIPART);
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
        return ($this->dataContent !== null);
    }

    /**
     * Get the data content
     *
     * @return string|array|null
     */
    public function getDataContent(): string|array|null
    {
        return $this->dataContent;
    }

    /**
     * Get the content length
     *
     * @return int|null
     */
    public function getDataContentLength(bool $mb = false): int|null
    {
        if (!is_array($this->dataContent)) {
            return ($mb) ? mb_strlen($this->dataContent) : strlen($this->dataContent);
        } else {
            return null;
        }
    }

    /**
     * Prepare request data
     *
     * @return void
     */
    public function prepareData(): void
    {
        // Prepare the GET query string
        if (($this->method == 'GET') && ((!$this->hasHeader('Content-Type')) ||
            ($this->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded'))) {
            $this->dataContent = ($this->data->hasRawData()) ?
                $this->data->getRawData() : $this->data->prepareQueryString();
        // Else, prepare the data content
        } else if ($this->method != 'GET') {
            switch ($this->requestType) {
                case Request::JSON:
                    $this->prepareJson();
                    break;
                case Request::XML:
                    $this->prepareXml();
                    break;
                case Request::URLFORM:
                    $this->prepareUrlEncoded();
                    break;
                case Request::MULTIPART:
                    $this->prepareMultipart();
                    break;
            }

            // Fallback
            if ($this->dataContent === null) {
                // If the request has raw data
                if ($this->data->hasRawData()) {
                    $this->prepareRawData();
                // Else, use basic data
                } else if ($this->data !== null) {
                    $this->dataContent = $this->getData(true);
                    if (!$this->hasHeader('Content-Type')) {
                        $this->setRequestType(Request::URLFORM);
                    }
                }
            }
        }

        if ($this->method != 'GET') {
            if ($this->getDataContentLength() !== null) {
                $this->addHeader('Content-Length', $this->getDataContentLength());
            }
        }
    }

    /**
     * Method to prepare JSON data content
     *
     * @return Request
     */
    public function prepareJson(): Request
    {
        if ($this->data->hasRawData()) {
            $jsonContent = $this->data->getRawData();
        } else {
            $jsonData    = $this->getData(true);
            $jsonContent = [];

            // Check for JSON files
            foreach ($jsonData as $jsonDatum) {
                if (isset($jsonDatum['filename']) && isset($jsonDatum['contentType']) &&
                    ($jsonDatum['contentType'] == 'application/json') && file_exists($jsonDatum['filename'])) {
                    $jsonContent = array_merge($jsonContent, json_decode(file_get_contents($jsonDatum['filename']), true));
                }
            }

            // Else, use JSON data
            if (empty($jsonContent)) {
                $jsonContent = $jsonData;
            }
        }

        // Only encode if the data isn't already encoded
        if (!((is_string($jsonContent) && (json_decode($jsonContent) !== false)) && (json_last_error() == JSON_ERROR_NONE))) {
            $this->dataContent = json_encode($jsonContent, JSON_PRETTY_PRINT);
        }

        return $this;
    }

    /**
     * Method to prepare XML data content
     *
     * @return Request
     */
    public function prepareXml(): Request
    {
        if ($this->data->hasRawData()) {
            $xmlContent = $this->data->getRawData();
        } else {
            $xmlData    = $this->getData(true);
            $xmlContent = null;

            // Check for XML files
            foreach ($xmlData as $xmlDatum) {
                $xmlContent .= (isset($xmlDatum['filename']) && isset($xmlDatum['contentType']) &&
                    ($xmlDatum['contentType'] == 'application/xml') && file_exists($xmlDatum['filename'])) ?
                    file_get_contents($xmlDatum['filename']) : $xmlDatum;
            }

            // Else, use xml data
            if (empty($xmlContent)) {
                $xmlContent = $xmlData;
            }
        }

        $this->dataContent = $xmlContent;

        return $this;
    }

    /**
     * Method to prepare URL-encoded data content
     *
     * @return Request
     */
    public function prepareUrlEncoded(): Request
    {
        $this->dataContent = $this->data->prepareQueryString();
        return $this;
    }

    /**
     * Method to prepare multipart data content
     *
     * @return Request
     */
    public function prepareMultipart(): Request
    {
        $formMessage       = Message::createForm($this->data->getData());
        $contentType       = $formMessage->getHeader('Content-Type');
        $this->dataContent = $formMessage->render(false);
        $this->addHeader($contentType);

        return $this;
    }

    /**
     * Method to prepare raw data content
     *
     * @return Request
     */
    public function prepareRawData(): Request
    {
        $this->dataContent = $this->data->getRawData();
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
