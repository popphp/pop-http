<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Parser;
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

        $boundary = 'PARSERTESTBOUNDARY';
        $body     = \Pop\Http\Body\Multipart::build($formData, $boundary);
        $content  = Parser::parseDataByContentType(
            $body->getContent(), 'multipart/form-data; boundary=' . $boundary
        );
        $this->assertEquals('admin', $content['username']);
        $this->assertEquals('123456', $content['password']);
        $this->assertEquals('Red', $content['colors'][0]);
        $this->assertEquals('Green', $content['colors'][1]);
    }

    public function testParseMediaType()
    {
        $result = Parser::parseMediaType('application/vnd.api+json; charset=utf-8');
        $this->assertEquals('application', $result['type']);
        $this->assertEquals('vnd.api', $result['subtype']);
        $this->assertEquals('json', $result['suffix']);
        $this->assertEquals('utf-8', $result['params']['charset']);
    }

    public function testParseMediaTypeWithNoSuffix()
    {
        $result = Parser::parseMediaType('text/plain');
        $this->assertEquals('text', $result['type']);
        $this->assertEquals('plain', $result['subtype']);
        $this->assertNull($result['suffix']);
    }

    public function testXhtmlXmlDoesNotRouteThroughXmlParser()
    {
        // application/xhtml+xml is XHTML, not generic XML, and should not be
        // silently routed through the generic <root><foo>bar</foo></root>-style
        // SimpleXML parse path the way application/xml is.
        $content = '<!DOCTYPE html><html><body>Not parseable as the generic XML shape</body></html>';
        $result  = Parser::parseDataByContentType($content, 'application/xhtml+xml');
        $this->assertEquals($content, $result);
    }

    public function testMalformedXmlThrowsException()
    {
        $this->expectException(\Pop\Http\Exception::class);
        Parser::parseDataByContentType('<not><valid</xml>', 'application/xml');
    }

    public function testParseJsonWithNonUtf8Charset()
    {
        $json = json_encode(['foo' => 'bar']);
        $iso  = mb_convert_encoding($json, 'ISO-8859-1', 'UTF-8');

        $result = Parser::parseDataByContentType($iso, 'application/json; charset=iso-8859-1');
        $this->assertEquals('bar', $result['foo']);
    }

    public function testParseMultipartDelegatesToBodyMultipart()
    {
        $body = \Pop\Http\Body\Multipart::build(['username' => 'admin'], 'DELEGATEBOUNDARY');
        $result = Parser::parseDataByContentType(
            $body->getContent(), 'multipart/form-data; boundary=DELEGATEBOUNDARY'
        );
        $this->assertEquals('admin', $result['username']);
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

    public function testDecodeGzipWithExtraHeaderFields()
    {
        // gzencode() with a non-empty $filename argument produces a gzip header
        // with the FNAME flag set, adding bytes beyond the fixed 10-byte minimum
        // header. The old gzinflate(substr($data, 10)) implementation assumed
        // exactly 10 bytes and would corrupt this.
        $original   = 'Hello World, this is gzip test content.';
        $compressed = gzencode($original, 9, FORCE_GZIP);
        // Re-encode with a filename to force extra header bytes:
        $gzHeaderWithName = "\x1f\x8b\x08\x08" . pack('V', time()) . "\x00\x03" . "myfile.txt\x00" .
            substr($compressed, 10);

        $this->assertEquals($original, Parser::decodeData($gzHeaderWithName, 'GZIP'));
    }

    public function testDecodeStandardGzip()
    {
        $original   = 'Standard gzip content';
        $compressed = gzencode($original);

        $this->assertEquals($original, Parser::decodeData($compressed, 'GZIP'));
    }

}