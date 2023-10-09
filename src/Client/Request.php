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
     * @param ?Uri $uri
     * @param ?Data $data
     * @param ?string $type
     */
    public function __construct(?Uri $uri = null, ?Data $data = null, ?string $type = null)
    {
        parent::__construct($uri);

        if ($data !== null) {
            $this->setData($data);
        }
        if ($type !== null) {
            switch ($type) {
                case 'application/json':
                    $this->createAsJson();
                    break;
                case 'application/xml':
                    $this->createAsXml();
                    break;
                case 'application/x-www-form-urlencoded':
                    $this->createUrlEncodedForm();
                    break;
                case 'multipart/form-data':
                    $this->createMultipartForm();
                    break;
            }
        }
    }

    /**
     * Factory method to create a Request object
     *
     * @param  ?Uri $uri
     * @param  ?Data $data
     * @return Request
     */
    public static function create(?Uri $uri = null, ?Data $data = null, ?string $type = null): Request
    {
        return new self($uri, $data);
    }

    /**
     * Set data
     *
     * @param  Data $data
     * @return Request
     */
    public function setData(Data $data): Request
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get data
     *
     * @return Uri
     */
    public function getData(): Data
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
        return ($this->data !== null);
    }

    /**
     * Create request as JSON
     *
     * @return Request
     */
    public function createAsJson(): Request
    {
        $this->requestType = 'application/json';

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
        return ($this->requestType == 'application/json');
    }

    /**
     * Create request as XML
     *
     * @return Request
     */
    public function createAsXml(): Request
    {
        $this->requestType = 'application/xml';

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
    public function isXml()
    {
        return ($this->requestType == 'application/xml');
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return Request
     */
    public function createUrlEncodedForm(): Request
    {
        $this->requestType = 'application/x-www-form-urlencoded';

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
        return ($this->requestType == 'application/x-www-form-urlencoded');
    }

    /**
     * Create request as a multipart form
     *
     * @return Request
     */
    public function createMultipartForm(): Request
    {
        $this->requestType = 'multipart/form-data';
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return bool
     */
    public function isMultipartForm(): bool
    {
        return ($this->requestType == 'multipart/form-data');
    }

    /**
     * Get form type
     *
     * @return string
     */
    public function getRequestType(): string
    {
        return $this->requestType;
    }

}
