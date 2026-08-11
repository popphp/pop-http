<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Client\Middleware\CallableMiddleware;
use Pop\Http\Client\Middleware\CallableRequestHandler;
use Pop\Http\Client\Middleware\MiddlewareInterface;
use Pop\Http\Client\Middleware\RequestHandlerInterface;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class CallableMiddlewareTest extends TestCase
{

    public function testCallableRequestHandlerImplementsInterface()
    {
        $handler = new CallableRequestHandler(fn($request) => new Response(['code' => 200]));
        $this->assertInstanceOf(RequestHandlerInterface::class, $handler);
    }

    public function testCallableRequestHandlerDelegatesToClosure()
    {
        $received = null;
        $request  = new Request('http://localhost/');
        $handler  = new CallableRequestHandler(function($req) use (&$received) {
            $received = $req;
            return new Response(['code' => 201]);
        });

        $response = $handler->handle($request);

        $this->assertSame($request, $received);
        $this->assertEquals(201, $response->getCode());
    }

    public function testCallableMiddlewareImplementsInterface()
    {
        $middleware = new CallableMiddleware(fn($request, $handler) => $handler->handle($request));
        $this->assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    public function testCallableMiddlewareDelegatesToClosure()
    {
        $request  = new Request('http://localhost/');
        $terminal = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $receivedRequest = null;
        $receivedHandler = null;
        $middleware = new CallableMiddleware(function($req, $handler) use (&$receivedRequest, &$receivedHandler) {
            $receivedRequest = $req;
            $receivedHandler = $handler;
            return $handler->handle($req);
        });

        $response = $middleware->process($request, $terminal);

        $this->assertSame($request, $receivedRequest);
        $this->assertSame($terminal, $receivedHandler);
        $this->assertEquals(200, $response->getCode());
    }

}
