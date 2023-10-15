<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    public function testConstructor()
    {
        $request = new Request('http://localhost/', 'GET', ['foo' => 'bar'], Request::JSON);
        $this->assertInstanceOf('Pop\Http\Client\Request', $request);
        $this->assertInstanceOf('Pop\Http\Client\Data', $request->getData());
    }

    public function testGetterAndSetter()
    {
        $request = Request::create('http://localhost/');
        $this->assertTrue($request->hasUri());
        $this->assertInstanceOf('Pop\Http\Uri', $request->getUri());
        $this->assertEquals('http://localhost/', $request->getUriAsString());
    }

    public function testMethod()
    {
        $request = new Request('http://localhost/', 'POST');
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testMethodException()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $request = new Request('http://localhost/', 'BAD');
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testRequestType()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'text/html');
        $request->setRequestType(Request::JSON);
        $this->assertEquals(Request::JSON, $request->getRequestType());
        $this->assertTrue($request->isJson());
        $request->setRequestType(Request::XML);
        $this->assertEquals(Request::XML, $request->getRequestType());
        $this->assertTrue($request->isXml());
        $request->setRequestType(Request::URLFORM);
        $this->assertEquals(Request::URLFORM, $request->getRequestType());
        $this->assertTrue($request->isUrlEncoded());
        $request->setRequestType(Request::MULTIPART);
        $this->assertEquals(Request::MULTIPART, $request->getRequestType());
        $this->assertTrue($request->isMultipart());
    }

    public function testBodyContent()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->setBody('This is a text body');
        $this->assertEquals('This is a text body', $request->getBodyContent());
        $this->assertEquals(19, $request->getBodyContentLength());
    }

    public function testBodyContentZero()
    {
        $request = new Request('http://localhost/', 'POST');
        $this->assertEquals(0, $request->getBodyContentLength());
    }

    public function testMagicMethod()
    {
        $request = new Request('http://localhost/');
        $this->assertTrue($request->isGet());
    }

    public function testMagicMethodException1()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $request = new Request('http://localhost/');
        $this->assertTrue($request->isBad());
    }

    public function testMagicMethodException2()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $request = new Request('http://localhost/');
        $this->assertTrue($request->badMethod());
    }

}