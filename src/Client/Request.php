<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client;

/**
 * HTTP client request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
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
     * Request boundary
     * @var string
     */
    protected $boundary = null;

    /**
     * Multipart request body
     * @var string
     */
    protected $multipartBody = null;

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
     * Set as URL-encoded form
     *
     * @return Request|AbstractClientObject
     */
    public function setAsUrlEncodedForm()
    {
        return $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    /**
     * Set as multipart form
     *
     * @param  string $boundary
     * @return Request|AbstractClientObject
     */
    public function setMultipartForm($boundary = null)
    {
        return $this->setHeader('Content-Type', 'multipart/form-data; boundary=' . $boundary);
    }

    /**
     * Is URL-encoded form
     *
     * @return boolean
     */
    public function isUrlEncodedForm()
    {
        return (isset($this->headers['Content-Type']) &&
            ($this->headers['Content-Type'] != 'application/x-www-form-urlencoded'));
    }

    /**
     * Is multipart form
     *
     * @return boolean
     */
    public function isMultipartForm()
    {
        return (isset($this->headers['Content-Type']) &&
            ($this->headers['Content-Type'] != 'multipart/form-data'));
    }

    /**
     * Create multipart body
     *
     * @param  string $boundary
     * @return string
     */
    public function createMultipartBody($boundary = null)
    {
        $this->multipartBody = '';

        if (null !== $boundary) {
            $this->setBoundary($boundary);
        } else if ($this->hasBoundary()) {
            $boundary = $this->getBoundary();
        } else {
            $boundary = $this->generateBoundary();
        }

        foreach ($this->fields as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $this->multipartBody .= '--' . $boundary . "\r\n" .
                        'Content-Disposition: form-data; name="' . $name . '[]"' . "\r\n\r\n" .
                        rawurlencode($val) . "\r\n";
                }
            } else {
                $this->multipartBody .= '--' . $boundary . "\r\n" .
                    'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n" .
                    rawurlencode($value) . "\r\n";
            }
        }

        if (!empty($this->multipartBody)) {
            $this->multipartBody .= '--' . $boundary;
        }

        return $this->multipartBody;
    }

    /**
     * Has multipart body
     *
     * @return boolean
     */
    public function hasMultipartBody()
    {
        return (null !== $this->multipartBody);
    }

    /**
     * Get multipart body
     *
     * @return string
     */
    public function getMultipartBody()
    {
        return $this->multipartBody;
    }

    /**
     * Get multipart body length
     *
     * @param  boolean $mb
     * @return int
     */
    public function getMultipartBodyLength($mb = true)
    {
        return ($mb) ? mb_strlen($this->multipartBody) : strlen($this->multipartBody);
    }

    /**
     * Set boundary
     *
     * @param  string
     * @return Request
     */
    public function setBoundary($boundary)
    {
        $this->boundary = $boundary;
        return $this;
    }

    /**
     * Get boundary
     *
     * @return string
     */
    public function getBoundary()
    {
        return $this->boundary;
    }

    /**
     * Has boundary
     *
     * @return string
     */
    public function hasBoundary()
    {
        return (null !== $this->boundary);
    }

    /**
     * Generate boundary
     *
     * @return string
     */
    public function generateBoundary()
    {
        $this->setBoundary(sha1(uniqid()));
        return $this->boundary;
    }

}