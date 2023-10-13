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
     * Set data
     *
     * @param  array|Data $data
     * @return Request
     */
    public function setData(array|Data $data): Request
    {
        $this->data = (is_array($data)) ? new Data($data) : $data;
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
    public function getFullUriAsString(bool $query = true): string
    {
        $uri = $this->getUriAsString();

        if (($this->method == 'GET') && ($this->data !== null) &&
            (($this->requestType === null) || ($this->requestType == self::URLFORM))) {
            $uri .= $this->data->prepareQueryString(true);
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
                $this->createUrlEncodedForm();
                break;
            case self::MULTIPART:
                $this->createMultipartForm();
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
    public function createUrlEncodedForm(): Request
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
    public function isUrlEncodedForm(): bool
    {
        return ($this->requestType == self::URLFORM);
    }

    /**
     * Create request as a multipart form
     *
     * @return Request
     */
    public function createMultipartForm(): Request
    {
        $this->requestType = self::MULTIPART;
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return bool
     */
    public function isMultipartForm(): bool
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
