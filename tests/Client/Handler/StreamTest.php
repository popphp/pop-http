<?php

namespace Pop\Http\Test\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\Stream;
use PHPUnit\Framework\TestCase;
use Pop\Mime\Part\Header;

class StreamTest extends TestCase
{

    public function testConstructor()
    {
        $stream = new Stream('r', ['http' =>
            [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
            ]
        ], ['foo' => 'bar']);
        $this->assertInstanceOf('Pop\Http\Client\Handler\Stream', $stream);
        $this->assertTrue($stream->hasContextOption('http'));
        $this->assertTrue($stream->hasContextParam('foo'));
        $this->assertEquals('r', $stream->getMode());
        $this->assertEquals('POST', $stream->getContextOption('http')['method']);
        $this->assertEquals('bar', $stream->getContextParam('foo'));
        $this->assertTrue(is_array($stream->getContextOptions()));
        $this->assertTrue(is_array($stream->getContextParams()));
        $this->assertNull($stream->getContext());
        $stream->createContext();
        $this->assertNotNull($stream->getContext());
    }

    public function testCreate()
    {
        $stream = Stream::create('POST', 'r', ['http' =>
            [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
            ]
        ], ['foo' => 'bar']);
        $this->assertInstanceOf('Pop\Http\Client\Handler\Stream', $stream);
    }

    public function testCreateContext()
    {
        $stream = new Stream();
        $this->assertNull($stream->getContext());
        $stream->createContext();
        $this->assertNotNull($stream->getContext());
    }

    public function testAddContextOption()
    {
        $stream = new Stream('r', ['http' => ['method' => 'POST']]);
        $this->assertEquals('POST', $stream->getContextOption('http')['method']);
        $stream->addContextOption('http', ['header' => 'Content-type: application/x-www-form-urlencoded']);
        $this->assertEquals('Content-type: application/x-www-form-urlencoded', $stream->getContextOption('http')['header']);
        $this->assertNull($stream->getContext());
        $stream->createContext();
        $this->assertNotNull($stream->getContext());
    }

    public function testVerifyPeer()
    {
        $stream = new Stream();
        $stream->setVerifyPeer(true);
        $this->assertTrue($stream->isVerifyPeer());
    }

    public function testAllowSelfSigned()
    {
        $stream = new Stream();
        $stream->allowSelfSigned(false);
        $this->assertFalse($stream->isAllowSelfSigned());
    }

    public function testPrepareWithHeaders1()
    {
        $stream  = new Stream();
        $stream->setContextOptions(['http' =>
            [
                'method' => 'POST',
                'header' => 'Authorization: Bearer 1234567890',
            ]
        ]);
        $request = new Request('http://localhost/', 'POST');
        $request->addHeader('Content-Type', 'application/json');

        $stream->prepare($request, null, false);
        $headers = $stream->getContextOption('http')['header'];
        $this->assertEquals("Authorization: Bearer 1234567890\r\nContent-Type: application/json\r\n", $headers);
        $this->assertTrue($stream->hasUri());
        $this->assertEquals('http://localhost/', $stream->getUri());
        $this->assertInstanceOf('Pop\Http\Uri', $stream->getUriObject());
    }

    public function testPrepareWithJsonData()
    {
        $stream  = new Stream();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::JSON);
        $request->setData(['foo' => 'bar']);

        $client  = new Client($request, $stream);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals("{\n    \"foo\": \"bar\"\n}", $stream->getContextOption('http')['content']);
    }

    public function testPrepareWithPostUrlFormData()
    {
        $stream  = new Stream();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::URLENCODED);
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $stream);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $stream->getContextOption('http')['content']);
    }

    public function testPrepareWithPostMultipartFormData()
    {
        $stream  = new Stream();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::MULTIPART);
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $stream);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertTrue(str_contains($stream->getContextOption('http')['content'], "Content-Disposition: form-data; name=foo\r\n\r\nbar\r\n"));
    }

    public function testPrepareWithPostFormData()
    {
        $stream  = new Stream();
        $request = new Request('http://localhost/', 'POST');
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $stream);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $stream->getContextOption('http')['content']);
    }

    public function testPrepareWithBodyData()
    {
        $stream  = new Stream();
        $request = new Request('http://localhost/', 'POST');
        $request->setBody('foo=bar');
        $client  = new Client($request, $stream);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $stream->getContextOption('http')['content']);
    }

    public function testPrepareClear()
    {
        $stream  = new Stream('r', ['http' => ['header' => 'X-Header: test']]);
        $request = new Request('http://localhost/', 'POST');
        $this->assertEquals('X-Header: test', $stream->getContextOption('http')['header']);
        $client  = new Client($request, $stream);
        $client->getHandler()->prepare($client->getRequest(), null, true);
        $this->assertNull($stream->getContextOption('http')['header']);
    }

    public function testSendException()
    {
        $this->expectException('Pop\Http\Client\Handler\Exception');
        $stream = new Stream();
        $stream->send();
    }

    public function testReset()
    {
        $stream = new Stream('r', ['http' =>
            [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
            ]
        ], ['foo' => 'bar']);
        $stream->reset();
        $this->assertNull($stream->getContext());
    }

    public function testDisconnect()
    {
        $stream = new Stream('r', ['http' =>
            [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
            ]
        ], ['foo' => 'bar']);
        $stream->disconnect();
        $this->assertNull($stream->stream());
    }

}
