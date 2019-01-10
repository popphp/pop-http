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
namespace Pop\Http;

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
class Response
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
     * HTTP version
     * @var string
     */
    protected $version = '1.1';

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
     * Response headers
     * @var array
     */
    protected $headers = [];

    /**
     * Response body
     * @var string
     */
    protected $body = null;

    /**
     * Constructor
     *
     * Instantiate the response object
     *
     * @param  array $config
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        // Check for config values and set defaults
        if (!isset($config['version'])) {
            $config['version'] = '1.1';
        }
        if (!isset($config['code'])) {
            $config['code'] = 200;
        }

        $this->setVersion($config['version'])
             ->setCode($config['code']);

        if (!isset($config['message'])) {
            $config['message'] = self::$responseCodes[$config['code']];
        }
        if (!isset($config['headers']) || (isset($config['headers']) && !is_array($config['headers']))) {
            $config['headers'] = ['Content-Type' => 'text/html'];
        }
        if (!isset($config['body'])) {
            $config['body'] = null;
        }

        $this->setMessage($config['message'])
             ->setHeaders($config['headers'])
             ->setBody($config['body']);
    }

    /**
     * Send redirect
     *
     * @param  string $url
     * @param  string $code
     * @param  string $version
     * @throws Exception
     * @return void
     */
    public static function redirect($url, $code = '302', $version = '1.1')
    {
        if (headers_sent()) {
            throw new Exception('The headers have already been sent.');
        }

        if (!array_key_exists($code, self::$responseCodes)) {
            throw new Exception('The header code '. $code . ' is not allowed.');
        }

        header("HTTP/{$version} {$code} " . self::$responseCodes[$code]);
        header("Location: {$url}");
    }

    /**
     * Get response message from code
     *
     * @param  int $code
     * @throws Exception
     * @return string
     */
    public static function getMessageFromCode($code)
    {
        if (!array_key_exists($code, self::$responseCodes)) {
            throw new Exception('The header code ' . $code . ' is not valid.');
        }

        return self::$responseCodes[$code];
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
     * Get the response version
     *
     * @return float
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the response code
     *
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Get the response message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
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
     * Get the response headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the response header
     *
     * @param  string $name
     * @return string
     */
    public function getHeader($name)
    {
        return (isset($this->headers[$name])) ? $this->headers[$name] : null;
    }

    /**
     * Get the response headers as a string
     *
     * @param  boolean $status
     * @param  string  $eol
     * @return string
     */
    public function getHeadersAsString($status = true, $eol = "\n")
    {
        $headers = '';

        if ($status) {
            $headers = "HTTP/{$this->version} {$this->code} {$this->message}{$eol}";
        }

        foreach ($this->headers as $name => $value) {
            $headers .= "{$name}: {$value}{$eol}";
        }

        return $headers;
    }

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
     * Set the response code
     *
     * @param  int $code
     * @throws Exception
     * @return Response
     */
    public function setCode($code = 200)
    {
        if (!array_key_exists($code, self::$responseCodes)) {
            throw new Exception('That header code ' . $code . ' is not allowed.');
        }

        $this->code    = $code;
        $this->message = self::$responseCodes[$code];

        return $this;
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
     * Set a response header
     *
     * @param  string $name
     * @param  string $value
     * @throws Exception
     * @return Response
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set response headers
     *
     * @param  array $headers
     * @throws Exception
     * @return Response
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }

        return $this;
    }

    /**
     * Send response headers
     *
     * @throws Exception
     * @return void
     */
    public function sendHeaders()
    {
        if (headers_sent()) {
            throw new Exception('The headers have already been sent.');
        }

        header("HTTP/{$this->version} {$this->code} {$this->message}");
        foreach ($this->headers as $name => $value) {
            header($name . ": " . $value);
        }
    }

    /**
     * Prepare response body
     *
     * @param  boolean $length
     * @return string
     */
    public function prepareBody($length = false)
    {
        $body = $this->body;

        if (array_key_exists('Content-Encoding', $this->headers)) {
            $body = Response\Parser::encodeBody($body, $this->headers['Content-Encoding']);
            $this->headers['Content-Length'] = strlen($body);
        } else if ($length) {
            $this->headers['Content-Length'] = strlen($body);
        }

        return $body;
    }

    /**
     * Send full response
     *
     * @param  int     $code
     * @param  array   $headers
     * @param  boolean $length
     * @return void
     */
    public function send($code = null, array $headers = null, $length = false)
    {
        if (null !== $code) {
            $this->setCode($code);
        }
        if (null !== $headers) {
            $this->setHeaders($headers);
        }

        $body = $this->prepareBody($length);

        $this->sendHeaders();
        echo $body;
    }

    /**
     * Send full response and exit
     *
     * @param  int   $code
     * @param  array $headers
     * @param  boolean $length
     * @return void
     */
    public function sendAndExit($code = null, array $headers = null, $length = false)
    {
        $this->send($code, $headers, $length);
        exit();
    }

    /**
     * Magic method to get a value from the headers
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            case 'headers':
                return $this->headers;
                break;
            default:
                return null;
        }
    }

    /**
     * Return entire response as a string
     *
     * @return string
     */
    public function __toString()
    {
        $body = $this->prepareBody();
        return $this->getHeadersAsString() . "\n" . $body;
    }

}
