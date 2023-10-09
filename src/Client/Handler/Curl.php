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
use Pop\Http\Exception;
use Pop\Http\Parser;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Mime\Message;

/**
 * HTTP client curl handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Curl extends AbstractCurl
{

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
     * @param Request $request
     * @param  ?Auth $auth
     * @throws Exception
     * @return Curl
     */
    public function prepare(Request $request, ?Auth $auth = null): Curl
    {
        $uri = $request->getUriAsString();

        // Add auth header
        if ($auth !== null) {
            $request->addHeader($auth->createAuthHeader());
        }

        // If request has data
        if ($request->hasData()) {
            // Append GET query string to URL
            if (($request->isGet()) && ((!$request->hasHeader('Content-Type')) ||
                    ($request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded'))) {
                $uri .= $request->getData()->prepareQueryString(true);
            // Else, prepare request data for transmission
            } else {
                // If request is JSON
                if ($request->getHeaderValue('Content-Type') == 'application/json') {
                    $content = json_encode($request->getData(true), JSON_PRETTY_PRINT);
                    $request->addHeader('Content-Length', strlen($content));
                    $this->setOption(CURLOPT_POSTFIELDS, $content);
                // If request is a URL-encoded form
                } else if ($request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded') {
                    $request->addHeader('Content-Length', $request->getData()->getQueryStringLength());
                    $this->setOption(CURLOPT_POSTFIELDS, $request->getData()->prepareQueryString());
                // Else, if request is a multipart form
                } else if ($request->isMultipartForm()) {
                    $formMessage = Message::createForm($request->getData(true));
                    $header      = $formMessage->getHeader('Content-Type');
                    $content     = $formMessage->render(false);
                    $formMessage->removeHeader('Content-Type');
                    $request->addHeader($header)
                        ->addHeader('Content-Length', strlen($content));
                    $this->setOption(CURLOPT_POSTFIELDS, $content);
                // Else, basic request with data
                } else {
                    $this->setOption(CURLOPT_POSTFIELDS, $request->getData(true));
                    if (!$request->hasHeader('Content-Type')) {
                        $request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
                    }
                }
            }
        // Else, if request has raw body content
        } else if ($request->hasBodyContent()) {
            $request->addHeader('Content-Length', strlen($request->getBodyContent()));
            $this->setOption(CURLOPT_POSTFIELDS, $request->getBodyContent());
        }

        $this->setOption(CURLOPT_URL, $uri);

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
            if ($this->options[CURLOPT_HEADER]) {
                $parsedHeaders = Parser::parseHeaders(substr($this->response, 0, $headerSize));
                $response->setVersion($parsedHeaders['version']);
                $response->setCode($parsedHeaders['code']);
                $response->setMessage($parsedHeaders['message']);
                $response->addHeaders($parsedHeaders['headers']);
                $response->setBody(substr($this->response, $headerSize));
            } else {
                $response->setBody($this->response);
            }
        }

        if ($response->hasHeader('Content-Encoding')) {
            $response->decodeBodyContent();
        }

        return $response;
    }

    /**
     * Method to send the request asynchronously
     *
     * @return mixed
     */
    public function sendAsync(): mixed
    {
        return null;
    }

    /**
     * Method to reset the handler
     *
     * @return Curl
     */
    public function reset(): Curl
    {
        return $this;
    }

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void
    {

    }

}
