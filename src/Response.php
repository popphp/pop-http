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
namespace Pop\Http;

/**
 * HTTP response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.5.0
 */
class Response extends Response\AbstractResponse
{

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
     * Get the response headers as a string
     *
     * @param  boolean $status
     * @param  string  $eol
     * @return string
     */
    public function getHeadersAsString($status = true, $eol = "\r\n")
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
     * @param  boolean $mb
     * @return string
     */
    public function prepareBody($length = false, $mb = false)
    {
        $body        = $this->body->render();
        $lengthValue = ($mb) ? mb_strlen($body) : strlen($body);

        if ($this->hasHeader('Content-Encoding')) {
            $body = Response\Parser::encodeBody($body, $this->getHeader('Content-Encoding')->getValue());
            $this->addHeader('Content-Length', $lengthValue);
        } else if ($length) {
            $this->addHeader('Content-Length', $lengthValue);
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
        return $this->getHeadersAsString() . "\r\n" . $body;
    }

}
