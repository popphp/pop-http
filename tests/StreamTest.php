<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $client = new Stream('http://www.popphp.org/version', 'r', ['http'=> ['method' => 'GET']]);
        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
        $this->assertFalse($client->isPost());
        $this->assertEquals('GET', $client->getContextOption('http')['method']);
        $this->assertEquals('r', $client->getMode());
        $client->send();
        $this->assertTrue(is_resource($client->getContext()));
        $this->assertTrue(is_resource($client->stream()));
        $this->assertTrue(is_array($client->getContextOptions()));
        $this->assertTrue(is_array($client->getContextParams()));
        $client->disconnect();
        $this->assertFalse($client->hasResource());
    }

    public function testSetPost()
    {
        $client = new Stream('http://www.popphp.org/version');
        $client->setPost();
        $this->assertTrue($client->hasContextOption('http'));
    }

    public function testGetWithFields()
    {
        $client = new Stream('http://www.popphp.org/version');
        $client->setField('foo', 'bar');
        $client->open();
    }

    public function testPostWithFields()
    {
        $client = new Stream('http://www.popphp.org/version', 'r', ['http'=> ['method' => 'POST']]);
        $client->setField('foo', 'bar');
        $client->send();
    }

    public function testAddContextParam()
    {
        $client = new Stream('http://www.popphp.org/version');
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

}
