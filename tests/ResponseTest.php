<?php

namespace Pop\Http\Test;

use Pop\Http\Server\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testConstructor()
    {
        $response = new Response();
        $response->addHeader('Content-Type', 'text/plain');
        $this->assertInstanceOf('Pop\Http\Server\Response', $response);
        $this->assertEquals('text/plain', $response->getHeader('Content-Type')->getValue());
    }

    public function testConstructorBadHeaderCodeException()
    {
        $this->expectException('Pop\Http\Server\Exception');
        $response = new Response(['code' => 700]);
    }

    public function testGetMessageCodeBadCodeException()
    {
        $this->expectException('Pop\Http\Server\Exception');
        $message = Response::getMessageFromCode(700);
    }

    public function testParse()
    {
        $response = Response\Parser::parseFromUri('https://www.popphp.org/');
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals(1.1, $response->getVersion());
        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isRedirect());
        $this->assertFalse($response->isError());
        $this->assertFalse($response->isClientError());
        $this->assertFalse($response->isServerError());
        $this->assertEquals('OK', $response->getMessage());
        $this->assertEquals('OK', Response::getMessageFromCode($response->getCode()));
    }

    public function testParseString1()
    {
        $response = Response\Parser::parseFromString(file_get_contents(__DIR__ . '/tmp/response.txt'));
        $this->assertEquals(200, $response->getCode());
        $this->assertContains('<html', $response->getBody()->getContent());
        $this->assertEquals('text/html', $response->getHeader('Content-Type')->getValue());
        $this->assertEquals('text/html', $response->getHeaders()['Content-Type']->getValue());
        $this->assertContains('ETag: "84f-509414fcd0819"', $response->getHeadersAsString());
    }

    public function testParseString2()
    {
        $response = Response\Parser::parseFromString(str_replace("\n", "\r\n", file_get_contents(__DIR__ . '/tmp/response.txt')));
        $this->assertEquals(200, $response->getCode());
        $this->assertContains('<html', $response->getBody()->getContent());
        $this->assertEquals('text/html', $response->getHeader('Content-Type')->getValue());
        $this->assertEquals('text/html', $response->getHeaders()['Content-Type']->getValue());
        $this->assertContains('ETag: "84f-509414fcd0819"', $response->getHeadersAsString());
    }

    public function testParseString3()
    {
        $response = Response\Parser::parseFromString(str_replace("\n", "\r\n", file_get_contents(__DIR__ . '/tmp/response-encoded.txt')));
        $this->assertEquals(200, $response->getCode());
    }

    public function testParseStringException()
    {
        $this->expectException('Pop\Http\Server\Exception');
        $response = Response\Parser::parseFromString('BAD HTTP REQUEST');
    }

    public function testEncodeBodyWithGzip()
    {
        $body = Response\Parser::encodeBody('Hello World');
        $this->assertEquals('Hello World', Response\Parser::decodeBody($body));
    }

    public function testEncodeBodyWithDeflate()
    {
        $body = Response\Parser::encodeBody('Hello World', 'deflate');
        $this->assertEquals('Hello World', Response\Parser::decodeBody($body, 'deflate'));
    }

    public function testEncodeBodyNoEncode()
    {
        $body = Response\Parser::encodeBody('Hello World', 'none');
        $this->assertEquals('Hello World', Response\Parser::decodeBody($body, 'none'));
    }

    public function testDecodeChunkedBody1()
    {
        $body = <<<BODY
4\r\n
Wiki\r\n
5\r\n
pedia\r\n
e\r\n
 in\r\n\r\nchunks.\r\n
0\r\n
\r\n
BODY;
        $this->assertContains('Wik', Response\Parser::decodeChunkedBody($body));
    }

    public function testDecodeChunkedBody2()
    {
        $body = <<<BODY
4\r\n
Wiki\r\n
5\r\n
pedia\r\n
e\r\n
 in\r\n\r\nchunks.\r\n
0\r\n
\r\n
BODY;

        $response = new Response();
        $response->setBody($body);
        $response->addHeaders([
            'Transfer-Encoding' => 'chunked'
        ]);

        $this->assertContains('Wik', $response->decodeBody()->getContent());
    }

    public function testDecodeChunkedBody3()
    {
        $body = <<<BODY
4\r\n
Wiki\r\n
5\r\n
pedia\r\n
e\r\n
 in\r\n\r\nchunks.\r\n
0\r\n
\r\n
BODY;

        $response = new Response();
        $response->addHeaders([
            'Transfer-Encoding' => 'chunked'
        ]);

        $this->assertContains('Wik', $response->decodeBody($body)->getContent());
    }

    public function testRedirectHeadersSentException()
    {
        $this->expectException('Pop\Http\Server\Exception');
        Response::redirect('http://www.popphp.org/version');
    }

    public function testPrepareBody()
    {
        $response = new Response();
        $response->addHeader('Content-Type', 'text/plain');
        $response->setBody('Hello World!');
        $response->prepareBody(true);
        $this->assertTrue($response->hasBody());
        $this->assertTrue($response->hasHeader('Content-Length'));
        $this->assertEquals('Hello World!', $response->body->getContent());
        $this->assertEquals('text/plain', $response->headers['Content-Type']->getValue());
        $this->assertNull($response->foo);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedirect()
    {
        ob_start();
        Response::redirect('http://www.popphp.org/version');
        $result = ob_get_clean();
        $this->assertEquals('', $result);
    }

    /**
     * @runInSeparateProcess
     */
    public function testRedirectBadCodeException()
    {
        $this->expectException('Pop\Http\Server\Exception');
        Response::redirect('http://www.popphp.org/version', 700);
    }

    public function testToString()
    {
        $response = new Response(['headers' => ['Content-Type' => 'text/plain', 'Content-Encoding' => 'deflate']]);
        $response->setBody('Hello World');
        $r = (string)$response;
        $this->assertContains('HTTP/1.1', $r);
        $this->assertContains('200 OK', $r);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSend()
    {
        $response = new Response(['headers' => ['Content-Type' => 'text/plain', 'Content-Encoding' => 'deflate']]);
        $response->setBody('Hello World');

        ob_start();
        $response->send(200, ['X-Header' => 'test']);
        $result = ob_get_clean();

        $this->assertGreaterThan(1, strlen($result));
    }

}
