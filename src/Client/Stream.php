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
 * HTTP response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
class Stream extends AbstractClient
{

    /**
     * Stream context
     * @var resource
     */
    protected $context = null;

    /**
     * Stream context options
     * @var array
     */
    protected $contextOptions = [];

    /**
     * Stream context parameters
     * @var array
     */
    protected $contextParams = [];

    /**
     * HTTP Response Headers
     * @var string
     */
    protected $httpResponseHeaders = null;

    /**
     * Stream mode
     * @var string
     */
    protected $mode = 'r';

    /**
     * Constructor
     *
     * Instantiate the stream object
     *
     * @param  string $url
     * @param  string $method
     * @param  string $mode
     * @param  array  $options
     * @param  array  $params
     */
    public function __construct($url, $method = 'GET', $mode = 'r', array $options = [], array $params = [])
    {
        $this->setUrl($url);
        $this->setMethod($method);
        $this->setMode($mode);
        if (count($options) > 0) {
            $this->setContextOptions($options);
        }
        if (count($params) > 0) {
            $this->setContextParams($params);
        }
    }

    /**
     * Set the method
     *
     * @param  string $method
     * @throws Exception
     * @return Stream
     */
    public function setMethod($method)
    {
        parent::setMethod($method);

        if (!isset($this->contextOptions['http'])) {
            $this->contextOptions['http'] = [];
        }

        $this->contextOptions['http']['method'] = $this->method;

        return $this;
    }

    /**
     * Return stream resource (alias to $this->getResource())
     *
     * @return resource
     */
    public function stream()
    {
        return $this->resource;
    }

    /**
     * Create stream context
     *
     * @return Stream
     */
    public function createContext()
    {
        if ((count($this->contextOptions) > 0) && (count($this->contextParams) > 0)) {
            $this->context = stream_context_create($this->contextOptions, $this->contextParams);
        } else if (count($this->contextOptions) > 0) {
            $this->context = stream_context_create($this->contextOptions);
        } else {
            $this->context = stream_context_create();
        }

        return $this;
    }

