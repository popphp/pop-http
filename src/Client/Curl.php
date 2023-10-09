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

use Pop\Http\Parser;
use Pop\Mime\Message;

/**
 * HTTP curl client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Curl extends AbstractClient
{

    /**
     * Curl options
     * @var array
     */
    protected array $options = [];

    /**
     * Constructor
     *
     * Instantiate the Curl object
     *
     * @param  ?string $url
     * @param  string  $method
     * @param  ?array  $opts
     * @throws Exception
     */
    public function __construct(?string $url = null, string $method = 'GET', ?array $opts = null)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Error: Curl is not available.');
        }

        $this->resource = curl_init();

        parent::__construct($url, $method);

        $this->setOption(CURLOPT_HEADER, true);
        $this->setOption(CURLOPT_RETURNTRANSFER, true);

        if ($opts !== null) {
            $this->setOptions($opts);
        }
    }

    /**
     * Factory method to create a Curl client
     *
     * @param  ?string $url
     * @param  string $method
     * @param  ?array $opts
     * @throws Exception
     * @return Curl
     *
     */
    public static function create(?string $url = null, string $method = 'GET', ?array $opts = null): Curl
    {
        return new self($url, $method, $opts);
    }

    /**
     * Set the method
     *
     * @param  string $method
     * @param  bool   $strict
     * @param  bool   $forceCustom
     * @throws Exception
     * @return Curl
     */
    public function setMethod(string $method, bool $strict = true, bool $forceCustom = false): Curl
    {
        parent::setMethod($method, $strict);

        if (($this->method == 'POST') && (!$forceCustom)) {
            $this->setOption(CURLOPT_POST, true);
        } else if ($this->method != 'GET') {
            $this->setOption(CURLOPT_CUSTOMREQUEST, $this->method);
        }

        return $this;
    }

    /**
     * Return Curl resource (alias to $this->getResource())
     *
     * @return mixed
     */
    public function curl(): mixed
    {
        return $this->resource;
    }

    /**
     * Set Curl option
     *
     * @param  int   $opt
     * @param  mixed $val
     * @return Curl
     */
    public function setOption(int $opt, mixed $val): Curl
    {
        // Set the protected property to keep track of the Curl options.
        $this->options[$opt] = $val;
        curl_setopt($this->resource, $opt, $val);

        return $this;
    }

    /**
     * Set Curl options
     *
     * @param  array $opts
     * @return Curl
     */
    public function setOptions(array $opts): Curl
    {
        // Set the protected property to keep track of the Curl options.
        foreach ($opts as $k => $v) {
            $this->options[$k] = $v;
        }

        curl_setopt_array($this->resource, $opts);

        return $this;
    }

    /**
     * Set Curl option to return the header
     *
     * @param  bool $header
     * @return Curl
     */
    public function setReturnHeader(bool $header = true): Curl
    {
        $this->setOption(CURLOPT_HEADER, (bool)$header);
        return $this;
    }

    /**
     * Set Curl option to return the transfer
     *
     * @param  bool $transfer
     * @return Curl
     */
    public function setReturnTransfer(bool $transfer = true): Curl
    {
        $this->setOption(CURLOPT_RETURNTRANSFER, (bool)$transfer);
        return $this;
    }

    /**
     * Check if Curl is set to return header
     *
     * @return bool
     */
    public function isReturnHeader(): bool
    {
        return (isset($this->options[CURLOPT_HEADER]) && ($this->options[CURLOPT_HEADER] == true));
    }

    /**
     * Check if Curl is set to return transfer
     *
     * @return bool
     */
    public function isReturnTransfer(): bool
    {
        return (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER] == true));
    }

    /**
     * Get a Curl option
     *
     * @param  int $opt
     * @return mixed
     */
    public function getOption(int $opt): mixed
    {
        return $this->options[$opt] ?? null;
    }

    /**
     * Has a Curl option
     *
     * @param  int $opt
     * @return bool
     */
    public function hasOption(int $opt): bool
    {
        return (isset($this->options[$opt]));
    }

    /**
     * Return the Curl last info
     *
     * @param  ?int $opt
     * @return array|string
     */
    public function getInfo(?int $opt = null): array|string
    {
        return ($opt !== null) ? curl_getinfo($this->resource, $opt) : curl_getinfo($this->resource);
    }

    /**
     * Create and open Curl resource
     *
     * @throws Exception|\Pop\Http\Exception
     * @return Curl
     */
    public function open(): Curl
    {
        $url = $this->url;

        // Set auth header if there is one
        if ($this->auth !== null) {
            $this->getRequest()->addHeader($this->auth->createAuthHeader());
        }

        if ($this->request !== null) {
            // Set query data if there is any
            if ($this->request->hasFields()) {
                // Append GET query string to URL
                if (($this->method == 'GET') && ((!$this->request->hasHeader('Content-Type')) ||
                        ($this->request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded'))) {
                    $url .= '?' . $this->request->getQuery();
                    if (!$this->request->hasHeader('Content-Type')) {
                        $this->request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
                    }
                // Else, prepare request data for transmission
                } else {
                    // If request is JSON
                    if ($this->request->getHeaderValue('Content-Type') == 'application/json') {
                        $content = json_encode($this->request->getFields(), JSON_PRETTY_PRINT);
                        $this->request->addHeader('Content-Length', strlen($content));
                        $this->setOption(CURLOPT_POSTFIELDS, $content);
                    // If request is a URL-encoded form
                    } else if ($this->request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded') {
                        $this->request->addHeader('Content-Length', $this->request->getQueryLength());
                        $this->setOption(CURLOPT_POSTFIELDS, $this->request->getQuery());
                    // Else, if request is a multipart form
                    } else if ($this->request->isMultipartForm()) {
                        $formMessage = Message::createForm($this->request->getFields());
                        $header      = $formMessage->getHeader('Content-Type');
                        $content     = $formMessage->render(false);
                        $formMessage->removeHeader('Content-Type');
                        $this->request->addHeader($header)
                            ->addHeader('Content-Length', strlen($content));
                        $this->setOption(CURLOPT_POSTFIELDS, $content);
                    // Else, basic request with data
                    } else {
                        $this->setOption(CURLOPT_POSTFIELDS, $this->request->getFields());
                        if (!$this->request->hasHeader('Content-Type')) {
                            $this->request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
                        }
                    }
                }
            // Else, if there is raw body content
            } else if ($this->request->hasBodyContent()) {
                $this->request->addHeader('Content-Length', strlen($this->request->getBodyContent()));
                $this->setOption(CURLOPT_POSTFIELDS, $this->request->getBodyContent());
            }

            if ($this->request->hasHeaders()) {
                $headers = [];
                foreach ($this->request->getHeaders() as $header) {
                    $headers[] = $header->render();
                }
                $this->setOption(CURLOPT_HTTPHEADER, $headers);
            }
        }

        $this->setOption(CURLOPT_URL, $url);

        return $this;
    }

    /**
     * Method to send the request and get the response
     *
     * @throws Exception|\Pop\Http\Exception
     * @return void
     */
    public function send(): void
    {
        $this->open();

        $response = curl_exec($this->resource);

        if ($response === false) {
            $this->throwError('Error: ' . curl_errno($this->resource) . ' => ' . curl_error($this->resource) . '.');
        }

        if ($this->response === null) {
            $this->response = new Response();
        }

        // If the CURLOPT_RETURNTRANSFER option is set, get the response body and parse the headers.
        if (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER])) {
            $headerSize = $this->getInfo(CURLINFO_HEADER_SIZE);
            if ($this->options[CURLOPT_HEADER]) {
                $parsedHeaders = Parser::parseHeaders(substr($response, 0, $headerSize));
                $this->response->setVersion($parsedHeaders['version']);
                $this->response->setCode($parsedHeaders['code']);
                $this->response->setMessage($parsedHeaders['message']);
                $this->response->addHeaders($parsedHeaders['headers']);
                $this->response->setBody(substr($response, $headerSize));
            } else {
                $this->response->setBody($response);
            }
        }

        if ($this->response->hasHeader('Content-Encoding')) {
            $this->response->decodeBodyContent();
        }
    }

    /**
     * Method to reset the client object
     *
     * @return Curl
     */
    public function reset(): Curl
    {
        $this->request  = new Request();
        $this->response = new Response();

        return $this;
    }

    /**
     * Return the Curl version
     *
     * @return array
     */
    public function version(): array
    {
        return curl_version();
    }

    /**
     * Close the Curl connection
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->hasResource()) {
            curl_close($this->resource);
            $this->resource = null;
            $this->request  = new Request();
            $this->response = new Response();
            $this->options  = [];

        }
    }

}
