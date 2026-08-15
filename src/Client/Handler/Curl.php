<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Parser;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;

/**
 * HTTP client curl handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
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
            // libcurl does NOT revert a handle to GET when CURLOPT_POST/CURLOPT_POSTFIELDS are
            // unset - it stays in POST mode until GET is asserted explicitly. Only matters when a
            // handler instance is reused, but there the request would otherwise silently go out
            // as a POST (verified on the wire).
            $this->setOption(CURLOPT_HTTPGET, true);
        } else {
            if ($this->hasOption(CURLOPT_HTTPGET)) {
                $this->removeOption(CURLOPT_HTTPGET);
            }
            if (($method == 'POST') && (!$forceCustom)) {
                $this->setOption(CURLOPT_POST, true);
                if ($this->hasOption(CURLOPT_CUSTOMREQUEST)) {
                    $this->removeOption(CURLOPT_CUSTOMREQUEST);
                }
            } else {
                $this->setOption(CURLOPT_CUSTOMREQUEST, $method);
            }
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
     * @return array|string|int|float|bool
     */
    public function getInfo(?int $opt = null): array|string|int|float|bool
    {
        return ($opt !== null) ? curl_getinfo($this->resource, $opt) : curl_getinfo($this->resource);
    }

    /**
     * Method to prepare the handler
     *
     * @param  Request $request
     * @param  ?Auth    $auth
     * @param  bool     $forceCustom
     * @param  bool     $clear
     * @throws \Pop\Http\Exception
     * @return Curl
     */
    public function prepare(Request $request, ?Auth $auth = null, bool $forceCustom = false, bool $clear = true): Curl
    {
        $this->request = $request;

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

        $headers = $this->collectRequestHeaders($request);
        if ($this->hasOption(CURLOPT_HTTPHEADER)) {
            $customHeaders = $this->getOption(CURLOPT_HTTPHEADER);
            foreach ($customHeaders as $customHeader) {
                if (!in_array($customHeader, $headers)) {
                    $headers[] = $customHeader;
                }
            }
        }

        ['queryString' => $queryString, 'body' => $body] = $this->resolveRequestBody($request);

        // Multipart bodies are handed to curl as an array (scalars + CURLFile) so it can build
        // its own matching multipart framing; pop-http's own Content-Type/boundary header (set by
        // prepareData() above) would otherwise disagree with the boundary curl actually generates.
        // Matched case-insensitively: HTTP header names are case-insensitive on the wire, so a
        // user-supplied 'content-type: ...' would otherwise survive this strip and be sent
        // alongside curl's own auto-generated multipart Content-Type.
        if (is_array($body)) {
            $headers = array_values(array_filter($headers, fn($header) => stripos($header, 'content-type:') !== 0));
        }

        $this->setOption(CURLOPT_HTTPHEADER, $headers);

        if ($body !== null) {
            $this->setOption(CURLOPT_POSTFIELDS, $body);
        // A reused handler must not carry a previous request's body forward: curl switches the
        // method to POST whenever CURLOPT_POSTFIELDS is set, so a stale value would silently
        // turn a subsequent body-less GET into a POST. Gated on $clear for the same reason the
        // header handling above is: with $clear = false the caller is explicitly asking to fall
        // back to whatever was pre-defined on the handler itself.
        } else if (($clear) && $this->hasOption(CURLOPT_POSTFIELDS)) {
            $this->removeOption(CURLOPT_POSTFIELDS);
            // Clearing CURLOPT_POSTFIELDS is itself a curl_setopt(..., null) call, which puts
            // the handle right back into POST mode - so the method has to be re-asserted AFTER
            // the removal, not just once at the top of prepare().
            $this->setMethod($request->getMethod(), $forceCustom);
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
            throw new Exception(
                'Error: ' . curl_errno($this->resource) . ' => ' . curl_error($this->resource) . '.',
                0, null, curl_errno($this->resource), $this->request
            );
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
            $headerSize = (int)$this->getInfo(CURLINFO_HEADER_SIZE);
            if (isset($this->options[CURLOPT_HEADER]) && ($this->options[CURLOPT_HEADER])) {
                $parsedHeaders = Parser::parseHeaders(substr($this->response, 0, $headerSize));
                if (!empty($parsedHeaders['version'])) {
                    $response->setVersion($parsedHeaders['version']);
                }
                if (!empty($parsedHeaders['code'])) {
                    $response->setCode((int)$parsedHeaders['code']);
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
