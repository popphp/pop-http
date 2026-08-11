<?php

namespace Pop\Http\Test\Factory;

use Pop\Http\Factory\ServerRequestFactory;
use PHPUnit\Framework\TestCase;

class ServerRequestFactoryTest extends TestCase
{

    public function testImplementsServerRequestFactoryInterface()
    {
        $this->assertInstanceOf('Psr\Http\Message\ServerRequestFactoryInterface', new ServerRequestFactory());
    }

    public function testCreateServerRequestDoesNotReadSuperglobals()
    {
        $request = (new ServerRequestFactory())->createServerRequest('POST', 'http://localhost/foo', ['FOO' => 'bar']);

        $this->assertInstanceOf('Psr\Http\Message\ServerRequestInterface', $request);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(['FOO' => 'bar'], $request->getServerParams());
        $this->assertEquals([], $request->getCookieParams());
        $this->assertEquals([], $request->getQueryParams());
    }

}
