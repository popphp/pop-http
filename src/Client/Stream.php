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

use Pop\Http\Parser;
use Pop\Mime\Message;

/**
 * HTTP stream client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
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
     * Stream mode
     * @var string
     */
    protected $mode = 'r';

    /**
     * HTTP response headers
     * @var array
     */
    protected $httpResponseHeaders = null;

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
    public function __construct($url = null, $method = 'GET', $mode = 'r', array $options = [], array $params = [])
    {
        parent::__construct($url, $method);

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
     * @param  string  $method
     * @param  boolean $strict
     * @throws Exception
     * @return Stream
     */
    public function setMethod($method, $strict = true)
    {
        parent::setMethod($method, $strict);

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
     * Create and open stream resource
     *
     * @return Stream
     */
    public function open()
    {
        $url                  = $this->url;
        $http_response_header = null;

        if (isset($this->contextOptions['http']['header'])) {
            $this->contextOptions['http']['header'] = null;
        }

        if (null !== $this->request) {
            // Set query data if there is any
            if ($this->request->hasFields()) {
                // Append GET query string to URL
                if (($this->method == 'GET') && ((!$this->request->hasHeader('Content-Type')) ||
                        ($this->request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded'))) {
                    $url .= '?' . $this->request->getQuery();
                // Else, prepare request data for transmission
                } else {
                    // If request is JSON
                    if ($this->request->getHeaderValue('Content-Type') == 'application/json') {
                        $content = json_encode($this->request->getFields(), JSON_PRETTY_PRINT);
                        $this->request->addHeader('Content-Length', strlen($content));
                        $this->contextOptions['http']['content'] = $content;
                    // If request is a URL-encoded form
                    } else if ($this->request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded') {
                        $this->request->addHeader('Content-Length', $this->request->getQueryLength());
                        $this->contextOptions['http']['content'] = $this->request->getQuery();
                    // Else, if request is a multipart form
                    } else if ($this->request->isMultipartForm()) {
                        $formMessage = Message::createForm($this->request->getFields());
                        $header      = $formMessage->getHeader('Content-Type');
                        $content     = $formMessage->render(false);
                        $formMessage->removeHeader('Content-Type');
                        $this->request->addHeader($header)
                            ->addHeader('Content-Length', strlen($content));
                        $this->contextOptions['http']['content'] = $content;
                    // Else, basic request with data
                    } else {
                        $this->contextOptions['http']['content'] = $this->request->getQuery();
                    }
                }
            // Else, if there is raw body content
            } else if ($this->request->hasBody()) {
                $this->request->addHeader('Content-Length', strlen($this->request->getBodyContent()));
                $this->contextOptions['http']['content'] = $this->request->getBodyContent();
            }

            if ($this->request->hasHeaders()) {
                $headers = [];

                foreach ($this->request->getHeaders() as $header => $value) {
                    if (is_array($value)) {
                        foreach ($value as $hdr => $val) {
                            $headers[] = (string)$val;
                        }
                    } else {
                        $headers[] = (string)$value;
                    }
                }

                if (isset($this->contextOptions['http']['header'])) {
                    $this->contextOptions['http']['header'] .= "\r\n" . implode("\r\n", $headers) . "\r\n";
                } else {
                    $this->contextOptions['http']['header'] = implode("\r\n", $headers) . "\r\n";
                }
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
     * Method to send the request and get the response
     *
     * @return void
     */
    public function send()
    {
        $rawHeader = null;
        $headers   = [];
        $body      = null;

        $this->open();

        if (null === $this->response) {
            $this->response = new Response();
        }

        if ($this->resource !== false) {
            $meta      = stream_get_meta_data($this->resource);
            $headers   = $meta['wrapper_data'];
            $body      = stream_get_contents($this->resource);
        } else if (null !== $this->httpResponseHeaders) {
            $headers   = $this->httpResponseHeaders;
        }

        // Parse response headers
        $parsedHeaders = Parser::parseHeaders($headers);
        $this->response->setVersion($parsedHeaders['version']);
        $this->response->setCode($parsedHeaders['code']);
        $this->response->setMessage($parsedHeaders['message']);
        $this->response->addHeaders($parsedHeaders['headers']);
        $this->response->setBody($body);

        if ($this->response->hasHeader('Content-Encoding')) {
            $this->response->decodeBodyContent();
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
