<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client\Handler;

use Pop\Http\AbstractRequest;
use Pop\Http\Auth;
use Pop\Http\Parser;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Mime\Message;

/**
 * HTTP client curl handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
class Curl extends AbstractCurl
{

    /**
     * Constructor
     *
     * Instantiate the Curl handler object
     *
     * @param  ?array $options
     * @param  bool   $default
     * @throws Exception
     */
    public function __construct(?array $options = null, bool $default = true)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('Error: Curl is not available.');
        }

        $this->resource = curl_init();

        if ($default) {
            $this->setOption(CURLOPT_HEADER, true);
            $this->setOption(CURLOPT_RETURNTRANSFER, true);
        }

        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Factory method to create a Curl handler
     *
     * @param  string $method
     * @param  ?array $options
     * @param  bool  $default
     * @return Curl
     *
     *@throws Exception
     */
    public static function create(string $method = 'GET', ?array $options = null, bool $default = true): Curl
    {
        $handler = new self($options, $default);
        $handler->setMethod($method);
        return $handler;
    }

    /**
     * Set Curl option
     *
     * @param  int   $opt
     * @param  mixed $val
     * @return AbstractCurl
     */
    public function setOption(int $opt, mixed $val): AbstractCurl
    {
        parent::setOption($opt, $val);
        curl_setopt($this->resource, $opt, $val);

        return $this;
    }

    /**
     * Set the method
     *
     * @param  string $method
     * @param  bool   $forceCustom
     * @return Curl
     */
    public function setMethod(string $method, bool $forceCustom = false): Curl
    {
        if ($method == 'GET') {
            if ($this->hasOption(CURLOPT_POST)) {
                $this->removeOption(CURLOPT_POST);
            }
            if ($this->hasOption(CURLOPT_CUSTOMREQUEST)) {
                $this->removeOption(CURLOPT_CUSTOMREQUEST);
            }
        } else if (($method == 'POST') && (!$forceCustom)) {
            $this->setOption(CURLOPT_POST, true);
            if ($this->hasOption(CURLOPT_CUSTOMREQUEST)) {
                $this->removeOption(CURLOPT_CUSTOMREQUEST);
            }
        } else {
            $this->setOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($method == 'HEAD') {
            $this->setOption(CURLOPT_NOBODY, true);
        } else if ($this->hasOption(CURLOPT_NOBODY)) {
            $this->removeOption(CURLOPT_NOBODY);
        }

        return $this;
    }

    /**
     * Set Curl option to return the transfer (set to true by default)
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
     * Set Curl option to return the headers (set to true by default)
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
     * Set Curl option to set verify peer (verifies the domain's SSL cert)
     *
     * @param  bool $verify
     * @return Curl
     */
    public function setVerifyPeer(bool $verify = true): Curl
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, (bool)$verify);
        return $this;
    }

    /**
     * Set Curl option to set to allow self-signed certs
     *
     * @param  bool $allow
     * @return Curl
     */
    public function allowSelfSigned(bool $allow = true): Curl
    {
        $this->setOption(CURLOPT_SSL_VERIFYHOST, (bool)$allow);
        return $this;
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
     * Check if Curl is set to return header
     *
     * @return bool
     */
    public function isReturnHeader(): bool
    {
        return (isset($this->options[CURLOPT_HEADER]) && ($this->options[CURLOPT_HEADER] == true));
    }

    /**
     * Check if Curl is set to verify peer
     *
     * @return bool
     */
    public function isVerifyPeer(): bool
    {
        return (isset($this->options[CURLOPT_SSL_VERIFYPEER]) && ($this->options[CURLOPT_SSL_VERIFYPEER] == true));
    }

    /**
     * Check if Curl is set to allow self-signed certs
     *
     * @return bool
     */
    public function isAllowSelfSigned(): bool
    {
        return (isset($this->options[CURLOPT_SSL_VERIFYHOST]) && ($this->options[CURLOPT_SSL_VERIFYHOST] == true));
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
     * Method to prepare the handler
     *
     * @param  Request|AbstractRequest $request
     * @param  ?Auth                   $auth
     * @param  bool                    $forceCustom
     * @param  bool                    $clear
     * @throws Exception|\Pop\Http\Exception
     * @return Curl
     */
    public function prepare(Request|AbstractRequest $request, ?Auth $auth = null, bool $forceCustom = false, bool $clear = true): Curl
    {
        $this->setMethod($request->getMethod(), $forceCustom);

        // Clear headers for a fresh request based on the headers in the request object,
        // else fall back to pre-defined headers in the stream context
        if (($clear) && $this->hasOption(CURLOPT_HTTPHEADER)) {
            $this->setOption(CURLOPT_HTTPHEADER, []);
        }

        // Add auth header
        if ($auth !== null) {
            $request->addHeader($auth->createAuthHeader());
        }

        // Prepare data and data headers
        if (($request->hasData()) && (!$request->getData()->isPrepared())) {
            $request->prepareData();
        }

        // Add headers
        if ($request->hasHeaders()) {
            $headers = [];

            foreach ($request->getHeaders() as $header => $value) {
                if (!(($request->getMethod() == 'GET') && ($header == 'Content-Length'))) {
                    if (is_array($value)) {
                        foreach ($value as $val) {
                            $headers[] = (string)$val;
                        }
                    } else {
                        $headers[] = (string)$value;
                    }
                }
            }
            if ($this->hasOption(CURLOPT_HTTPHEADER)) {
                $customHeaders = $this->getOption(CURLOPT_HTTPHEADER);
                foreach ($customHeaders as $customHeader) {
                    if (!in_array($customHeader, $headers)) {
                        $headers[] = $customHeader;
                    }
                }
            }
            $this->setOption(CURLOPT_HTTPHEADER, $headers);
        }

        $queryString = null;

        // If request has a query
        if ($request->hasQuery()) {
            $queryString = '?' . $request->getQuery()->prepare()->getDataContent();
        }

        // If request has data
        if ($request->hasData()) {
            // Set request data content
            if ($request->hasDataContent()) {
                // If it's a URL-encoded GET request
                if (($queryString === null) && ($request->isGet()) && (!$request->hasRequestType() || $request->isUrlEncoded())) {
                    $queryString = '?' . $request->getDataContent();

                    // Clear old request data
                    if ($this->hasOption(CURLOPT_POSTFIELDS)) {
                        $this->removeOption(CURLOPT_POSTFIELDS);
                    }
                // Else, set data content
                } else {
                    $this->setOption(CURLOPT_POSTFIELDS, $request->getDataContent());
                }
            }
        // Else, if there is raw body content
        } else if ($request->hasBodyContent()) {
            $request->addHeader('Content-Length', $request->getBodyContentLength());
            $this->setOption(CURLOPT_POSTFIELDS, $request->getBodyContent());
        }

        $this->uri = $request->getUriAsString();
        if (!empty($queryString) && !str_contains($this->uri, '?')) {
            $this->uri .= $queryString;
        }

        $this->setOption(CURLOPT_URL, $this->uri);

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
        $this->response = curl_exec($this->resource);

        if ($this->response === false) {
            throw new Exception('Error: ' . curl_errno($this->resource) . ' => ' . curl_error($this->resource) . '.');
        }

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

        // If the CURLOPT_RETURNTRANSFER option is set, get the response body and parse the headers.
        if (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER])) {
            $headerSize = $this->getInfo(CURLINFO_HEADER_SIZE);
            if (isset($this->options[CURLOPT_HEADER]) && ($this->options[CURLOPT_HEADER])) {
                $parsedHeaders = Parser::parseHeaders(substr($this->response, 0, $headerSize));
                if (!empty($parsedHeaders['version'])) {
                    $response->setVersion($parsedHeaders['version']);
                }
                if (!empty($parsedHeaders['code'])) {
                    $response->setCode($parsedHeaders['code']);
                }
                if (!empty($parsedHeaders['message'])) {
                    $response->setMessage($parsedHeaders['message']);
                }
                if (!empty($parsedHeaders['headers'])) {
                    $response->addHeaders($parsedHeaders['headers']);
                }
                if (!empty($this->response)) {
                    $response->setBody(substr($this->response, $headerSize));
                }
            } else if (!empty($this->response)) {
                $response->setBody($this->response);
            }
        }

        if ($response->hasHeader('Content-Encoding')) {
            $response->decodeBodyContent();
        }

        return $response;
    }

    /**
     * Method to reset the handler
     *
     * @param  bool $default
     * @return Curl
     */
    public function reset(bool $default = true): Curl
    {
        curl_reset($this->resource);
        $this->response = null;
        $this->options  = [];

        if ($default) {
            $this->setOption(CURLOPT_HEADER, true);
            $this->setOption(CURLOPT_RETURNTRANSFER, true);
        }

        return $this;
    }

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->hasResource()) {
            curl_close($this->resource);
            $this->resource = null;
            $this->response = null;
            $this->options  = [];
        }
    }

}
