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
 * HTTP server class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 * @property   $request Server\Request
 * @property   $response Server\Response
 */
class Server extends AbstractHttp
{

    /**
     * Instantiate the server object
     *
     * @param ?Server\Request  $request
     * @param ?Server\Response $response
     */
    public function __construct(
        ?Server\Request $request = new Server\Request(new Uri()), ?Server\Response $response = new Server\Response()
    )
    {
        parent::__construct($request, $response);
    }

    /**
     * Create server object with a base path reference for the request URI
     *
     * @param  string $basePath
     * @return Server
     */
    public static function createWithBasePath(string $basePath): Server
    {
        return new self(new Server\Request(new Uri(null, $basePath)));
    }

    /**
     * Get request (shorthand)
     *
     * @return Server\Request
     */
    public function request(): Server\Request
    {
        return $this->request;
    }

    /**
     * Get response (shorthand)
     *
     * @return Server\Response
     */
    public function response(): Server\Response
    {
        return $this->response;
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
        return $this->response->getHeadersAsString($status, $eol);
    }

    /**
     * Send response headers
     *
     * @throws Exception
     * @return void
     */
    public function sendHeaders(): void
    {
        $this->response->sendHeaders();
    }

    /**
     * Send the server response
     *
     * @return void
     */
    public function send(?int $code = null, ?array $headers = null, bool $length = false): void
    {
        $this->response->send($code, $headers, $length);
    }


    /**
     * Send the server response and exit
     *
     * @return void
     */
    public function sendAndExit(?int $code = null, ?array $headers = null, bool $length = false): void
    {
        $this->response->sendAndExit($code, $headers, $length);
    }

    /**
     * Render response as a raw string
     *
     * @return string
     */
    public function render(): string
    {
        return ($this->response !== null) ? $this->response->render() : '';
    }

    /**
     * Render response as a raw string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Magic method to get the request or response object
     *
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'request'  => $this->request,
            'response' => $this->response,
            default    => null,
        };
    }

}
