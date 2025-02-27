<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client;

use Pop\Http\HttpFilterableTrait;
use Pop\Mime\Message;
use Pop\Mime\Part;

/**
 * Client request data class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.3.2
 */
class Data
{

    use HttpFilterableTrait;

    /**
     * Raw data constant
     */
    const POP_CLIENT_REQUEST_RAW_DATA = 'POP_CLIENT_REQUEST_RAW_DATA';

    /**
     * Data fields (form fields and files)
     *    $data = [
     *      'username' => 'admin'
     *      'file1'     => [
     *          'filename'    => __DIR__ . '/path/to/file.txt',
     *          'contentType' => 'text/plain'
     *      ],
     *      'file2'     => [
     *          'filename'    => 'test.pdf',
     *          'contentType' => 'application/pdf',
     *          'contents'    => file_get_contents(__DIR__ . '/path/to/test.pdf'
     *      ]
     *    ]
     * @var array
     */
    protected array $data = [];

    /**
     * Data parent request
     * @var ?Request
     */
    protected ?Request $request = null;

    /**
     * Data content
     * @var ?string
     */
    protected ?string $dataContent = null;

    /**
     * Data content prepared flag
     * @var bool
     */
    protected bool $prepared = false;

    /**
     * Common mime types
     * @var array
     */
    protected static array $mimeTypes = [
        'bmp'    => 'image/x-ms-bmp',
        'bz2'    => 'application/bzip2',
        'csv'    => 'text/csv',
        'doc'    => 'application/msword',
        'docx'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'gif'    => 'image/gif',
        'gz'     => 'application/gzip',
        'jpe'    => 'image/jpeg',
        'jpg'    => 'image/jpeg',
        'jpeg'   => 'image/jpeg',
        'json'   => 'application/json',
        'log'    => 'text/plain',
        'pdf'    => 'application/pdf',
        'png'    => 'image/png',
        'ppt'    => 'application/msword',
        'pptx'   => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'psd'    => 'image/x-photoshop',
        'svg'    => 'image/svg+xml',
        'tar'    => 'application/x-tar',
        'tbz'    => 'application/bzip2',
        'tbz2'   => 'application/bzip2',
        'tgz'    => 'application/gzip',
        'tif'    => 'image/tiff',
        'tiff'   => 'image/tiff',
        'tsv'    => 'text/tsv',
        'txt'    => 'text/plain',
        'xls'    => 'application/msword',
        'xlsx'   => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml'    => 'application/xml',
        'zip'    => 'application/zip'
    ];

    /**
     * Default mime type
     * @var string
     */
    protected static string $defaultMimeType = 'application/octet-stream';

    /**
     * Constructor
     *
     * Instantiate the request data object
     *
     * @param array|string $data
     * @param mixed        $filters
     * @param ?string      $type
     */
    public function __construct(array|string $data = [], mixed $filters = null, ?Request $request = null)
    {
        if ($filters !== null) {
            if (is_array($filters)) {
                $this->addFilters($filters);
            } else {
                $this->addFilter($filters);
            }
        }

        if ($request !== null) {
            $this->setRequest($request);
        }

        if (!empty($data)) {
            $this->setData($data);
        }
    }

    /**
     * Set data
     *
     * @param  array|string $data
     * @return Data
     */
    public function setData(array|string $data): Data
    {
        if (is_string($data)) {
            $this->data = [self::POP_CLIENT_REQUEST_RAW_DATA => $data];
        } else if (is_array($data) && (count($data) == 1) && isset($data[0])) {
            $this->data = [self::POP_CLIENT_REQUEST_RAW_DATA => $data[0]];
        } else {
            $this->data = $data;
            $this->prepare();
        }

        return $this;
    }

    /**
     * Add data
     *
     * @param  array|string $data
     * @param  mixed        $value
     * @return Data
     */
    public function addData(array|string $data, mixed $value = null): Data
    {
        if (is_string($data) && ($value !== null)) {
            $this->data[$data] = $value;
        } else if (is_array($data)) {
            $this->data = array_merge($this->data, $data);
        }

        $this->prepare();

        return $this;
    }

    /**
     * Get data
     *
     * @param  ?string $key
     * @return mixed
     */
    public function getData(?string $key = null): mixed
    {
        if ($key !== null) {
            return $this->data[$key] ?? null;
        } else {
            return $this->data;
        }
    }

    /**
     * Has data
     *
     * @param  ?string $key
     * @return bool
     */
    public function hasData(?string $key = null): bool
    {
        if ($key !== null) {
            return (isset($this->data[$key]));
        } else {
            return !empty($this->data);
        }
    }

