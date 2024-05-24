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
namespace Pop\Http\Server;

use Pop\Http\Parser;
use Pop\Http\AbstractResponse;
use Pop\Http\Client;

/**
 * HTTP server response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
class Response extends AbstractResponse
{

    /**
     * Prepare response body
     *
     * @param  bool $length
     * @param  bool $mb
     * @return string
     */
    public function prepareBody(bool $length = false, bool $mb = false): string
    {
        $body = $this->body->render();

        if ($this->hasHeader('Content-Encoding') && (count($this->getHeader('Content-Encoding')->getValues()) == 1)) {
            $body = Parser::encodeData($body, strtoupper($this->getHeader('Content-Encoding')->getValue(0)));
            if ($length) {
                $this->addHeader('Content-Length', (($mb) ? mb_strlen($body) : strlen($body)));
            }
        } else if ($length) {
            $this->addHeader('Content-Length', (($mb) ? mb_strlen($body) : strlen($body)));
        }

        return $body;
    }

    /**
     * Get the response headers as a string
     *
     * @param  mixed  $status
     * @param  string $eol
     * @return string
     */
    public function getHeadersAsString(mixed $status = null, string $eol = "\r\n"): string
    {
        $httpStatus = ($status === true) ? "HTTP/{$this->version} {$this->code} {$this->message}" : $status;
        return parent::getHeadersAsString($httpStatus, $eol);
    }

    /**
     * Send response headers
     *
     * @throws Exception
     * @return void
     */
    public function sendHeaders(): void
    {
        if (headers_sent()) {
            throw new Exception('The headers have already been sent.');
        }

        header("HTTP/{$this->version} {$this->code} {$this->message}");
        foreach ($this->headers as $name => $value) {
            if ($value instanceof \Pop\Mime\Part\Header) {
                header((string)$value);
            } else {
                header($name . ": " . $value);
            }
        }
    }

    /**
     * Send full response
     *
     * @param  ?int   $code
     * @param  ?array $headers
     * @param  bool   $length
     * @throws Exception|\Pop\Http\Exception
     * @return void
     */
    public function send(?int $code = null, ?array $headers = null, bool $length = false): void
    {
        if ($code !== null) {
            $this->setCode($code);
        }
        if ($headers !== null) {
            $this->addHeaders($headers);
        }

        $body = $this->prepareBody($length);

        $this->sendHeaders();
        echo $body;
    }

    /**
     * Send full response and exit
     *
     * @param  ?int   $code
     * @param  ?array $headers
     * @param  bool   $length
     * @throws Exception|\Pop\Http\Exception
     * @return void
     */
    public function sendAndExit(?int $code = null, ?array $headers = null, $length = false)
    {
        $this->send($code, $headers, $length);
        exit();
    }

    /**
     * Render entire response as a string
     *
     * @return string
     */
    public function render(): string
    {
        $body = $this->prepareBody();
        return $this->getHeadersAsString(true) . "\r\n" . $body;
    }

    /**
     * Return entire response as a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Send redirect
     *
     * @param  string $url
     * @param  int    $code
     * @param  string $version
     * @throws Exception
     * @return void
     */
    public static function redirect(string $url, int $code = 302, string $version = '1.1'): void
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
     * Send redirect and exit
     *
     * @param  string $url
     * @param  int    $code
     * @param  string $version
     * @throws Exception
     * @return void
     */
    public static function redirectAndExit(string $url, int $code = 302, string $version = '1.1'): void
    {
        static::redirect($url, $code, $version);
        exit();
    }

    /**
     * Forward a client response as the server response
     *
     * @param  Client\Response $clientResponse
     * @throws Exception|\Pop\Http\Exception
     * @return void
     */
    public static function forward(Client\Response $clientResponse): void
    {
        $serverResponse = new static([
            'version' => $clientResponse->getVersion(),
            'code'    => $clientResponse->getCode(),
            'message' => $clientResponse->getMessage(),
            'headers' => $clientResponse->getHeaders(),
            'body'    => $clientResponse->getBody()
        ]);
        $serverResponse->send();
    }

    /**
     * Forward a client response as the server response and exit
     *
     * @param  Client\Response $clientResponse
     * @throws Exception|\Pop\Http\Exception
     * @return void
     */
    public static function forwardAndExit(Client\Response $clientResponse): void
    {
        static::forward($clientResponse);
        exit();
    }

    /**
     * Get response message from code
     *
     * @param  int $code
     * @throws Exception
     * @return string
     */
    public static function getMessageFromCode(int $code): string
    {
        if (!array_key_exists($code, self::$responseCodes)) {
            throw new Exception('The header code ' . $code . ' is not valid.');
        }

        return self::$responseCodes[$code];
    }

}
