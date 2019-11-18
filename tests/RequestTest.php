<?php

namespace Pop\Http\Test;

use Pop\Http\Request;
use PHPUnit\Framework\TestCase;
use Pop\Mime\Message;

class RequestTest extends TestCase
{

    public function testConstructor()
    {
        $request = new Request();
        $this->assertInstanceOf('Pop\Http\Request', $request);
    }

    public function testParseData()
    {
        $_SERVER['HTTP_HOST']  = 'localhost:8000';
        $_SERVER['SERVER_NAME']  = 'localhost';
        $_SERVER['SERVER_PORT']  = 8000;
        $_SERVER['DOCUMENT_ROOT']  = getcwd();
        $_SERVER['REQUEST_URI']    = '/page';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = 'var=123&foo=bar';
        $_GET = [
            'var' => '123',
            'foo' => 'bar'
        ];

        $request = new Request(null, '/home');
        $this->assertEquals('/page', $request->getRequestUri());
        $this->assertEquals('/home/page', $request->getFullRequestUri());
        $this->assertEquals('/home', $request->getBasePath());
        $this->assertEquals('123', $request->getQuery('var'));
        $this->assertEquals('bar', $request->getQuery('foo'));
        $this->assertEquals('var=123&foo=bar', $request->getRawData());
        $this->assertEquals(2, count($request->getParsedData()));
        $this->assertEquals('bar', $request->getParsedData('foo'));
        $this->assertEquals(2, count($request->getQuery()));
        $this->assertEquals(1, count($request->getSegments()));
        $this->assertEquals('page', $request->getSegment(0));
        $this->assertEquals('http', $request->getScheme());
        $this->assertEquals('localhost', $request->getHost());
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isHead());
        $this->assertFalse($request->isPost());
        $this->assertFalse($request->isPut());
        $this->assertFalse($request->isDelete());
        $this->assertFalse($request->isTrace());
        $this->assertFalse($request->isOptions());
        $this->assertFalse($request->isConnect());
        $this->assertFalse($request->isPatch());
        $this->assertFalse($request->isSecure());
        $this->assertFalse($request->hasFiles());
    }

    public function testGetHostFromServerName()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            unset($_SERVER['HTTP_HOST']);
        }
        $_SERVER['SERVER_NAME']  = 'localhost';

        $request = new Request();
        $this->assertEquals('localhost', $request->getHost());
    }

    public function testParseJsonData()
    {
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_NAME']         = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['DOCUMENT_ROOT']       = getcwd();
        $_SERVER['REQUEST_URI']         = '/page';
        $_SERVER['REQUEST_METHOD']      = 'POST';
        $_SERVER['CONTENT_TYPE']        = 'application/json';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '{"foo" : "bar"}';

        $request = new Request(null, '/home');
        $this->assertEquals('bar', $request->getParsedData()['foo']);
    }

    public function testParseXmlData()
    {
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_NAME']         = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['DOCUMENT_ROOT']       = getcwd();
        $_SERVER['REQUEST_URI']         = '/page';
        $_SERVER['REQUEST_METHOD']      = 'POST';
        $_SERVER['CONTENT_TYPE']        = 'application/xml';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '<root><node><![CDATA[Hello World]]></node></root>';

        $request = new Request(null, '/home');
        $this->assertEquals('Hello World', $request->getParsedData()['node']);
    }

    public function testParseDataWithNoContentType()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_NAME']         = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['DOCUMENT_ROOT']       = getcwd();
        $_SERVER['REQUEST_METHOD']      = 'POST';
        $_POST                          = ["foo" => "bar"];

        $request = new Request(null, '/home');
        $this->assertEquals('bar', $request->getParsedData()['foo']);
    }

    public function testGetFullHost()
    {
        $_SERVER['HTTP_HOST']      = '';
        $_SERVER['SERVER_NAME']    = 'localhost';
        $_SERVER['SERVER_PORT']    = 8000;
        $_SERVER['DOCUMENT_ROOT']  = getcwd();
        $_SERVER['REQUEST_URI']    = '/page';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = 'var=123&foo=bar';
        $_GET = [
            'var' => '123',
            'foo' => 'bar'
        ];

        unset($_SERVER['CONTENT_TYPE']);

        $request = new Request(null, '/home');
        $this->assertEquals('localhost:8000', $request->getFullHost());
    }

    public function testGetFullHostFromHttpHost()
    {
        $_SERVER['HTTP_HOST']      = 'localhost';
        $_SERVER['SERVER_NAME']    = '';
        $_SERVER['SERVER_PORT']    = 8000;

        $request = new Request();
        $this->assertEquals('localhost:8000', $request->getFullHost());
    }

    public function testGetIpFromRemoteAddress()
    {
        $_SERVER['REMOTE_ADDR']    = '127.0.0.1';
        $request = new Request(null, '/home');
        $this->assertEquals('127.0.0.1', $request->getIp());
    }

    public function testGetIpFromClientIp()
    {
        $_SERVER['HTTP_CLIENT_IP'] = '127.0.0.1';
        $request = new Request(null, '/home');
        $this->assertEquals('127.0.0.1', $request->getIp(true));
    }

    public function testGetIpFromForwardedFor()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            unset($_SERVER['HTTP_CLIENT_IP']);
        }
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
        $request = new Request(null, '/home');
        $this->assertEquals('127.0.0.1', $request->getIp(true));
    }

    public function testGetPost()
    {
        $_POST = [
            'var' => '123',
            'foo' => 'bar'
        ];
        $request = new Request();
        $this->assertEquals('123', $request->getPost('var'));
        $this->assertEquals('bar', $request->getPost('foo'));
        $this->assertEquals(2, count($request->getPost()));
    }

    public function testGetFiles()
    {
        $_FILES = [
            'var' => '123',
            'foo' => 'bar'
        ];
        $request = new Request();
        $this->assertEquals('123', $request->getFiles('var'));
        $this->assertEquals('bar', $request->getFiles('foo'));
        $this->assertEquals(2, count($request->getFiles()));
    }

    public function testGetCookie()
    {
        $_COOKIE = [
            'var' => '123',
            'foo' => 'bar'
        ];
        $request = new Request();
        $this->assertEquals('123', $request->getCookie('var'));
        $this->assertEquals('bar', $request->getCookie('foo'));
        $this->assertEquals(2, count($request->getCookie()));
    }

    public function testGetServer()
    {
        $request = new Request();
        $this->assertContains('phpunit', $request->getServer('PHP_SELF'));
        $this->assertGreaterThan(1, count($request->getServer()));
    }

    public function testGetDocRoot()
    {
        $_SERVER['DOCUMENT_ROOT'] = getcwd();
        $request = new Request();
        $this->assertEquals(getcwd(), $request->getDocumentRoot());
    }

    public function testGetEnv()
    {
        $_ENV = [
            'var' => '123',
            'foo' => 'bar'
        ];
        $request = new Request();
        $this->assertEquals('123', $request->getEnv('var'));
        $this->assertEquals('bar', $request->getEnv('foo'));
        $this->assertEquals(2, count($request->getEnv()));
    }

    public function testGetPut()
    {
        $request = new Request();
        $this->assertNull($request->getPut('foo'));
        $this->assertEquals(0, count($request->getPut()));
    }

    public function testGetPatch()
    {
        $request = new Request();
        $this->assertNull($request->getPatch('foo'));
        $this->assertEquals(0, count($request->getPatch()));
    }

    public function testGetDelete()
    {
        $request = new Request();
        $this->assertNull($request->getDelete('foo'));
        $this->assertEquals(0, count($request->getDelete()));
    }

    public function testGetHeaders()
    {
        $request = new Request();
        $this->assertTrue(is_array($request->getHeaders()));
        $this->assertNull($request->getHeader('Content-Type'));
    }

    public function testSetBasePath()
    {
        $request = new Request();
        $request->setBasePath('/home');
        $this->assertEquals('/home', $request->getBasePath());;
    }

    public function testSetBasePathTrailingSlash()
    {
        $request = new Request('/home/', '/home');
        $this->assertEquals('/home', $request->getBasePath());;
    }

    public function testSetBasePathTrailingQuery()
    {
        $request = new Request('/home?', '/home');
        $this->assertEquals('/home', $request->getBasePath());;
    }

    public function testDocRoot()
    {
        $cwd = getcwd();
        $uri = substr($cwd, strrpos($cwd, DIRECTORY_SEPARATOR));
        $_SERVER['DOCUMENT_ROOT']  = substr($cwd, 0, strrpos($cwd, DIRECTORY_SEPARATOR));
        $request = new Request($uri);
        $this->assertEquals('', $request->getRequestUri());
    }

    public function testContentTypeHeader()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'application/json';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'POST';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '{"foo" : "bar"}';

        $request = new Request();
        $this->assertEquals('bar', $request->getParsedData('foo'));
    }

    public function testParsedDataPut()
    {
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['CONTENT_TYPE']        = 'application/json';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '{"foo" : "bar"}';

        $request = new Request();
        $this->assertEquals('bar', $request->getPut('foo'));
    }

    public function testParsedDataPatch()
    {
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['CONTENT_TYPE']        = 'application/json';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PATCH';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '{"foo" : "bar"}';

        $request = new Request();
        $this->assertEquals('bar', $request->getPatch('foo'));
    }

    public function testParsedDataDelete()
    {
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['CONTENT_TYPE']        = 'application/json';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'DELETE';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '{"foo" : "bar"}';

        $request = new Request();
        $this->assertEquals('bar', $request->getDelete('foo'));
    }

    public function testUrlFormParsedDataPut()
    {
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['CONTENT_TYPE']        = 'application/x-www-form-urlencoded';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = http_build_query(['foo' => 'bar']);

        $request = new Request();
        $this->assertEquals('bar', $request->getPut('foo'));
    }

    public function testMultipartFormParsedDataPut()
    {
        $formContents = Message::createForm(['foo' => 'bar']);
        $header       = $formContents->getHeader('Content-Type');
        $formContents->removeHeader('Content-Type');

        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['CONTENT_TYPE']        = 'multipart/form-data; boundary=' . $header->getParameter('boundary');
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = $formContents->render(false);

        $request = new Request();
        $this->assertEquals('bar', $request->getPut('foo'));
    }

    public function testGetMagicMethod()
    {
        $request = new Request();
        $this->assertTrue(is_array($request->get));
        $this->assertTrue(is_array($request->post));
        $this->assertTrue(is_array($request->files));
        $this->assertTrue(is_array($request->put));
        $this->assertTrue(is_array($request->patch));
        $this->assertTrue(is_array($request->delete));
        $this->assertTrue(is_array($request->cookie));
        $this->assertTrue(is_array($request->server));
        $this->assertTrue(is_array($request->env));
        $this->assertTrue(is_array($request->headers));
        $this->assertTrue(is_array($request->parsed));
        $this->assertNotEmpty($request->raw);
        $this->assertNull($request->bad);
    }

}
