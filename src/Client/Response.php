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

use Pop\Http\Response\Parser;

/**
 * HTTP client response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2019 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.2.0
 */
class Response extends AbstractClientObject
{

    /**
     * Response codes & messages
     * @var array
     */
    protected static $responseCodes = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    ];

    /**
     * HTTP version from response
     * @var string
     */
    protected $version = 1.1;

    /**
     * Response code
     * @var int
     */
    protected $code = null;

    /**
     * Response message
     * @var string
     */
    protected $message = null;

    /**
     * Response body
     * @var string
     */
    protected $body = null;

    /**
     * Raw response string
     * @var string
     */
    protected $response = null;

    /**
     * Set the response version
     *
     * @param  float $version
     * @return Response
     */
    public function setVersion($version = 1.1)
    {
        if (($version == 1.0) || ($version == 1.1)) {
            $this->version = $version;
        }
        return $this;
    }

    /**
     * Get the response HTTP version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the response code
     *
     * @param  int $code
     * @throws \Pop\Http\Exception
     * @return Response
     */
    public function setCode($code = 200)
    {
        if (!array_key_exists($code, self::$responseCodes)) {
            throw new \Pop\Http\Exception('The header code ' . $code . ' is not allowed.');
        }

        $this->code    = $code;
        $this->message = self::$responseCodes[$code];

        return $this;
    }

    /**
     * Get the response code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set the response message
     *
     * @param  string $message
     * @return Response
     */
    public function setMessage($message = null)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get the response HTTP message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the response body
     *
     * @param  string $body
     * @return Response
     */
    public function setBody($body = null)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Get the response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set the raw response string
     *
     * @param  string $response
     * @return Response
     */
    public function setResponse($response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get the raw response
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Determine if the response is a success
     *
     * @return boolean
     */
    public function isSuccess()
    {
        $type = floor($this->code / 100);
        return (($type == 1) || ($type == 2) || ($type == 3));
    }

    /**
     * Determine if the response is a redirect
     *
     * @return boolean
     */
    public function isRedirect()
    {
        $type = floor($this->code / 100);
        return ($type == 3);
    }

    /**
     * Determine if the response is an error
     *
     * @return boolean
     */
    public function isError()
    {
        $type = floor($this->code / 100);
        return (($type == 4) || ($type == 5));
    }

    /**
     * Determine if the response is a client error
     *
     * @return boolean
     */
    public function isClientError()
    {
        $type = floor($this->code / 100);
        return ($type == 4);
    }

    /**
     * Determine if the response is a server error
     *
     * @return boolean
     */
    public function isServerError()
    {
        $type = floor($this->code / 100);
        return ($type == 5);
    }
    
    /**
     * Decode the body
     *
     * @param  string $body
     * @return string
     */
    public function decodeBody($body = null)
    {
        if (null !== $body) {
            $this->setBody($body);
        }
        if (isset($this->headers['Transfer-Encoding']) && (strtolower($this->headers['Transfer-Encoding'] == 'chunked'))) {
            $this->body = Parser::decodeChunkedBody($this->body);
        }
        $this->body = Parser::decodeBody($this->body, $this->headers['Content-Encoding']);

        return $this->body;
    }

    /**
     * Parse response headers
     *
     * @param  string|array $responseHeader
     * @return void
     */
    public function parseResponseHeaders($responseHeader)
    {
        if (null !== $responseHeader) {
            $headers = (is_string($responseHeader)) ? explode("\n", $responseHeader) : (array)$responseHeader;
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
                    if (isset($this->headers[$name])) {
                        if (!is_array($this->headers[$name])) {
                            $this->headers[$name] = [$this->headers[$name]];
                        }
                        $this->headers[$name][] = $value;
                    } else {
                        $this->headers[$name] = $value;
                    }
                }
            }
        }
    }

}