    /**
     * Create and open stream resource
     *
     * @return Stream
     */
    public function open()
    {
        $http_response_header = null;

        $url     = $this->url;
        $headers = [];

        if (isset($this->contextOptions['http']['header'])) {
            $this->contextOptions['http']['header'] = null;
        }

        // Set query data if there is any
        if (count($this->fields) > 0) {
            if ($this->method == 'GET') {
                $url .= '?' . $this->getQuery();
            } else {
                $this->contextOptions['http']['content'] = $this->getQuery();
                $headers[] = 'Content-Length: ' . $this->getQueryLength();
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

            if (isset($this->contextOptions['http']['header'])) {
                $this->contextOptions['http']['header'] .= "\r\n" . implode("\r\n", $headers) . "\r\n";
            } else {
                $this->contextOptions['http']['header'] = implode("\r\n", $headers) . "\r\n";
            }
        }

        if ((count($this->contextOptions) > 0) || (count($this->contextParams) > 0)) {
            $this->createContext();
        }

        $this->resource = (null !== $this->context) ?
            @fopen($url, $this->mode, false, $this->context) : @fopen($url, $this->mode);

        $this->httpResponseHeaders = $http_response_header;

        return $this;
    }

    /**
     * Add a context options
     *
     * @param  string $name
     * @param  mixed  $option
     * @return Stream
     */
    public function addContextOption($name, $option)
    {
        if (isset($this->contextOptions[$name]) && is_array($this->contextOptions[$name]) && is_array($option)) {
            $this->contextOptions[$name] = array_merge($this->contextOptions[$name], $option);
        } else {
            $this->contextOptions[$name] = $option;
        }

        return $this;
    }

    /**
     * Add a context parameter
     *
     * @param  string $name
     * @param  mixed  $param
     * @return Stream
     */
    public function addContextParam($name, $param)
    {
        $this->contextParams[$name] = $param;
        return $this;
    }

    /**
     * Set the context options
     *
     * @param  array $options
     * @return Stream
     */
    public function setContextOptions(array $options)
    {
        foreach ($options as $name => $option) {
            $this->addContextOption($name, $option);
        }
        return $this;
    }

    /**
     * Set the context parameters
     *
     * @param  array $params
     * @return Stream
     */
    public function setContextParams(array $params)
    {
        foreach ($params as $name => $param) {
            $this->addContextParam($name, $param);
        }
        return $this;
    }

    /**
     * Set the mode
     *
     * @param  string $mode
     * @return Stream
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get the context resource
     *
     * @return resource
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Get the context options
     *
     * @return array
     */
    public function getContextOptions()
    {
        return $this->contextOptions;
    }

    /**
     * Get a context option
     *
     * @param  string $name
     * @return mixed
     */
    public function getContextOption($name)
    {
        return (isset($this->contextOptions[$name])) ? $this->contextOptions[$name] : null;
    }

    /**
     * Determine if a context option has been set
     *
     * @param  string $name
     * @return boolean
     */
    public function hasContextOption($name)
    {
        return (isset($this->contextOptions[$name]));
    }

    /**
     * Get the context parameters
     *
     * @return array
     */
    public function getContextParams()
    {
        return $this->contextParams;
    }

    /**
     * Get a context parameter
     *
     * @param  string $name
     * @return mixed
     */
    public function getContextParam($name)
    {
        return (isset($this->contextParams[$name])) ? $this->contextParams[$name] : null;
    }

    /**
     * Determine if a context parameter has been set
     *
     * @param  string $name
     * @return boolean
     */
    public function hasContextParam($name)
    {
        return (isset($this->contextParams[$name]));
    }

    /**
     * Get the mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Method to send the request and get the response
     *
     * @return void
     */
    public function send()
    {
        $headers    = [];
        $rawHeader  = null;
        $bodyString = null;

        $this->open();

        if ($this->resource != false) {
            $meta      = stream_get_meta_data($this->resource);
            $rawHeader = implode("\r\n", $meta['wrapper_data']) . "\r\n\r\n";
            $body      = stream_get_contents($this->resource);

            $firstLine     = $meta['wrapper_data'][0];
            unset($meta['wrapper_data'][0]);
            $allHeadersAry = $meta['wrapper_data'];
            $bodyString    = $body;
        } else if (null !== $this->httpResponseHeaders) {
            $rawHeader = implode("\r\n", $this->httpResponseHeaders) . "\r\n\r\n";
            $firstLine = $this->httpResponseHeaders[0];
            unset($this->httpResponseHeaders[0]);
            $allHeadersAry = $this->httpResponseHeaders;
        }

        // Get the version, code and message
        if (null !== $rawHeader) {
            $version = substr($firstLine, 0, strpos($firstLine, ' '));
            $version = substr($version, (strpos($version, '/') + 1));
            preg_match('/\d\d\d/', trim($firstLine), $match);
            $code    = $match[0];
            $message = str_replace('HTTP/' . $version . ' ' . $code . ' ', '', $firstLine);

            // Get the headers
            foreach ($allHeadersAry as $hdr) {
                $name  = trim(substr($hdr, 0, strpos($hdr, ':')));
                $value = trim(substr($hdr, (strpos($hdr, ' ') + 1)));
                if (isset($headers[$name])) {
                    if (!is_array($headers[$name])) {
                        $headers[$name] = [$headers[$name]];
                    }
                    $headers[$name][] = $value;
                } else {
                    $headers[$name] = $value;
                }
            }

            $this->code            = $code;
            $this->responseHeader  = $rawHeader;
            $this->responseHeaders = $headers;
            $this->body            = $bodyString;
            $this->response        = $rawHeader . $bodyString;
            $this->message         = $message;
            $this->version         = $version;

            if (array_key_exists('Content-Encoding', $this->responseHeaders)) {
                $this->decodeBody();
            }
        }
    }

    /**
     * Close the stream
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->hasResource()) {
            $this->resource = null;
            $this->context  = null;
        }
    }

}
