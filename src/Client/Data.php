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

use Pop\Http\HttpFilterableTrait;
use Pop\Mime\Message;
use Pop\Mime\Part;

/**
 * Client request data class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
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
     * Data type
     * @var ?string
     */
    protected ?string $type = null;

    /**
     * Data content-type header
     * @var ?Part\Header
     */
    protected ?Part\Header $contentTypeHeader = null;

    /**
     * Data content-length header
     * @var ?Part\Header
     */
    protected ?Part\Header $contentLengthHeader = null;

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
    public function __construct(array|string $data = [], mixed $filters = null, ?string $type = Request::URLENCODED)
    {
        if ($filters !== null) {
            if (is_array($filters)) {
                $this->addFilters($filters);
            } else {
                $this->addFilter($filters);
            }
        }

        if ($type !== null) {
            $this->setType($type);
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
     * Set data type
     *
     * @param  string $type
     * @return Data
     */
    public function setType(string $type): Data
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get data type
     *
     * @return ?string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Has data type
     *
     * @return bool
     */
    public function hasType(): bool
    {
        return !empty($this->type);
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
     * Get data content-type header
     *
     * @return ?Part\Header
     */
    public function getContentTypeHeader(): ?Part\Header
    {
        return $this->contentTypeHeader;
    }

    /**
     * Has data content-type header
     *
     * @return bool
     */
    public function hasContentTypeHeader(): bool
    {
        return !empty($this->contentTypeHeader);
    }

    /**
     * Get data content-length header
     *
     * @return ?Part\Header
     */
    public function getContentLengthHeader(): ?Part\Header
    {
        return $this->contentLengthHeader;
    }

    /**
     * Has data content-length header
     *
     * @return bool
     */
    public function hasContentLengthHeader(): bool
    {
        return !empty($this->contentLengthHeader);
    }

    /**
     * Check if data is URL-encoded
     *
     * @return bool
     */
    public function isUrlEncoded(): bool
    {
        return ($this->type == Request::URLENCODED);
    }

    /**
     * Check if data is JSON
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return ($this->type == Request::JSON);
    }

    /**
     * Check if data is XML
     *
     * @return bool
     */
    public function isXml(): bool
    {
        return ($this->type == Request::XML);
    }

    /**
     * Check if data is multi-part
     * @return bool
     */
    public function isMultipart(): bool
    {
        return ($this->type == Request::MULTIPART);
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
     * @return Data
     */
    public function prepare(): Data
    {
        switch ($this->type) {
            case Request::URLENCODED:
                $this->prepareUrlEncoded();
                break;
            case Request::JSON:
                $this->prepareJson();
                break;
            case Request::XML:
                $this->prepareXml();
                break;
            case Request::MULTIPART:
                $this->prepareMultipart();
                break;
            default:
                if ($this->hasRawData()) {
                    $this->dataContent = $this->getRawData();
                } else if ($this->hasData()) {
                    $this->prepareUrlEncoded();
                }
        }

        if (!empty($this->dataContent)) {
            $this->contentLengthHeader = new Part\Header('Content-Length', strlen($this->dataContent));
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
        $this->dataContent         = null;
        $this->contentTypeHeader   = null;
        $this->contentLengthHeader = null;
        $this->prepared            = false;
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

        if ($this->hasType()) {
            $this->contentTypeHeader = new Part\Header('Content-Type', $this->type);
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

        if ($this->hasType()) {
            $this->contentTypeHeader = new Part\Header('Content-Type', $this->type);
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

        if ($this->hasType()) {
            $this->contentTypeHeader = new Part\Header('Content-Type', $this->type);
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
        $formMessage             = Message::createForm($this->data);
        $this->dataContent       = $formMessage->renderRaw();
        $this->contentTypeHeader = $formMessage->getHeader('Content-Type');

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
