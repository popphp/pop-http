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
 * Curl class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
class Curl extends AbstractClient
{

    /**
     * cURL options
     * @var array
     */
    protected $options = [];

    /**
     * Constructor
     *
     * Instantiate the cURL object
     *
     * @param  string $url
     * @param  string $method
     * @param  array  $opts
     * @throws Exception
     */
    public function __construct($url, $method = 'GET', array $opts = null)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Error: cURL is not available.');
        }
        $this->resource = curl_init();

        $this->setUrl($url);
        $this->setMethod($method);
        $this->setOption(CURLOPT_URL, $this->url);
        $this->setOption(CURLOPT_HEADER, true);
        $this->setOption(CURLOPT_RETURNTRANSFER, true);

        if (null !== $opts) {
            $this->setOptions($opts);
        }
    }

    /**
     * Set the method
     *
     * @param  string $method
     * @throws Exception
     * @return Curl
     */
    public function setMethod($method)
    {
        parent::setMethod($method);

        if ($this->method != 'GET') {
            switch ($this->method) {
                case 'POST':
                    $this->setOption(CURLOPT_POST, true);
                    break;
                default:
                    $this->setOption(CURLOPT_CUSTOMREQUEST, $this->method);
            }
        }


        return $this;
    }

    /**
     * Return cURL resource (alias to $this->getResource())
     *
     * @return resource
     */
    public function curl()
    {
        return $this->resource;
    }

    /**
     * Create and open cURL resource
     *
     * @return Curl
     */
    public function open()
    {
        $url     = $this->url;
        $headers = [];

        // Set query data if there is any
        if (count($this->fields) > 0) {
            if ($this->method == 'GET') {
                $url = $this->options[CURLOPT_URL] . '?' . $this->getQuery();
                $this->setOption(CURLOPT_URL, $url);
            } else {
                if (isset($this->requestHeaders['Content-Type']) && ($this->requestHeaders['Content-Type'] != 'multipart/form-data')) {
                    $this->setOption(CURLOPT_POSTFIELDS, $this->getQuery());
                    $this->setRequestHeader('Content-Length', $this->getQueryLength());
                } else {
                    $this->setOption(CURLOPT_POSTFIELDS, $this->fields);
                }
                $this->setOption(CURLOPT_POSTFIELDS, $this->fields);
                $this->setOption(CURLOPT_URL, $url);
            }
        }

        if ($this->hasRequestHeaders()) {
            foreach ($this->requestHeaders as $header => $value) {
                if (is_array($value)) {
                    foreach ($value as $hdr => $val) {
                        $headers[] = $hdr . ': ' . $val;
                    }
                } else {
                    $headers[] = $header . ': ' . $value;
                }
            }
            $this->setOption(CURLOPT_HTTPHEADER, $headers);
        }

        $this->response = curl_exec($this->resource);

        if ($this->response === false) {
            $this->throwError('Error: ' . curl_errno($this->resource) . ' => ' . curl_error($this->resource) . '.');
        }

        return $this;
    }

    /**
     * Set cURL session option
     *
     * @param  int   $opt
     * @param  mixed $val
     * @return Curl
     */
    public function setOption($opt, $val)
    {
        // Set the protected property to keep track of the cURL options.
        $this->options[$opt] = $val;
        curl_setopt($this->resource, $opt, $val);

        return $this;
    }

    /**
     * Set cURL session options
     *
     * @param  array $opts
     * @return Curl
     */
    public function setOptions($opts)
    {
        // Set the protected property to keep track of the cURL options.
        foreach ($opts as $k => $v) {
            $this->options[$k] = $v;
        }

        curl_setopt_array($this->resource, $opts);

        return $this;
    }

    /**
     * Set cURL option to return the header
     *
     * @param  boolean $header
     * @return Curl
     */
    public function setReturnHeader($header = true)
    {
        $this->setOption(CURLOPT_HEADER, (bool)$header);
        return $this;
    }

    /**
     * Set cURL option to return the transfer
     *
     * @param  boolean $transfer
     * @return Curl
     */
    public function setReturnTransfer($transfer = true)
    {
        $this->setOption(CURLOPT_RETURNTRANSFER, (bool)$transfer);
        return $this;
    }

    /**
     * Check if cURL is set to return header
     *
     * @return boolean
     */
    public function isReturnHeader()
    {
        return (isset($this->options[CURLOPT_HEADER]) && ($this->options[CURLOPT_HEADER] == true));
    }

    /**
     * Check if cURL is set to return transfer
     *
     * @return boolean
     */
    public function isReturnTransfer()
    {
        return (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER] == true));
    }

    /**
     * Get a cURL session option
     *
     * @param  int $opt
     * @return string
     */
    public function getOption($opt)
    {
        return (isset($this->options[$opt])) ? $this->options[$opt] : null;
    }

    /**
     * Return the cURL session last info
     *
     * @param  int $opt
     * @return array|string
     */
    public function getInfo($opt = null)
    {
        return (null !== $opt) ? curl_getinfo($this->resource, $opt) : curl_getinfo($this->resource);
    }

    /**
     * Method to send the request and get the response
     *
     * @return void
     */
    public function send()
    {
        $this->open();

        if ($this->response === false) {
            $this->throwError('Error: ' . curl_errno($this->resource) . ' => ' . curl_error($this->resource) . '.');
        }

        // If the CURLOPT_RETURNTRANSFER option is set, get the response body and parse the headers.
        if (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER] == true)) {
            $headerSize = $this->getInfo(CURLINFO_HEADER_SIZE);
            if ($this->options[CURLOPT_HEADER]) {
                $this->responseHeader = substr($this->response, 0, $headerSize);
                $this->body   = substr($this->response, $headerSize);
                $this->parseResponseHeaders();
            } else {
                $this->body = $this->response;
            }
        }

        if (array_key_exists('Content-Encoding', $this->responseHeaders)) {
            $this->decodeBody();
        }
    }

    /**
     * Return the cURL version
     *
     * @return array
     */
    public function version()
    {
        return curl_version();
    }

    /**
     * Close the cURL connection
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->hasResource()) {
            curl_close($this->resource);
        }
    }

    /**
     * Parse headers
     *
     * @return void
     */
    protected function parseResponseHeaders()
    {
        if (null !== $this->responseHeader) {
            $headers = explode("\n", $this->responseHeader);
            foreach ($headers as $header) {
                if (strpos($header, 'HTTP') !== false) {
                    $this->version = substr($header, 0, strpos($header, ' '));
                    $this->version = substr($this->version, (strpos($this->version, '/') + 1));
                    preg_match('/\d\d\d/', trim($header), $match);
                    $this->code    = $match[0];
                    $this->message = trim(str_replace('HTTP/' . $this->version . ' ' . $this->code . ' ', '', $header));
                } else if (strpos($header, ':') !== false) {
                    $name  = trim(substr($header, 0, strpos($header, ':')));
                    $value = trim(substr($header, strpos($header, ':') + 1));
                    if (isset($this->responseHeaders[$name])) {
                        if (!is_array($this->responseHeaders[$name])) {
                            $this->responseHeaders[$name] = [$this->responseHeaders[$name]];
                        }
                        $this->responseHeaders[$name][] = $value;
                    } else {
                        $this->responseHeaders[$name] = $value;
                    }
                }
            }
        }
    }

}
