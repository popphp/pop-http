<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Parser;
use Pop\Mime\Message;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{

    public function testParseDataByContentType1()
    {
        $str = base64_encode('Hello World');
        $this->assertEquals('Hello World', Parser::parseDataByContentType($str, null, Parser::BASE64));
    }

    public function testParseDataByContentType2()
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
        $this->assertContains('Wik', Parser::decodeData($body, null, true));
    }

}