<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Auth;
use Pop\Http\Client\Request;
use Pop\Http\Client\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{

    public function testConstructor()
    {
        $client = new Stream('http://localhost/', 'GET', 'r', ['http' => ['user_agent' => 'Mozilla']], ['foo' => 'bar']);
        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
        $this->assertEquals('GET', $client->getContextOption('http')['method']);
        $this->assertEquals('r', $client->getMode());
        $client->send();
        $this->assertTrue(is_resource($client->getContext()));
        $this->assertTrue(is_resource($client->stream()));
        $this->assertTrue(is_array($client->getContextOptions()));
        $this->assertTrue(is_array($client->getContextParams()));
        $this->assertTrue($client->hasContextOption('http'));
        $this->assertTrue($client->hasContextParam('foo'));
        $client->disconnect();
        $this->assertFalse($client->hasResource());
    }
    public function testCreateContext()
    {
        $client = new Stream(null, null);
        $client->createContext();
        $this->assertTrue(is_resource($client->getContext()));
    }

    public function testGetWithFields()
    {
        $client = new Stream('http://localhost/');
        $client->setField('foo', 'bar');
        $client->open();

        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
    }

    public function testPostWithFields()
    {
        $client = new Stream('http://localhost/', 'POST');
        $client->setField('foo', 'bar');
        $client->send();

        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
    }

    public function testAddContextOption()
    {
        $client = new Stream('http://localhost/');
        $client->addContextOption('foo', ['bar' => 'baz']);
        $this->assertTrue(isset($client->getContextOption('foo')['bar']));
    }

    public function testSetContextOptions()
    {
        $client = new Stream('http://localhost/');
        $client->setContextOptions([
            'http' => [
                'header' => 'Content-Type: text/plain'
            ]
        ]);
        $this->assertTrue(isset($client->getContextOption('http')['header']));
    }

    public function testAddContextParam()
    {
        $client = new Stream('http://localhost/');
        $client->setContextParams(['foo' => 'bar']);
        $this->assertTrue($client->hasContextParam('foo'));
        $this->assertEquals('bar', $client->getContextParam('foo'));
    }

    public function testThrowError()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $client = new Stream('http://www.popphp.org/version');
        $client->throwError('Error: Some Error');
    }

    public function testCreateAsJson()
    {
        $client = new Stream('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->setAuth(Auth::createBasic('username', 'password'));
        $client->createAsJson();
        $client->open();
        $this->assertTrue($client->isJson());
        $this->assertEquals('application/json', $client->request()->getFormType());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertEquals('application/json', $client->getRequestHeader('Content-Type')->getValue(0));
    }

    public function testCreateAsJson2()
    {
        $data = [
            'foo' => 'bar',
            'var' => 123
        ];
        $client = new Stream('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->createAsJson();
        $client->getRequest()->setBody(json_encode($data, JSON_PRETTY_PRINT));
        $client->open();
        $this->assertTrue($client->isJson());
        $this->assertEquals('application/json', $client->request()->getFormType());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertEquals('application/json', $client->getRequestHeader('Content-Type')->getValue(0));
    }

    public function testCreateAsXml()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<data>
    <foo>bar</foo>
    <var>123</var>
</data>
XML;
        $client = new Stream('http://localhost/', 'POST');
        $client->createAsXml();
        $client->getRequest()->setBody($xml);
        $client->open();
        $this->assertTrue($client->isXml());
        $this->assertEquals('application/xml', $client->request()->getFormType());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertEquals('application/xml', $client->getRequestHeader('Content-Type')->getValue(0));
    }

    public function testCreateUrlForm()
    {
        $client = new Stream('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->addContextOption('http', ['header' => 'Content-Type: text/plain']);
        $client->createUrlEncodedForm();
        $client->open();
        $this->assertTrue($client->isUrlEncodedForm());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertEquals('application/x-www-form-urlencoded', $client->getRequestHeader('Content-Type')->getValue(0));
    }

    public function testCreateMultipartForm()
    {
        $client = new Stream('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->createMultipartForm();
        $client->open();
        $this->assertTrue($client->request()->hasField('username'));
        $this->assertTrue($client->isMultipartForm());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertStringContainsString('multipart/form-data', $client->getRequestHeader('Content-Type')->getValue(0));
    }

    public function testReset()
    {
        $client = new Stream('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->createMultipartForm();

        $client->reset();

        $this->assertNull($client->getContext());
    }

}