    /**
     * Remove data
     *
     * @param  string $key
     * @return Data
     */
    public function removeData(string $key): Data
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }

        $this->prepare();

        return $this;
    }

    /**
     * Remove all data
     *
     * @return Data
     */
    public function removeAllData(): Data
    {
        $this->data        = [];
        $this->dataContent = null;

        return $this;
    }

    /**
     * Set data parent request
     *
     * @param  Request $request
     * @return Data
     */
    public function setRequest(Request $request): Data
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get data parent request
     *
     * @return ?Request
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Has data parent request
     *
     * @return bool
     */
    public function hasRequest(): bool
    {
        return !empty($this->request);
    }

    /**
     * Get prepared data content
     *
     * @return ?string
     */
    public function getDataContent(): ?string
    {
        return $this->dataContent;
    }

    /**
     * Get data content length
     *
     * @param  bool $mb
     * @return int
     */
    public function getDataContentLength(bool $mb = false): int
    {
        return ($mb) ? mb_strlen($this->dataContent) : strlen($this->dataContent);
    }

    /**
     * Check if the data content has been prepared
     *
     * @return bool
     */
    public function hasDataContent(): bool
    {
        return !empty($this->dataContent);
    }

    /**
     * Check if the data content has been prepared (alias to hasDataContent)
     *
     * @return bool
     */
    public function isPrepared(): bool
    {
        return $this->prepared;
    }

    /**
     * Prepare data
     *
     * @param  bool $mb
     * @return Data
     */
    public function prepare(bool $mb = false): Data
    {
        $type = $this->request?->getRequestType();
        switch ($type) {
            case Request::JSON:
                $this->prepareJson();
                break;
            case Request::XML:
                $this->prepareXml();
                break;
            case Request::URLENCODED:
                $this->prepareUrlEncoded();
                break;
            case Request::MULTIPART:
                $this->prepareMultipart();
                break;
            default:
                // Custom types
                if (($type !== null) && (strrpos($type, 'json') !== false)) {
                    $this->prepareJson();
                } else if (($type !== null) && (strrpos($type, 'xml') !== false)) {
                    $this->prepareXml();
                } else {
                    if ($this->hasRawData()) {
                        $this->dataContent = $this->getRawData();
                    } else if ($this->hasData()) {
                        $this->prepareUrlEncoded();
                    }
                }

        }

        if (!empty($this->dataContent) && ($this->hasRequest()) && ($this->request->getMethod() != 'GET')) {
            if ($this->request->hasHeader('Content-Length')) {
                $this->request->removeHeader('Content-Length');
            }
            $this->request->addHeader('Content-Length', strlen($this->dataContent));
        }

        $this->prepared = true;

        return $this;
    }

    /**
     * Reset data
     *
     * @return Data
     */
    public function reset(): Data
    {
        $this->dataContent = null;
        $this->prepared    = false;
        return $this;
    }

    /**
     * Method to prepare URL-encoded data content
     *
     * @return Data
     */
    public function prepareUrlEncoded(): Data
    {
        if (!array_key_exists(self::POP_CLIENT_REQUEST_RAW_DATA, $this->data) && !empty($this->data)) {
            $data = $this->data;
            if ($this->hasFilters()) {
                $data = $this->filter($data);
            }
            $this->dataContent = http_build_query($data);
        } else {
            $this->dataContent = null;
        }

        if ($this->hasRequest()) {
            if ($this->request->hasHeader('Content-Type')) {
                $this->request->removeHeader('Content-Type');
            }
            $this->request->addHeader('Content-Type', Request::URLENCODED);
        }

        return $this;
    }

    /**
     * Method to prepare JSON data content
     *
     * @return Data
     */
    public function prepareJson(): Data
    {
        if ($this->hasRawData()) {
            $jsonContent = $this->getRawData();
        } else {
            $jsonData    = $this->data;
            $jsonContent = [];

            // Check for JSON files
            foreach ($jsonData as $jsonDatum) {
                if (isset($jsonDatum['filename']) && isset($jsonDatum['contentType']) &&
                    str_contains(strtolower($jsonDatum['contentType']), 'json') && file_exists($jsonDatum['filename'])) {
                    $jsonContent = array_merge($jsonContent, json_decode(file_get_contents($jsonDatum['filename']), true));
                }
            }

            // Else, use JSON data
            if (empty($jsonContent)) {
                $jsonContent = $jsonData;
            }
        }

        if ($this->hasRequest()) {
            if ($this->request->hasHeader('Content-Type') && !str_contains(strtolower((string)$this->request->getHeader('Content-Type')), 'json')) {
                $this->request->removeHeader('Content-Type');
            }
            if (!$this->request->hasHeader('Content-Type')) {
                $type = $this->request?->getRequestType();
                if (!empty($type) && (strrpos($type, 'json') !== false)) {
                    $this->request->addHeader('Content-Type', $type);
                } else {
                    $this->request->addHeader('Content-Type', Request::JSON);
                }
            }
        }

        // Only encode if the data isn't already encoded
        if (!((is_string($jsonContent) && (json_decode($jsonContent) !== false)) && (json_last_error() == JSON_ERROR_NONE))) {
            $this->dataContent = json_encode($jsonContent, JSON_PRETTY_PRINT);
        } else {
            $this->dataContent = $jsonContent;
        }

        return $this;
    }

    /**
     * Method to prepare XML data content
     *
     * @return Data
     */
    public function prepareXml(): Data
    {
        if ($this->hasRawData()) {
            $xmlContent = $this->getRawData();
        } else {
            $xmlData    = $this->data;
            $xmlContent = '';

            // Check for XML files
            foreach ($xmlData as $xmlDatum) {
                $xmlContent .= (isset($xmlDatum['filename']) && isset($xmlDatum['contentType']) &&
                    str_contains(strtolower($xmlDatum['contentType']), 'xml') && file_exists($xmlDatum['filename'])) ?
                    file_get_contents($xmlDatum['filename']) : $xmlDatum;
            }
        }

        if ($this->hasRequest()) {
            if ($this->request->hasHeader('Content-Type') && !str_contains(strtolower((string)$this->request->getHeader('Content-Type')), 'xml')) {
                $this->request->removeHeader('Content-Type');
            }
            if (!$this->request->hasHeader('Content-Type')) {
                $type = $this->request?->getRequestType();
                if (!empty($type) && (strrpos($type, 'xml') !== false)) {
                    $this->request->addHeader('Content-Type', $type);
                } else {
                    $this->request->addHeader('Content-Type', Request::XML);
                }
            }
        }

        $this->dataContent = $xmlContent;

        return $this;
    }

    /**
     * Method to prepare multi-part data content
     *
     * @return Data
     */
    public function prepareMultipart(): Data
    {
        $formMessage       = Message::createForm($this->data);
        $this->dataContent = $formMessage->renderRaw();

        if ($this->hasRequest()) {
            if ($this->request->hasHeader('Content-Type')) {
                $this->request->removeHeader('Content-Type');
            }
            $this->request->addHeader($formMessage->getHeader('Content-Type'));
        }

        return $this;
    }

    /**
     * Get raw data
     *
     * @return ?string
     */
    public function getRawData(): ?string
    {
        return $this->data[self::POP_CLIENT_REQUEST_RAW_DATA] ?? null;
    }

    /**
     * Has raw data
     *
     * @return bool
     */
    public function hasRawData(): bool
    {
        return (count($this->data) == 1) && isset($this->data[self::POP_CLIENT_REQUEST_RAW_DATA]);
    }

    /**
     * Get raw data length
     *
     * @param  bool $mb
     * @return int
     */
    public function getRawDataLength(bool $mb = false): int
    {
        return ($mb) ? mb_strlen((string)$this->getRawData()) : strlen((string)$this->getRawData());
    }

    /**
     * Get data array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get common mime types
     *
     * @return array
     */
    public static function getMimeTypes(): array
    {
        return static::$mimeTypes;
    }

    /**
     * Has mime type
     *
     * @param  string $ext
     * @return bool
     */
    public static function hasMimeType(string $ext): bool
    {
        return isset(static::$mimeTypes[$ext]);
    }

    /**
     * Get mime type
     *
     * @param  string $ext
     * @return ?string
     */
    public static function getMimeType(string $ext): ?string
    {
        return static::$mimeTypes[$ext] ?? null;
    }

    /**
     * Get mime type
     *
     * @param  string $filename
     * @return string
     */
    public static function getMimeTypeFromFilename(string $filename): string
    {
        $info = pathinfo($filename);

        return (isset($info['extension']) && isset(self::$mimeTypes[$info['extension']])) ?
            self::$mimeTypes[$info['extension']] : self::$defaultMimeType;
    }

    /**
     * Get default mime type
     *
     * @return string
     */
    public static function getDefaultMimeType(): string
    {
        return static::$defaultMimeType;
    }

}
