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

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Body;

/**
 * Abstract HTTP response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
abstract class AbstractResponse extends AbstractRequestResponse
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
     * HTTP version for response, i.e. 1.0, 1.1, 2.0, etc.
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
     * Factory method to create a Response object
     *
     * @param  array $config
     * @return static
     */
    public static function create(array $config = []): static
    {
        return new static($config);
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
     * Has the response HTTP version
     *
     * @return bool
     */
    public function hasVersion(): bool
    {
        return ($this->version !== null);
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
     * Has the response code
     *
     * @return bool
     */
    public function hasCode(): bool
    {
        return ($this->code !== null);
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
     * Has the response message
     *
     * @return bool
     */
    public function hasMessage(): bool
    {
        return ($this->message !== null);
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
     * Determine if the response is a 100 continue
     *
     * @return bool
     */
    public function isContinue(): bool
    {
        $type = floor($this->code / 100);
        return ($type == 1);
    }

    /**
     * Determine if the response is 200 OK
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return ($this->code == 200);
    }

    /**
     * Determine if the response is 201 created
     *
     * @return bool
     */
    public function isCreated(): bool
    {
        return ($this->code == 201);
    }

    /**
     * Determine if the response is 202 accepted
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return ($this->code == 202);
    }

    /**
     * Determine if the response is 204 No Content
     *
     * @return bool
     */
    public function isNoContent(): bool
    {
        return ($this->code == 204);
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
     * Determine if the response is a 301 Moved Permanently
     *
     * @return bool
     */
    public function isMovedPermanently(): bool
    {
        return ($this->code == 301);
    }

    /**
     * Determine if the response is a 302 Found
     *
     * @return bool
     */
    public function isFound(): bool
    {
        return ($this->code == 302);
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
     * Determine if the response is a 400 Bad Request
     *
     * @return bool
     */
    public function isBadRequest(): bool
    {
        return ($this->code == 400);
    }

    /**
     * Determine if the response is a 401 Unauthorized
     *
     * @return bool
     */
    public function isUnauthorized(): bool
    {
        return ($this->code == 401);
    }

    /**
     * Determine if the response is a 403 Forbidden
     *
     * @return bool
     */
    public function isForbidden(): bool
    {
        return ($this->code == 403);
    }

    /**
     * Determine if the response is a 404 Not Found
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return ($this->code == 404);
    }

    /**
     * Determine if the response is a 405 Method Not Allowed
     *
     * @return bool
     */
    public function isMethodNotAllowed(): bool
    {
        return ($this->code == 405);
    }

    /**
     * Determine if the response is a 406 Not Acceptable
     *
     * @return bool
     */
    public function isNotAcceptable(): bool
    {
        return ($this->code == 406);
    }

    /**
     * Determine if the response is a 408 Request Timeout
     *
     * @return bool
     */
    public function isRequestTimeout(): bool
    {
        return ($this->code == 408);
    }

    /**
     * Determine if the response is a 409 Conflict
     *
     * @return bool
     */
    public function isConflict(): bool
    {
        return ($this->code == 409);
    }

    /**
     * Determine if the response is a 411 Length Required
     *
     * @return bool
     */
    public function isLengthRequired(): bool
    {
        return ($this->code == 411);
    }

    /**
     * Determine if the response is a 415 Unsupported Media Type
     *
     * @return bool
     */
    public function isUnsupportedMediaType(): bool
    {
        return ($this->code == 415);
    }

    /**
     * Determine if the response is a 422 Unprocessable Entity
     *
     * @return bool
     */
    public function isUnprocessableEntity(): bool
    {
        return ($this->code == 422);
    }

    /**
     * Determine if the response is a 429 Too Many Requests
     *
     * @return bool
     */
    public function isTooManyRequests(): bool
    {
        return ($this->code == 429);
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

    /**
     * Determine if the response is a 500 Internal Server Error
     *
     * @return bool
     */
    public function isInternalServerError(): bool
    {
        return ($this->code == 500);
    }

    /**
     * Determine if the response is a 502 Bad Gateway
     *
     * @return bool
     */
    public function isBadGateway(): bool
    {
        return ($this->code == 502);
    }

    /**
     * Determine if the response is a 503 Service Unavailable
     *
     * @return bool
     */
    public function isServiceUnavailable(): bool
    {
        return ($this->code == 503);
    }

}
