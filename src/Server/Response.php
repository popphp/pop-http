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
namespace Pop\Http\Server;

use Pop\Http\AbstractResponse;
use Pop\Http\Parser;
use Pop\Mime\Part\Body;

/**
 * HTTP response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.0.0
 */
class Response extends AbstractResponse
{

    /**
     * Prepare response body
     *
     * @param  boolean $length
     * @param  boolean $mb
     * @return string
     */
    public function prepareBody($length = false, $mb = false)
    {
        $body = $this->body->render();

        if ($this->hasHeader('Content-Encoding')) {
            $body = Parser::encodeData($body, strtoupper($this->getHeader('Content-Encoding')->getValue()));
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
     * @param  boolean $status
     * @param  string  $eol
     * @return string
     */
    public function getHeadersAsString($status = null, $eol = "\r\n")
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
    public function sendHeaders()
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
            $this->addHeaders($headers);
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
     * Return entire response as a string
     *
     * @return string
     */
    public function __toString()
    {
        $body = $this->prepareBody();
        return $this->getHeadersAsString(true) . "\r\n" . $body;
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

}
