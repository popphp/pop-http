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

use Pop\Mime\Part;

/**
 * HTTP client request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.5.0
 */
class Request extends AbstractClientObject
{

    /**
     * Fields
     * @var array
     */
    protected $fields = [];

    /**
     * Files
     * @var array
     */
    protected $files = [];

    /**
     * Request query
     * @var string
     */
    protected $query = null;

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
     * Set a file
     *
     * @param  string $name
     * @param  string $path
     * @return Request
     */
    public function setFile($name, $path)
    {
        $this->files[$name] = $path;
        return $this;
    }

    /**
     * Set all files
     *
     * @param  array $files
     * @return Request
     */
    public function setFiles(array $files)
    {
        foreach ($files as $name => $path) {
            $this->setFile($name, $path);
        }

        return $this;
    }

    /**
     * Get a file
     *
     * @param  string $name
     * @return mixed
     */
    public function getFile($name)
    {
        return (isset($this->files[$name])) ? $this->files[$name] : null;
    }

    /**
     * Get all files
     *
     * @return array
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Has files
     *
     * @return boolean
     */
    public function hasFiles()
    {
        return (!empty($this->files));
    }

    /**
     * Remove a file
     *
     * @param  string $name
     * @return Request
     */
    public function removeFile($name)
    {
        if (isset($this->files[$name])) {
            unset($this->files[$name]);
        }

        return $this;
    }

    /**
     * Prepare the HTTP query
     *
     * @return string
     */
    public function prepareQuery()
    {
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
        if ((null === $this->query) && ($this->hasFields())) {
            $this->prepareQuery();
        }
        return $this->query;
    }

    /**
     * Get HTTP query length
     *
     * @param  boolean $mb
     * @return int
     */
    public function getQueryLength($mb = true)
    {
        return ($mb) ? mb_strlen($this->query) : strlen($this->query);
    }


    /**
     * Create request as a URL-encoded form
     *
     * @return Request|AbstractClientObject
     */
    public function createUrlEncodedForm()
    {
        return $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return boolean
     */
    public function isUrlEncodedForm()
    {
        return (($this->hasHeader('Content-Type')) &&
            (strpos($this->getHeader('Content-Type')->getValue(), 'application/x-www-form-urlencoded') !== false));
    }

    /**
     * Create request as a multipart form
     *
     * @param  string $boundary
     * @return Request|AbstractClientObject
     */
    public function createMultipartForm($boundary = null)
    {
        if (null === $boundary) {
            $boundary = (new Part())->generateBoundary();
        }
        return $this->addHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
    }

    /**
     * Create request as a URL-encoded form
     *
     * @return boolean
     */
    public function isMultipartForm()
    {
        return (($this->hasHeader('Content-Type')) &&
            (strpos($this->getHeader('Content-Type')->getValue(), 'multipart/form-data') !== false));
    }

    /**
     * Get boundary
     *
     * @return string
     */
    public function getBoundary()
    {
        return (($this->hasHeader('Content-Type')) &&
            ($this->getHeader('Content-Type')->hasParameter('boundary'))) ?
            $this->getHeader('Content-Type')->getParameter('boundary') : null;
    }

}