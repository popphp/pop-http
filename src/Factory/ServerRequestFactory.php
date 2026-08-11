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
namespace Pop\Http\Factory;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Pop\Http\Server\Request;
use Pop\Http\Uri;

/**
 * PSR-17 server request factory class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class ServerRequestFactory implements ServerRequestFactoryInterface
{

    /**
     * Create a new server request - does not read PHP superglobals, per PSR-17
     *
     * @param  string              $method
     * @param  UriInterface|string $uri
     * @param  array               $serverParams
     * @return ServerRequestInterface
     */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        $uriString = ($uri instanceof UriInterface) ? (string)$uri : $uri;
        $request   = new Request(new Uri($uriString), null, null, false, $serverParams);

        return $request->withMethod($method);
    }

}
