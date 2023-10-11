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
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Parser;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Mime\Message;

/**
 * HTTP client stream handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Stream extends AbstractHandler
{

    /**
     * URI string
     * @var ?string
     */
    protected ?string $uri = null;

    /**
     * Stream context
     * @var mixed
     */
    protected mixed $context = null;

    /**
     * Stream context options
     * @var array
     */
    protected array $contextOptions = [];

    /**
     * Stream context parameters
     * @var array
     */
    protected array $contextParams = [];

    /**
     * Stream mode
     * @var string
     */
    protected string $mode = 'r';

    /**
     * HTTP response headers
     * @var ?array
     */
    protected ?array $httpResponseHeaders = null;

    /**
     * Constructor
     *
     * Instantiate the stream handler object
     *
     * @param string $mode
     * @param array $options
     * @param array $params
     */
    public function __construct(string $mode = 'r', array $options = [], array $params = [])
    {
        $this->setMode($mode);

        if (count($options) > 0) {
            $this->setContextOptions($options);
        }
        if (count($params) > 0) {
            $this->setContextParams($params);
        }
    }

    /**
     * Return stream resource (alias to $this->getResource())
     *
     * @return mixed
     */
    public function stream(): mixed
    {
        return $this->resource;
    }

    /**
     * Create stream context
     *
     * @return Stream
     */
    public function createContext(): Stream
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
    public function addContextOption(string $name, mixed $option): Stream
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
    public function addContextParam(string $name, mixed $param): Stream
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
    public function setContextOptions(array $options): Stream
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
    public function setContextParams(array $params): Stream
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
    public function setMode(string $mode): Stream
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Get the context resource
     *
     * @return mixed
     */
    public function getContext(): mixed
    {
        return $this->context;
    }

    /**
     * Get the context options
     *
     * @return array
     */
    public function getContextOptions(): array
    {
        return $this->contextOptions;
    }

    /**
     * Get a context option
     *
     * @param  string $name
     * @return mixed
     */
    public function getContextOption(string $name): mixed
    {
        return $this->contextOptions[$name] ?? null;
    }

    /**
     * Determine if a context option has been set
     *
     * @param  string $name
     * @return bool
     */
    public function hasContextOption(string $name): bool
    {
        return (isset($this->contextOptions[$name]));
    }

    /**
     * Get the context parameters
     *
     * @return array
     */
    public function getContextParams(): array
    {
        return $this->contextParams;
    }

    /**
     * Get a context parameter
     *
     * @param  string $name
     * @return mixed
     */
    public function getContextParam(string $name): mixed
    {
        return $this->contextParams[$name] ?? null;
    }

    /**
     * Determine if a context parameter has been set
     *
     * @param  string $name
     * @return bool
     */
    public function hasContextParam(string $name): bool
    {
        return (isset($this->contextParams[$name]));
    }

    /**
     * Get the mode
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Method to prepare the handler
     *
     * @param  Request $request
     * @param  ?Auth   $auth
     * @param  bool    $clear
     * @throws Exception|\Pop\Http\Exception
     * @return Stream
     */
    public function prepare(Request $request, ?Auth $auth = null, bool $clear = true): Stream
    {
        $this->uri = $request->getUriAsString();

        // Clear headers for a fresh request based on the headers in the request object,
        // else fall back to pre-defined headers in the stream context
        if (($clear) && isset($this->contextOptions['http']['header'])) {
            $this->contextOptions['http']['header'] = null;
        }
        // Add auth header
        if ($auth !== null) {
            $request->addHeader($auth->createAuthHeader());
        }

        // If request has data
        if ($request->hasData()) {
            // Append GET query string to URL
            if (($request->isGet()) && ((!$request->hasHeader('Content-Type')) ||
                    ($request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded'))) {
                $this->uri .= $request->getData()->prepareQueryString(true);
            // Else, prepare request data for transmission
            } else {
                // If request is JSON
                if ($request->getHeaderValue('Content-Type') == 'application/json') {
                    $content = json_encode($request->getData(true), JSON_PRETTY_PRINT);
                    $request->addHeader('Content-Length', strlen($content));
                    $this->contextOptions['http']['content'] = $content;
                // If request is a URL-encoded form
                } else if ($request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded') {
                    $request->addHeader('Content-Length', $request->getData()->getQueryStringLength());
                    $this->contextOptions['http']['content'] = $request->getData()->prepareQueryString();
                // Else, if request is a multipart form
                } else if ($request->isMultipartForm()) {
                    $formMessage = Message::createForm($request->getData(true));
                    $header      = $formMessage->getHeader('Content-Type');
                    $content     = $formMessage->render(false);
                    $formMessage->removeHeader('Content-Type');
                    $request->addHeader($header)
                        ->addHeader('Content-Length', strlen($content));
                    $this->contextOptions['http']['content'] = $content;
                // Else, basic request with data
                } else {
                    $this->contextOptions['http']['content'] = $request->getData(true);
                    if (!$request->hasHeader('Content-Type')) {
                        $request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
                    }
                }
            }
        // Else, if there is raw body content
        } else if ($request->hasBodyContent()) {
            $request->addHeader('Content-Length', strlen($request->getBodyContent()));
            $this->contextOptions['http']['content'] = $request->getBodyContent();
        }

        return $this;
    }

    /**
     * Method to send the request
     *
     * @throws Exception
     * @return Response
     */
    public function send(): Response
    {
        if ($this->uri === null) {
            throw new Exception('Error: The request handler has not been prepared.');
        }
        $http_response_header = null;

        $this->resource = ($this->context !== null) ?
            @fopen($this->uri, $this->mode, false, $this->context) : @fopen($this->uri, $this->mode);

        $this->uri = null;
        $this->httpResponseHeaders = $http_response_header;

        return $this->parseResponse();
    }

    /**
     * Parse the response
     *
     * @return Response
     */
    public function parseResponse(): Response
    {
        $response = new Response();
        $headers  = [];
        $body     = null;

        if ($this->resource !== false) {
            $meta    = stream_get_meta_data($this->resource);
            $headers = $meta['wrapper_data'];
            $body    = stream_get_contents($this->resource);
        } else if ($this->httpResponseHeaders !== null) {
            $headers = $this->httpResponseHeaders;
        }

        // Parse response headers
        $parsedHeaders = Parser::parseHeaders($headers);
        $response->setVersion($parsedHeaders['version']);
        $response->setCode($parsedHeaders['code']);
        $response->setMessage($parsedHeaders['message']);
        $response->addHeaders($parsedHeaders['headers']);
        if ($body !== null) {
            $response->setBody($body);
        }

        if ($response->hasHeader('Content-Encoding')) {
            $response->decodeBodyContent();
        }

        return $response;
    }

    /**
     * Method to reset the handler
     *
     * @return Stream
     */
    public function reset(): Stream
    {
        $this->context             = null;
        $this->contextOptions      = [];
        $this->contextParams       = [];
        $this->httpResponseHeaders = null;
        return $this;
    }

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->uri                 = null;
        $this->resource            = null;
        $this->context             = null;
        $this->contextOptions      = [];
        $this->contextParams       = [];
        $this->httpResponseHeaders = null;
    }

}
