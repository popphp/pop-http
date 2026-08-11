<?php
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
namespace Pop\Http\Client\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Client middleware interface - a PSR-15-style interceptor for Client's outbound
 * dispatch. Not a literal PSR-15 implementation: PSR-15's real interfaces are typed
 * to ServerRequestInterface (server-side only) and cannot apply to a Client's
 * outbound RequestInterface flow, so this mirrors PSR-15's shape with the request
 * type swapped.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
interface MiddlewareInterface
{

    /**
     * Process the request, optionally passing it (or a modified copy) to $handler
     * to continue the chain, or returning a response directly to short-circuit
     *
     * Typed to the broader PSR-7 ResponseInterface for interface parity with
     * RequestHandlerInterface::handle(), but for use with Pop\Http\Client, the
     * response returned here (whether from $handler->handle() or a short-circuit)
     * must be a Pop\Http\Client\Response (or something that satisfies it) -
     * Client\Response is the concrete type this pipeline's terminal handler and
     * Client's internals require, not an arbitrary ResponseInterface. Client
     * enforces this at the end of the pipeline and throws a Client\Exception with
     * a clear message if it's violated.
     *
     * @param  RequestInterface        $request
     * @param  RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface;

}
