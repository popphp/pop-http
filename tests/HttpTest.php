<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Server\Request;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{

    public function testRemoveHeaders()
    {
        $request = new Request();
        $request->addHeader('Content-Type', 'application/json');
        $request->addHeader('Content-Length', 1000);
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('Content-Length'));
        $this->assertEquals('application/json', $request->getHeaderValue('Content-Type'));
        $this->assertEquals('1000', $request->getHeaderValue('Content-Length'));
        $request->removeHeader('Content-Type');
        $this->assertFalse($request->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('Content-Length'));
        $request->removeHeaders();
        $this->assertFalse($request->hasHeader('Content-Length'));
    }

    public function testBodyContent()
    {
        $request = new Request();
        $request->setBody('Hello World!');
        $this->assertTrue($request->hasBodyContent());
        $this->assertEquals('Hello World!', $request->getBodyContent());
        $request->removeBody();
        $this->assertFalse($request->hasBody());
        $this->assertFalse($request->hasBodyContent());
    }

}