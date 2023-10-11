<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Parser;
use Pop\Mime\Message;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{

    public function testParseHeaders()
    {
        $headerString = <<<HEADERS
HTTP/1.1 200 OK
Content-Type: application/json
Authorization: Bearer 123456
HEADERS;
        $headers = Parser::parseHeaders($headerString);
        $this->assertEquals('1.1', $headers['version']);
        $this->assertEquals('200', $headers['code']);
        $this->assertEquals('OK', $headers['message']);
        $this->assertCount(2, $headers['headers']);
    }

    public function testParseBase64Data()
    {
        $str = base64_encode('Hello World');
        $this->assertEquals('Hello World', Parser::parseDataByContentType($str, null, Parser::BASE64));
    }

    public function testParseJsonData()
    {
        $json = json_encode(['foo' => 'bar']);
        $data = Parser::parseDataByContentType($json, 'application/json');
        $this->assertEquals('bar', $data['foo']);
    }

    public function testParseXmlData()
    {
        $xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<root>
    <foo>bar</foo>
    <test><![CDATA[This is a string]]></test>
</root>
XML;

        $data = Parser::parseDataByContentType($xml, 'application/xml');
        $this->assertEquals('bar', $data['foo']);
        $this->assertEquals('This is a string', $data['test']);
    }

    public function testParseUrlFormData()
    {
        $formData = [
            'username' => 'admin',
            'password' => '123456',
            'colors'   => ['Red', 'Green']
        ];

        $content     = Parser::parseDataByContentType(http_build_query($formData), 'application/x-www-form-urlencoded');
        $this->assertEquals('admin', $content['username']);
        $this->assertEquals('123456', $content['password']);
        $this->assertEquals('Red', $content['colors'][0]);
        $this->assertEquals('Green', $content['colors'][1]);
    }

    public function testParseMultipartFormData()
    {
        $formData = [
            'username' => 'admin',
            'password' => '123456',
            'colors'   => ['Red', 'Green']
        ];

        $formMessage = Message::createForm($formData);
        $content     = Parser::parseDataByContentType($formMessage, 'multipart/form-data');
        $this->assertEquals('admin', $content['username']);
        $this->assertEquals('123456', $content['password']);
        $this->assertEquals('Red', $content['colors'][0]);
        $this->assertEquals('Green', $content['colors'][1]);
    }

    public function testParseResponseFromUri()
    {
        $response = Parser::parseResponseFromUri('http://localhost/');
        $this->assertEquals('200', $response->getCode());
        $this->assertEquals('OK', $response->getMessage());
    }

    public function testParseResponseFromString2()
    {
        $headers = <<<HEADERS
HTTP/1.1 200 OK
Content-Type: application/json
Authorization: Bearer 123456
HEADERS;

        $http = <<<HTTP
<html><body><h1>Hello World!</h1></body></html>
HTTP;


        $response = Parser::parseResponseFromString($headers . "\r\n\r\n" . $http);
        $this->assertEquals('200', $response->getCode());
        $this->assertEquals('OK', $response->getMessage());
    }

    public function testParseResponseFromString1()
    {
        $http = str_replace("\n", "\r\n", file_get_contents(__DIR__ . '/tmp/response-encoded.txt'));
        $response = Parser::parseResponseFromString($http);
        $this->assertEquals('200', $response->getCode());
        $this->assertEquals('OK', $response->getMessage());
    }

    public function testEncodeData()
    {
        $base64 = base64_encode('Hello World');
        $quoted = quoted_printable_encode('Hello World');
        $url    = urlencode('Hello World');
        $raw    = rawurlencode('Hello World');
        $gzip   = gzencode('Hello World');

        $this->assertEquals($base64, Parser::encodeData('Hello World', Parser::BASE64));
        $this->assertEquals($quoted, Parser::encodeData('Hello World', Parser::QUOTED));
        $this->assertEquals($url, Parser::encodeData('Hello World', Parser::URL));
        $this->assertEquals($raw, Parser::encodeData('Hello World', Parser::RAW_URL));
        $this->assertEquals($gzip, Parser::encodeData('Hello World', Parser::GZIP));
    }

    public function testDecodeData()
    {
        $this->assertEquals('Hello World', Parser::decodeData(base64_encode('Hello World'), Parser::BASE64));
        $this->assertEquals('Hello World', Parser::decodeData(quoted_printable_encode('Hello World'), Parser::QUOTED));
        $this->assertEquals('Hello World', Parser::decodeData(urlencode('Hello World'), Parser::URL));
        $this->assertEquals('Hello World', Parser::decodeData(rawurlencode('Hello World'), Parser::RAW_URL));
        $this->assertEquals('Hello World', Parser::decodeData(gzencode('Hello World'), Parser::GZIP));
    }

    public function testDecodeChunkedData()
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
        $this->assertStringContainsString('Wik', Parser::decodeData($body, null, true));
    }

}