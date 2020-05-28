<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client;

use Pop\Http\AbstractRequest;
use Pop\Mime\Part;

/**
 * HTTP client request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
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
    protected $fields = [];

    /**
     * Request query
     * @var string
     */
    protected $query = null;

    /**
     * Request form type
     * @var string
     */
    protected $formType = null;

    /**
     * Set a field
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Request
     */
    public function setField($name, $value)
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
    public function setFields(array $fields)
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
    public function getField($name)
    {
        return (isset($this->fields[$name])) ? $this->fields[$name] : null;
    }

    /**
     * Get all field
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Has fields
     *
     * @return boolean
     */
    public function hasFields()
    {
        return (!empty($this->fields));
    }

    /**
     * Has field
     *
     * @param  string $name
     * @return boolean
     */
    public function hasField($name)
    {
        return (isset($this->fields[$name]));
    }

    /**
     * Remove a field
     *
     * @param  string $name
     * @return Request
     */
    public function removeField($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        $this->prepareQuery();

        return $this;
    }

    /**
     * Prepare the HTTP query
     *
     * @return string
     */
    public function prepareQuery()
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
     * @return string
     */
    public function hasQuery()
    {
        return (null !== $this->query);
    }

    /**
     * Get HTTP query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get HTTP query length
     *
     * @param  boolean $mb
     * @return int
     */
    public function getQueryLength($mb = false)
    {
        return ($mb) ? mb_strlen($this->query) : strlen($this->query);
    }

    /**
     * Create request as JSON
     *
     * @return Request
     */
    public function createAsJson()
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
     * @return boolean
     */
    public function isJson()
    {
        return ($this->formType == 'application/json');
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return Request
     */
    public function createUrlEncodedForm()
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
     * @return boolean
     */
    public function isUrlEncodedForm()
    {
        return ($this->formType == 'application/x-www-form-urlencoded');
    }

    /**
     * Create request as a multipart form
     *
     * @return Request
     */
    public function createMultipartForm()
    {
        $this->formType = 'multipart/form-data';
        return $this;
    }

    /**
     * Check if request is a multipart form
     *
     * @return boolean
     */
    public function isMultipartForm()
    {
        return ($this->formType == 'multipart/form-data');
    }

    /**
     * Get form type
     *
     * @return string
     */
    public function getFormType()
    {
        return $this->formType;
    }

}