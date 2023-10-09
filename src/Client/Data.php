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
     * @param  array $data
     * @return Data
     */
    public function setData(array $data): Data
    {
        $this->data = $data;
        $this->prepareQueryString();

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
     * @return array
     */
    public function getData(): array
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
     * @return string|null
     */
    public function prepareQueryString(): string|null
    {
        if (!empty($this->data)) {
            if ($this->hasFilters()) {
                $this->data = $this->filter($this->data);
            }
            $this->queryString = http_build_query($this->data);
        } else {
            $this->queryString = null;
        }

        return $this->queryString;
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
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
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

}