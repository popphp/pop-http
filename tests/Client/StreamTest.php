<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Request;
use Pop\Http\Client\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{

    public function testConstructor()
    {
        $client = new Stream('https://www.popphp.org/version');
        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
        $this->assertEquals('GET', $client->getContextOption('http')['method']);
        $this->assertEquals('r', $client->getMode());
        $client->send();
        $this->assertTrue(is_resource($client->getContext()));
        $this->assertTrue(is_resource($client->stream()));
        $this->assertTrue(is_array($client->getContextOptions()));
        $this->assertTrue(is_array($client->getContextParams()));
        $this->assertTrue($client->hasContextOption('http'));
        $client->disconnect();
        $this->assertFalse($client->hasResource());
    }

    public function testGetWithFields()
    {
        $client = new Stream('https://www.popphp.org/version');
        $client->setField('foo', 'bar');
        $client->open();

        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
    }

    public function testPostWithFields()
    {
        $client = new Stream('https://www.popphp.org/version', 'POST');
        $client->setField('foo', 'bar');
        $client->send();

        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
    }

    public function testAddContextOption()
    {
        $client = new Stream('https://www.popphp.org/version');
        $client->addContextOption('foo', ['bar' => 'baz']);
        $this->assertTrue(isset($client->getContextOption('foo')['bar']));
    }

    public function testSetContextOptions()
    {
        $client = new Stream('https://www.popphp.org/version');
        $client->setContextOptions([
            'http' => [
                'header' => 'Content-Type: text/plain'
            ]
        ]);
        $this->assertTrue(isset($client->getContextOption('http')['header']));
    }

    public function testAddContextParam()
    {
        $client = new Stream('https://www.popphp.org/version');
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

    public function testCreateUrlForm()
    {
        $client = new Stream('https://www.popphp.org/version', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->addContextOption('http', ['header' => 'Content-Type: text/plain']);
        $client->createUrlEncodedForm();
        $client->open();
        $this->assertTrue($client->isUrlEncodedForm());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertEquals('application/x-www-form-urlencoded', $client->getRequestHeader('Content-Type')->getValue());

    }

    public function testCreateMultipartForm()
    {
        $client = new Stream('https://www.popphp.org/version', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->createMultipartForm();
        $client->open();
        $this->assertTrue($client->request()->hasField('username'));
        $this->assertTrue($client->isMultipartForm());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertContains('multipart/form-data', $client->getRequestHeader('Content-Type')->getValue());
    }

}
