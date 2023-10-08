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
     * Fields (form fields and files)
     *    $fields = [
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
    protected array $fields = [];

    /**
     * Request query
     * @var ?string
     */
    protected ?string $query = null;

    /**
     * Request form type
     * @var ?string
     */
    protected ?string $formType = null;

    /**
     * Set a field
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Request
     */
    public function setField(string $name, string $value): Request
    {
        $this->fields[$name] = $value;
        $this->prepareQuery();

        return $this;
    }

    /**
     * Set all fields
     *
     * @param  array $fields
     * @return Request
     */
    public function setFields(array $fields): Request
    {
        foreach ($fields as $name => $value) {
            $this->setField($name, $value);
        }

        $this->prepareQuery();

        return $this;
    }

    /**
     * Get a field
     *
     * @param  string $name
     * @return mixed
     */
    public function getField(string $name): mixed
    {
        return (isset($this->fields[$name])) ? $this->fields[$name] : null;
    }

    /**
     * Get all field
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Has fields
     *
     * @return bool
     */
    public function hasFields(): bool
    {
        return (!empty($this->fields));
    }

    /**
     * Has field
     *
     * @param  string $name
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return (isset($this->fields[$name]));
    }

    /**
     * Remove a field
     *
     * @param  string $name
     * @return Request
     */
    public function removeField(string $name): Request
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        $this->prepareQuery();

        return $this;
    }

    /**
     * Remove all fields
     *
     * @return Request
     */
    public function removeFields(): Request
    {
        $this->fields = [];
        $this->query  = null;

        return $this;
    }

    /**
     * Prepare the HTTP query
     *
     * @return string
     */
    public function prepareQuery(): string
    {
        if (($this->hasFilters()) && !empty($this->fields)) {
            $this->fields = $this->filter($this->fields);
        }
        $this->query = http_build_query($this->fields);
        return $this->query;
    }

    /**
     * Is HTTP query prepared
     *
     * @return bool
     */
    public function hasQuery(): bool
    {
        return ($this->query !== null);
    }

    /**
     * Get HTTP query
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get HTTP query length
     *
     * @param  bool $mb
     * @return int
     */
    public function getQueryLength(bool $mb = false): int
    {
        return ($mb) ? mb_strlen($this->query) : strlen($this->query);
    }

    /**
     * Create request as JSON
     *
     * @return Request
     */
    public function createAsJson(): Request
    {
        $this->formType = 'application/json';

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        $this->addHeader('Content-Type', $this->formType);

        return $this;
    }

    /**
     * Check if request is JSON
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return ($this->formType == 'application/json');
    }

    /**
     * Create request as XML
     *
     * @return Request
     */
    public function createAsXml(): Request
    {
        $this->formType = 'application/xml';

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        $this->addHeader('Content-Type', $this->formType);

        return $this;
    }

    /**
     * Check if request is XML
     *
     * @return bool
     */
    public function isXml()
    {
        return ($this->formType == 'application/xml');
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return Request
     */
    public function createUrlEncodedForm(): Request
    {
        $this->formType = 'application/x-www-form-urlencoded';

        if ($this->hasHeader('Content-Type')) {
            $this->removeHeader('Content-Type');
        }
        $this->addHeader('Content-Type', $this->formType);

        return $this;
    }

    /**
     * Check if request is a URL-encoded form
     *
     * @return bool
     */
    public function isUrlEncodedForm(): bool
    {
        return ($this->formType == 'application/x-www-form-urlencoded');
    }

    /**
     * Create request as a multipart form
     *
     * @return Request
     */
    public function createMultipartForm(): Request
    {
        $this->formType = 'multipart/form-data';
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return bool
     */
    public function isMultipartForm(): bool
    {
        return ($this->formType == 'multipart/form-data');
    }

    /**
     * Get form type
     *
     * @return string
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

}