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
        // Multipart content is now built by Pop\Http\Body\Multipart, which quotes
        // the field name per RFC 7578, rather than the old Message::createForm() output.
        $this->assertTrue(str_contains($stream->getContextOption('http')['content'], "Content-Disposition: form-data; name=\"foo\"\r\n\r\nbar\r\n"));
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

    public function testMultipartContentIsRenderedString()
    {
        $file = sys_get_temp_dir() . '/pop-http-stream-test-' . uniqid() . '.txt';
        file_put_contents($file, 'streamed content');

        $request = new \Pop\Http\Client\Request('http://localhost/', 'POST');
        $request->setData(['upload' => ['filename' => $file, 'contentType' => 'text/plain']])
            ->createAsMultipart();

        $stream = new Stream();
        $stream->prepare($request);

        $content = $stream->getContextOptions()['http']['content'];
        $this->assertIsString($content);
        $this->assertStringContainsString('Content-Disposition: form-data; name="upload"', $content);
        $this->assertStringContainsString('streamed content', $content);

        unlink($file);
    }

    public function testMultipartContentTypeBoundaryMatchesRenderedContent()
    {
        $file = sys_get_temp_dir() . '/pop-http-stream-test-' . uniqid() . '.txt';
        file_put_contents($file, 'streamed content');

        // Note: createAsMultipart() is called before setData() here (matching the documented
        // usage order in README.md), unlike the setData()->createAsMultipart() order used in
        // testMultipartContentIsRenderedString() above. That reversed order exposes a separate,
        // pre-existing bug (Data::setData() eagerly prepares the request at construction time,
        // before createAsMultipart() has updated the request type, and the prepared flag it sets
        // then blocks the necessary re-preparation later) which is out of scope for this task and
        // not specific to the Stream handler - see the task-15 report for details.
        $request = new \Pop\Http\Client\Request('http://localhost/', 'POST');
        $request->createAsMultipart()
            ->setData(['upload' => ['filename' => $file, 'contentType' => 'text/plain']]);

        $stream = new Stream();
        $stream->prepare($request);

        $contentType = $request->getHeaderValueAsString('Content-Type');
        $this->assertNotNull($contentType);
        preg_match('/boundary=(\S+)/', $contentType, $matches);
        $this->assertNotEmpty($matches);
        $boundary = $matches[1];

        $content = $stream->getContextOptions()['http']['content'];
        $this->assertStringContainsString('--' . $boundary, $content);

        unlink($file);
    }

    public function testMultipartContentIsRenderedExactlyOnceAndIsBoundaryMatched()
    {
        // Task 15 regression guard, re-asserted after multipart preparation was made lazy:
        // Data::prepareMultipart() no longer renders anything, so Stream::prepare() is the
        // one and only place the multipart string gets built.
        $file = sys_get_temp_dir() . '/pop-http-stream-lazy-' . uniqid() . '.txt';
        file_put_contents($file, 'streamed content');

        $request = new \Pop\Http\Client\Request('http://localhost/', 'POST');
        $request->createAsMultipart()
            ->setData(['username' => 'admin', 'upload' => ['filename' => $file, 'contentType' => 'text/plain']]);

        // Data never rendered the body itself
        $this->assertEmpty($request->getData()->getDataContent());

        $stream = new Stream();
        $stream->prepare($request);

        $contentType = $request->getHeaderValueAsString('Content-Type');
        preg_match('/boundary=(\S+)/', $contentType, $matches);
        $boundary = $matches[1];

        $content = $stream->getContextOptions()['http']['content'];
        $this->assertIsString($content);
        $this->assertStringStartsWith('--' . $boundary . "\r\n", $content);
        $this->assertStringEndsWith('--' . $boundary . "--\r\n", $content);
        $this->assertStringContainsString('name="username"', $content);
        $this->assertStringContainsString('streamed content', $content);
        // Exactly one rendering: 2 part delimiters + 1 closing delimiter, no duplicated parts
        $this->assertEquals(3, substr_count($content, '--' . $boundary));
        $this->assertEquals(1, substr_count($content, 'streamed content'));

        // PHP's http:// stream wrapper derives Content-Length from 'content' itself
        $this->assertFalse($request->hasHeader('Content-Length'));

        unlink($file);
    }

    public function testReusedHandlerClearsStaleContent()
    {
        // Regression test: a stale 'content' left over from a prior prepare() would be sent
        // as the body of a subsequent body-less request.
        $stream = new Stream();

        $stream->prepare(new Request('http://localhost/', 'POST', ['foo' => 'bar']));
        $this->assertEquals('foo=bar', $stream->getContextOptions()['http']['content']);

        $stream->prepare(new Request('http://localhost/other', 'GET'));

        $this->assertArrayNotHasKey('content', $stream->getContextOptions()['http']);
        $this->assertEquals('GET', $stream->getContextOptions()['http']['method']);
    }

    public function testPrepareWithClearFalsePreservesPreDefinedContent()
    {
        // Regression test: the stale-body clearing must respect $clear the same way the header
        // handling does - with $clear = false a body pre-defined on the handler itself (as
        // Parser::parseResponseFromUri() allows via its $options argument) has to survive.
        $stream = new Stream();
        $stream->setContextOptions(['http' => ['method' => 'POST', 'content' => 'pre-defined body']]);

        $stream->prepare(new Request('http://localhost/', 'POST'), null, false);

        $this->assertEquals('pre-defined body', $stream->getContextOptions()['http']['content']);
    }

}
