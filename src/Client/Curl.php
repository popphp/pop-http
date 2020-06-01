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
 * HTTP curl client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
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
    public function __construct($url = null, $method = 'GET', array $opts = null)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Error: cURL is not available.');
        }

        $this->resource = curl_init();

        parent::__construct($url, $method);


        $this->setOption(CURLOPT_HEADER, true);
        $this->setOption(CURLOPT_RETURNTRANSFER, true);

        if (null !== $opts) {
            $this->setOptions($opts);
        }
    }

    /**
     * Set the method
     *
     * @param  string  $method
     * @param  boolean $strict
     * @throws Exception
     * @return Curl
     */
    public function setMethod($method, $strict = true)
    {
        parent::setMethod($method, $strict);

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
     * Has a cURL session option
     *
     * @param  int $opt
     * @return boolean
     */
    public function hasOption($opt)
    {
        return (isset($this->options[$opt]));
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
     * Create and open cURL resource
     *
     * @return Curl
     */
    public function open()
    {
        $url = $this->url;

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
                    }
                }
            // Else, if there is raw body content
            } else if ($this->request->hasBody()) {
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
     * @return void
     */
    public function send()
    {
        $this->open();

        $response = curl_exec($this->resource);

        if ($response === false) {
            $this->throwError('Error: ' . curl_errno($this->resource) . ' => ' . curl_error($this->resource) . '.');
        }

        if (null === $this->response) {
            $this->response = new Response();
        }

        // If the CURLOPT_RETURNTRANSFER option is set, get the response body and parse the headers.
        if (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER] == true)) {
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

}
