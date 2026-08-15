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
namespace Pop\Http\Client;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * HTTP client request exception class - thrown by Client::sendRequest() for
 * pre-dispatch validation failures (e.g. no request URI to send)
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class RequestException extends Exception implements RequestExceptionInterface
{

    /**
     * The request that failed to dispatch
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * Constructor
     *
     * @param string            $message
     * @param RequestInterface  $request
     * @param int               $code
     * @param ?\Throwable       $previous
     */
    public function __construct(string $message, RequestInterface $request, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }

    /**
     * Get the request that failed to dispatch
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

}
