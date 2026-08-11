<?php

namespace Pop\Http\Test\Factory;

use Pop\Http\Factory\RequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{

    public function testImplementsRequestFactoryInterface()
    {
        $this->assertInstanceOf('Psr\Http\Message\RequestFactoryInterface', new RequestFactory());
    }

    public function testCreateRequest()
    {
        $request = (new RequestFactory())->createRequest('POST', 'http://localhost/foo');
        $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $request);
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('/foo', $request->getUri()->getPath());
    }

}
