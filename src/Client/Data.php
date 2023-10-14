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

/**
 * Client request data class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
     * Query string
     * @var ?string
     */
    protected ?string $queryString = null;

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
     * @param array $data
     */
    public function __construct(array $data = [], mixed $filters = null)
    {
        if ($filters !== null) {
            if (is_array($filters)) {
                $this->addFilters($filters);
            } else {
                $this->addFilter($filters);
            }
        }

        $this->setData($data);
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
            $this->prepareQueryString();
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

        $this->prepareQueryString();

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

        $this->prepareQueryString();

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
        $this->queryString = null;

        return $this;
    }

    /**
     * Prepare the query string
     *
     * @param  bool $withQuestionMark
     * @return string|null
     */
    public function prepareQueryString(bool $withQuestionMark = false): string|null
    {
        if (!empty($this->data) && !array_key_exists(self::POP_CLIENT_REQUEST_RAW_DATA, $this->data)) {
            if ($this->hasFilters()) {
                $this->data = $this->filter($this->data);
            }
            $this->queryString = http_build_query($this->data);
        } else {
            $this->queryString = null;
        }

        return ($withQuestionMark) ? '?' . $this->queryString : $this->queryString;
    }

    /**
     * Is query string prepared
     *
     * @return bool
     */
    public function hasQueryString(): bool
    {
        return ($this->queryString !== null);
    }

    /**
     * Get the query string
     *
     * @param  bool $withQuestionMark
     * @return string
     */
    public function getQueryString(bool $withQuestionMark = false): string
    {
        return $this->prepareQueryString($withQuestionMark);
    }

    /**
     * Get query string length
     *
     * @param  bool $mb
     * @return int
     */
    public function getQueryStringLength(bool $mb = false): int
    {
        return ($mb) ? mb_strlen($this->queryString) : strlen($this->queryString);
    }

    /**
     * Has raw data
     *
     * @return bool
     */
    public function hasRawData(): bool
    {
        return (isset($this->data[self::POP_CLIENT_REQUEST_RAW_DATA]));
    }

    /**
     * Get the raw data
     *
     * @return string|null
     */
    public function getRawData(): string|null
    {
        return $this->data[self::POP_CLIENT_REQUEST_RAW_DATA] ?? null;
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
     * @return string|null
     */
    public static function getMimeType(string $ext): string|null
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