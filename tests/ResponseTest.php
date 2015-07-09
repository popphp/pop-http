<?php

namespace Pop\Http\Test;

use Pop\Http\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'text/plain');
        $this->assertInstanceOf('Pop\Http\Response', $response);
        $this->assertEquals('text/plain', $response->getHeader('Content-Type'));
    }

    public function testConstructorBadHeaderCodeException()
    {
        $this->setExpectedException('Pop\Http\Exception');
        $response = new Response(['code' => 700]);
    }

    public function testGetMessageCodeBadCodeException()
    {
        $this->setExpectedException('Pop\Http\Exception');
        $message = Response::getMessageFromCode(700);
    }

    public function testParse()
    {
        $response = Response::parse('http://www.popphp.org/');
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

    public function testParseString()
    {
        $response = Response::parse(file_get_contents(__DIR__ . '/tmp/response.txt'));
        $this->assertEquals(200, $response->getCode());
        $this->assertContains('<html', $response->getBody());
        $this->assertEquals('text/html', $response->getHeader('Content-Type'));
        $this->assertEquals('text/html', $response->getHeaders()['Content-Type']);
        $this->assertContains('ETag: "84f-509414fcd0819"', $response->getHeadersAsString());
    }

    public function testEncodeBodyWithGzip()
    {
        $body = Response::encodeBody('Hello World');
        $this->assertEquals('Hello World', Response::decodeBody($body));
    }

    public function testEncodeBodyWithDeflate()
    {
        $body = Response::encodeBody('Hello World', 'deflate');
        $this->assertEquals('Hello World', Response::decodeBody($body, 'deflate'));
    }

    public function testEncodeBodyNoEncode()
    {
        $body = Response::encodeBody('Hello World', 'none');
        $this->assertEquals('Hello World', Response::decodeBody($body, 'none'));
    }

    public function testDecodeChunkedBody()
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
        $this->assertContains('Wik', Response::decodeChunkedBody($body));
    }

    public function testRedirectHeadersSentException()
    {
        $this->setExpectedException('Pop\Http\Exception');
        Response::redirect('http://www.popphp.org/version');
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
        $this->setExpectedException('Pop\Http\Exception');
        Response::redirect('http://www.popphp.org/version', 700);
    }

    public function testSslHeaders()
    {
        $response = new Response();
        $response->setSslHeaders();
        $this->assertEquals(0, $response->getHeader('Expires'));
        $this->assertEquals('private, must-revalidate', $response->getHeader('Cache-Control'));
        $this->assertEquals('cache', $response->getHeader('Pragma'));
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
        $response->send();
        $result = ob_get_clean();

        $this->assertGreaterThan(1, strlen($result));
    }

}