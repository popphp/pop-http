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
namespace Pop\Http\Client\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Builds and runs a middleware chain. Registration order is wrap order: the
 * first-registered middleware ends up outermost (runs first going in, last
 * coming out) - the standard onion convention.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Pipeline
{

    /**
     * Registered middleware, in registration order
     * @var array
     */
    protected array $middleware;

    /**
     * Constructor
     *
     * @param  array $middleware  MiddlewareInterface instances, in registration order
     * @throws \InvalidArgumentException if any element is not a MiddlewareInterface
     */
    public function __construct(array $middleware = [])
    {
        foreach ($middleware as $i => $entry) {
            if (!($entry instanceof MiddlewareInterface)) {
                throw new \InvalidArgumentException(
                    'Error: Middleware at index ' . $i . ' must be an instance of ' .
                    MiddlewareInterface::class . ', got ' .
                    (is_object($entry) ? get_class($entry) : gettype($entry)) . '.'
                );
            }
        }

        $this->middleware = $middleware;
    }

    /**
     * Build the chain (terminal innermost, first-registered middleware outermost)
     * and run it against the given request
     *
     * @param  RequestInterface $request
     * @param  callable         $terminal  callable(RequestInterface $request): ResponseInterface
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, callable $terminal): ResponseInterface
    {
        $handler = new CallableRequestHandler($terminal);

        foreach (array_reverse($this->middleware) as $middleware) {
            $handler = new CallableRequestHandler(
                fn(RequestInterface $req) => $middleware->process($req, $handler)
            );
        }

        return $handler->handle($request);
    }

}
