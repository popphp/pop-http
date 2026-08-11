<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Client\Middleware\CallableMiddleware;
use Pop\Http\Client\Middleware\Pipeline;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class PipelineTest extends TestCase
{

    public function testProcessWithNoMiddlewareCallsTerminalDirectly()
    {
        $pipeline = new Pipeline([]);
        $request  = new Request('http://localhost/');

        $response = $pipeline->process($request, fn($req) => new Response(['code' => 200]));

        $this->assertEquals(200, $response->getCode());
    }

    public function testMiddlewareRunsInRegistrationOrderOuterToInner()
    {
        $order = [];

        $first = new CallableMiddleware(function($request, $handler) use (&$order) {
            $order[] = 'first-before';
            $response = $handler->handle($request);
            $order[] = 'first-after';
            return $response;
        });

        $second = new CallableMiddleware(function($request, $handler) use (&$order) {
            $order[] = 'second-before';
            $response = $handler->handle($request);
            $order[] = 'second-after';
            return $response;
        });

        $pipeline = new Pipeline([$first, $second]);
        $request  = new Request('http://localhost/');

        $pipeline->process($request, function($req) use (&$order) {
            $order[] = 'terminal';
            return new Response(['code' => 200]);
        });

        $this->assertEquals(
            ['first-before', 'second-before', 'terminal', 'second-after', 'first-after'],
            $order
        );
    }

    public function testMiddlewareCanShortCircuitWithoutCallingHandler()
    {
        $terminalCalled = false;

        $shortCircuit = new CallableMiddleware(function($request, $handler) {
            return new Response(['code' => 304]);
        });

        $pipeline = new Pipeline([$shortCircuit]);
        $request  = new Request('http://localhost/');

        $response = $pipeline->process($request, function($req) use (&$terminalCalled) {
            $terminalCalled = true;
            return new Response(['code' => 200]);
        });

        $this->assertEquals(304, $response->getCode());
        $this->assertFalse($terminalCalled);
    }

    public function testMiddlewareCanMutateRequestBeforePassingToHandler()
    {
        $receivedHeader = null;

        $inject = new CallableMiddleware(function($request, $handler) {
            return $handler->handle($request->withHeader('X-Injected', 'yes'));
        });

        $pipeline = new Pipeline([$inject]);
        $request  = new Request('http://localhost/');

        $pipeline->process($request, function($req) use (&$receivedHeader) {
            $receivedHeader = $req->getHeaderLine('X-Injected');
            return new Response(['code' => 200]);
        });

        $this->assertEquals('yes', $receivedHeader);
    }

    public function testExceptionFromMiddlewarePropagates()
    {
        $throwing = new CallableMiddleware(function($request, $handler) {
            throw new \RuntimeException('Simulated middleware failure.');
        });

        $pipeline = new Pipeline([$throwing]);
        $request  = new Request('http://localhost/');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated middleware failure.');
        $pipeline->process($request, fn($req) => new Response(['code' => 200]));
    }

    public function testConstructorRejectsNonMiddlewareElement()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Pipeline(['not a middleware']);
    }

    public function testExceptionFromTerminalPropagates()
    {
        $pipeline = new Pipeline([]);
        $request  = new Request('http://localhost/');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated terminal failure.');
        $pipeline->process($request, function($req) {
            throw new \RuntimeException('Simulated terminal failure.');
        });
    }

}
