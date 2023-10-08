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
namespace Pop\Http;

/**
 * Abstract HTTP request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractResponse extends AbstractHttp
{

    /**
     * Response codes & messages
     * @var array
     */
    protected static array $responseCodes = [
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
     * HTTP version for response, i.e. 1.0, 1.1, 2.0, 3.0, etc.
     * @var string
     */
    protected string $version = '1.1';

    /**
     * Response code
     * @var ?int
     */
    protected ?int $code = null;

    /**
     * Response message
     * @var ?string
     */
    protected ?string $message = null;

    /**
     * Constructor
     *
     * Instantiate the response object
     *
     * @param  array $config
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
        if (!isset($config['headers']) || (!is_array($config['headers']))) {
            $config['headers'] = ['Content-Type' => 'text/html'];
        }
        if (isset($config['body'])) {
            $this->setBody($config['body']);
        }

        $this->setMessage($config['message'])
            ->addHeaders($config['headers']);
    }

    /**
     * Set the response version
     *
     * @param  float|string $version
     * @return AbstractResponse
     */
    public function setVersion(float|string $version): AbstractResponse
    {
        $this->version = (string)$version;
        return $this;
    }

    /**
     * Get the response HTTP version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set the response code
     *
     * @param  int $code
     * @throws Exception
     * @return AbstractResponse
     */
    public function setCode(int $code = 200): AbstractResponse
    {
        if (!array_key_exists($code, self::$responseCodes)) {
            throw new Exception("Error: The header code '" . $code . "' is not allowed.");
        }

        $this->code    = $code;
        $this->message = self::$responseCodes[$code];

        return $this;
    }

    /**
     * Get the response code
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Set the response message
     *
     * @param  ?string $message
     * @return AbstractResponse
     */
    public function setMessage(?string $message = null): AbstractResponse
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Get the response HTTP message
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Determine if the response is a success
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        $type = floor($this->code / 100);
        return (($type == 1) || ($type == 2) || ($type == 3));
    }

    /**
     * Determine if the response is a redirect
     *
     * @return bool
     */
    public function isRedirect(): bool
    {
        $type = floor($this->code / 100);
        return ($type == 3);
    }

    /**
     * Determine if the response is an error
     *
     * @return bool
     */
    public function isError(): bool
    {
        $type = floor($this->code / 100);
        return (($type == 4) || ($type == 5));
    }

    /**
     * Determine if the response is a client error
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        $type = floor($this->code / 100);
        return ($type == 4);
    }

    /**
     * Determine if the response is a server error
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        $type = floor($this->code / 100);
        return ($type == 5);
    }

}