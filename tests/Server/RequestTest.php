<?php

namespace Pop\Http\Test\Server;

use Pop\Filter\Filter;
use Pop\Http\Auth;
use Pop\Http\Client\Data;
use Pop\Http\Server\Request;
use PHPUnit\Framework\TestCase;
use Pop\Http\Uri;
use Pop\Mime\Message;

class RequestTest extends TestCase
{

    public function testConstructor()
    {
        $request1 = new Request();
        $this->assertInstanceOf('Pop\Http\Server\Request', $request1);
        $request2 = Request::create();
        $this->assertInstanceOf('Pop\Http\Server\Request', $request2);
    }

    public function testAuth1()
    {
        $request = new Request();
        $request->setAuth(Auth::createBearer('123456'));
        $this->assertTrue($request->hasAuth());
        $this->assertInstanceOf('Pop\Http\Auth', $request->getAuth());
    }

    public function testAuth2()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer 123456';
        $request = new Request();
        $this->assertTrue($request->hasAuth());
        $this->assertInstanceOf('Pop\Http\Auth', $request->getAuth());
        $this->assertEquals('Authorization: Bearer 123456', $request->getAuth()->getAuthHeaderAsString());
    }

    public function testUriString()
    {
        $request = new Request(new Uri('http://localhost/'));
        $this->assertEquals('/', $request->getUriString());
    }

    public function testSetBasePath()
    {
        $request = new Request(new Uri('/'));
        $request->setBasePath('/my-folder');
        $this->assertEquals('/my-folder', $request->getBasePath());
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

        $request = new Request(new Uri('/page', '/home'), ['strip_tags', 'htmlentities']);
        $this->assertInstanceOf('Pop\Http\Uri', $request->getUri());
        $this->assertEquals('/home/page', $request->getFullUriString());
        $this->assertEquals('/home', $request->getBasePath());
        $this->assertEquals('123', $request->getQuery('var'));
        $this->assertEquals('bar', $request->getQuery('foo'));
        // A GET request carries its data in the query string, so that IS its raw data
        // (php://input is empty for GET, so QUERY_STRING is the fallback).
        $this->assertEquals('var=123&foo=bar', $request->getRawData());
        $this->assertEquals(2, count($request->getParsedData()));
        $this->assertEquals('bar', $request->getParsedData('foo'));
        $this->assertEquals(2, count($request->getQuery()));
        $this->assertEquals(1, count($request->getSegments()));
        $this->assertEquals('page', $request->getSegment(0));
        $this->assertEquals('http', $request->getScheme());
        $this->assertEquals('localhost', $request->getHost());
        $this->assertEquals('GET', $request->getMethod());
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
        // queryData is no longer independently populated from $_SERVER['QUERY_STRING']
        // now that the redundant re-parse-and-reconcile pass is removed; getQuery()
        // (asserted above) is the source of truth for GET data.
        $this->assertFalse($request->hasQueryData());
        $this->assertTrue($request->hasParsedData());
        $this->assertTrue($request->hasData());
    }

    public function testGetRawDataReturnsQueryStringForGetRequest()
    {
        // Regression test: a GET request's raw data is its query string. php://input is empty
        // for GET, so QUERY_STRING is the documented fallback (README.md).
        $_SERVER['HTTP_HOST']      = 'localhost:8000';
        $_SERVER['SERVER_NAME']    = 'localhost';
        $_SERVER['SERVER_PORT']    = 8000;
        $_SERVER['DOCUMENT_ROOT']  = getcwd();
        $_SERVER['REQUEST_URI']    = '/page';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = 'alpha=1&beta=two';
        unset($_SERVER['X_POP_HTTP_RAW_DATA']);
        $_GET = ['alpha' => '1', 'beta' => 'two'];

        $request = new Request();

        $this->assertEquals('alpha=1&beta=two', $request->getRawData());
        $this->assertTrue($request->hasRawData());
    }

    public function testGetRawDataDoesNotClobberRawDataOverrideForGetRequest()
    {
        // The X_POP_HTTP_RAW_DATA test override (and any other already-captured raw body)
        // must win over the QUERY_STRING fallback.
        $_SERVER['HTTP_HOST']            = 'localhost:8000';
        $_SERVER['SERVER_NAME']          = 'localhost';
        $_SERVER['SERVER_PORT']          = 8000;
        $_SERVER['DOCUMENT_ROOT']        = getcwd();
        $_SERVER['REQUEST_URI']          = '/page';
        $_SERVER['REQUEST_METHOD']       = 'GET';
        $_SERVER['QUERY_STRING']         = 'alpha=1';
        $_SERVER['X_POP_HTTP_RAW_DATA']  = 'already-captured';
        $_GET = ['alpha' => '1'];

        $request = new Request();

        $this->assertEquals('already-captured', $request->getRawData());

        unset($_SERVER['X_POP_HTTP_RAW_DATA']);
    }

    public function testGetHostFromServerName()
    {
        $_GET = null;
        unset($_GET);
        if (isset($_SERVER['QUERY_STRING'])) {
            unset($_SERVER['QUERY_STRING']);
        }
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
        $_SERVER['HTTP_CONTENT_TYPE']   = 'application/json';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '{"foo" : "bar"}';

        $request = new Request('/home');
        $this->assertEquals('bar', $request->getParsedData()['foo']);
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testParseXmlData()
    {
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_NAME']         = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['DOCUMENT_ROOT']       = getcwd();
        $_SERVER['REQUEST_URI']         = '/page';
        $_SERVER['REQUEST_METHOD']      = 'POST';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'application/xml';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '<root><node><![CDATA[Hello World]]></node></root>';

        $request = new Request('/home');
        $this->assertEquals('Hello World', $request->getParsedData()['node']);
    }

    public function testParseDataWithNoContentType()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            unset($_SERVER['HTTP_CONTENT_TYPE']);
        }
        if (isset($_SERVER['X_POP_HTTP_RAW_DATA'])) {
            unset($_SERVER['X_POP_HTTP_RAW_DATA']);
        }
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_NAME']         = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['DOCUMENT_ROOT']       = getcwd();
        $_SERVER['REQUEST_METHOD']      = 'POST';
        $_POST                          = ["foo" => "bar"];

        $request = new Request('/home');
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

        $request = new Request('/home');
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
        $request = new Request('/home');
        $this->assertEquals('127.0.0.1', $request->getIp());
    }

    public function testGetIpFromClientIp()
    {
        $_SERVER['HTTP_CLIENT_IP'] = '127.0.0.1';
        $request = new Request('/home');
        $this->assertEquals('127.0.0.1', $request->getIp(true));
    }

    public function testGetIpFromForwardedFor()
    {
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            unset($_SERVER['HTTP_CLIENT_IP']);
        }
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
        $request = new Request('/home');
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
        $this->assertStringContainsString('phpunit', $request->getServer('PHP_SELF'));
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
        $this->assertNull($request->getHeaderObject('Content-Type'));
    }

    public function testGetHeadersAsArray()
    {
        $request = new Request();
        $headers = $request->getHeadersAsArray();
        $this->assertTrue(is_array($headers));
        $this->assertEquals('localhost', $headers['Host']);
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
        // Note: $_SERVER['HTTP_CONTENT_TYPE'] (not 'CONTENT_TYPE') is what Request's header
        // collection picks up in this environment - see neighboring tests (testParseJsonData
        // et al.) for the same convention.
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'application/x-www-form-urlencoded';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = http_build_query(['foo' => 'bar']);

        $request = new Request();
        $this->assertEquals('bar', $request->getPut('foo'));
    }

    public function testMultipartFormParsedDataPut()
    {
        // Built by hand (rather than via Pop\Mime\Message::createForm()) as an RFC-7578-correct
        // multipart/form-data body: quoted Content-Disposition "name" and CRLF line endings, both
        // required by Pop\Http\Body\Multipart::parse(). Message::createForm() is a mail-multipart
        // (RFC 2046) builder retrofit for HTTP form-data and emits unquoted names / LF endings that
        // the RFC-7578-strict parser used here does not accept.
        $boundary = '1234567890';
        $rawBody  = "--{$boundary}\r\nContent-Disposition: form-data; name=\"foo\"\r\n\r\nbar\r\n--{$boundary}--\r\n";

        // Note: $_SERVER['HTTP_CONTENT_TYPE'] (not 'CONTENT_TYPE') is what Request's header
        // collection picks up in this environment - see neighboring tests (testParseJsonData
        // et al.) for the same convention.
        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'multipart/form-data; boundary=' . $boundary;
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = $rawBody;

        $request = new Request();
        $this->assertEquals('bar', $request->getPut('foo'));
    }

    public function testStreamToFile1()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            unset($_SERVER['HTTP_CONTENT_TYPE']);
        }
        if (isset($_SERVER['X_POP_HTTP_RAW_DATA'])) {
            unset($_SERVER['X_POP_HTTP_RAW_DATA']);
        }

        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = 'Some file contents';

        $request = new Request(null, null, __DIR__ . '/../tmp/my-data');

        $this->assertFileExists(__DIR__ . '/../tmp/my-data');
        $this->assertEquals(__DIR__ . '/../tmp/my-data', $request->getData()->getStreamToFileLocation());
        $this->assertEquals('Some file contents', file_get_contents(__DIR__ . '/../tmp/my-data'));

        $request->getData()->clearStreamToFile();
        $this->assertFileDoesNotExist(__DIR__ . '/../tmp/my-data');
    }

    public function testStreamToFile2()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            unset($_SERVER['HTTP_CONTENT_TYPE']);
        }

        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = 'Some file contents';

        $request = new Request(null, null, __DIR__ . '/../tmp');

        $this->assertTrue($request->hasRawData());
        $this->assertTrue($request->getData()->isStreamToFile());
        $this->assertFileExists($request->getData()->getStreamToFileLocation());

        $streamFile = $request->getData()->getStreamToFileLocation();
        $this->assertEquals('Some file contents', file_get_contents($streamFile));

        $request->getData()->clearStreamToFile();
        $this->assertFileDoesNotExist($streamFile);
    }

    public function testStreamToFile3()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            unset($_SERVER['HTTP_CONTENT_TYPE']);
        }

        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = 'Some file contents';

        $request = new Request(null, null, true);

        $this->assertTrue($request->hasRawData());
        $this->assertTrue($request->getData()->isStreamToFile());
        $this->assertFileExists($request->getData()->getStreamToFileLocation());

        $streamFile = $request->getData()->getStreamToFileLocation();
        $this->assertEquals('Some file contents', file_get_contents($streamFile));

        $request->getData()->clearStreamToFile();
        $this->assertFileDoesNotExist($streamFile);
    }

    public function testStreamToFile4()
    {
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        if (isset($_SERVER['HTTP_CONTENT_TYPE'])) {
            unset($_SERVER['HTTP_CONTENT_TYPE']);
        }
        if (isset($_SERVER['QUERY_STRING'])) {
            unset($_SERVER['QUERY_STRING']);
        }

        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'application/json';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = json_encode(['foo' => 'bar']);

        $request = new Request(null, null,  __DIR__ . '/../tmp/my-data');

        $this->assertTrue($request->hasRawData());
        $this->assertTrue($request->getData()->isStreamToFile());
        $this->assertFileExists($request->getData()->getStreamToFileLocation());

        $request->getData()->processStreamToFile();

        $this->assertEquals('bar', $request->getPut('foo'));

        $streamFile = $request->getData()->getStreamToFileLocation();
        $request->getData()->clearStreamToFile();
        $this->assertFileDoesNotExist($streamFile);
    }

    public function testStreamToFileException()
    {
        $this->expectException('Pop\Http\Server\Exception');

        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }

        $_SERVER['HTTP_HOST']           = 'localhost';
        $_SERVER['SERVER_PORT']         = 8000;
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = 'Some file contents';

        $request = new Request(null, null, '/bad/dir');
    }

    public function testFilter1()
    {
        $_SERVER['HTTP_HOST']  = 'localhost';
        $_SERVER['SERVER_PORT']  = 8000;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = 'var=<b>123</b>&foo=bar';
        $_GET = [
            'var' => '<b>123</b>',
            'foo' => 'bar'
        ];

        $request = new Request(null, new Filter('strip_tags'));
        $this->assertEquals(123, $request->getQuery('var'));
    }

    public function testFilter2()
    {
        $data = new Data();
        $data->addFilter('strip_tags');
        $this->assertEquals('Hello', $data->filter('<b>Hello</b>'));
    }

    public function testMultipartFormDataUsesNativePostAndFiles()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE']   = 'multipart/form-data; boundary=TESTBOUNDARY';
        $_POST  = ['username' => 'admin'];
        $_FILES = ['upload' => ['name' => 'test.txt', 'type' => 'text/plain', 'tmp_name' => '/tmp/x', 'error' => 0, 'size' => 10]];
        // Simulate PHP's own behavior: php://input is empty for multipart requests.
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '';

        $request = new \Pop\Http\Server\Request();

        $this->assertEquals('admin', $request->getPost('username'));
        $this->assertEquals('test.txt', $request->getFiles('upload')['name']);

        unset($_SERVER['CONTENT_TYPE'], $_SERVER['X_POP_HTTP_RAW_DATA'], $_POST, $_FILES);
    }

    public function testGetMagicMethod()
    {
        // This test only cares that each magic-getter property has the right type/shape, not
        // about any particular request payload - so give it its own deterministic REQUEST_METHOD
        // and raw data rather than depending on whatever state prior tests in this file happened
        // to leave behind (this file's tests share $_SERVER/$_POST/etc. and don't all clean up
        // after themselves).
        if (isset($_SERVER['CONTENT_TYPE'])) {
            unset($_SERVER['CONTENT_TYPE']);
        }
        $_SERVER['REQUEST_METHOD']      = 'PUT';
        $_SERVER['HTTP_CONTENT_TYPE']   = 'application/json';
        $_SERVER['X_POP_HTTP_RAW_DATA'] = '{"foo" : "bar"}';

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

    public function testPopulateFromGlobalsFalseProducesBareInstance()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false, ['REQUEST_METHOD' => 'POST']);

        $this->assertEquals(['REQUEST_METHOD' => 'POST'], $request->getServer());
        $this->assertEquals([], $request->getCookie());
        $this->assertEquals([], $request->getEnv());
        $this->assertFalse($request->hasHeaders());
        $this->assertEquals([], $request->getQuery());
        $this->assertEquals([], $request->getPost());
        $this->assertEquals([], $request->getFiles());
    }

    public function testPopulateFromGlobalsTrueIsUnaffected()
    {
        $request = new \Pop\Http\Server\Request();
        $this->assertIsArray($request->getServer());
    }

    public function testGetMethodDefaultsToGetWhenUnset()
    {
        // When REQUEST_METHOD is not set in server array, getMethod() should return 'GET'
        $request = new \Pop\Http\Server\Request(null, null, null, false, []);
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testGetMethodReturnsRequestMethodWhenSet()
    {
        // When REQUEST_METHOD is set in server array, getMethod() should return it
        $request = new \Pop\Http\Server\Request(null, null, null, false, ['REQUEST_METHOD' => 'POST']);
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testIsXxxMethodsRespectMethodOverrideAfterWithMethod()
    {
        // Finding 7: isGet()/isPost()/etc. must agree with getMethod() (and therefore
        // $methodOverride), not read $server['REQUEST_METHOD'] directly.
        $original = new \Pop\Http\Server\Request(null, null, null, false, ['REQUEST_METHOD' => 'GET']);
        $this->assertTrue($original->isGet());
        $this->assertFalse($original->isPost());

        $clone = $original->withMethod('POST');
        $this->assertTrue($clone->isPost());
        $this->assertFalse($clone->isGet());
        $this->assertEquals($clone->getMethod(), 'POST');

        // Original is unaffected
        $this->assertTrue($original->isGet());
        $this->assertFalse($original->isPost());
    }

    public function testAllIsXxxMethodsRespectMethodOverride()
    {
        $methods = [
            'GET'     => 'isGet',
            'HEAD'    => 'isHead',
            'POST'    => 'isPost',
            'PUT'     => 'isPut',
            'DELETE'  => 'isDelete',
            'TRACE'   => 'isTrace',
            'OPTIONS' => 'isOptions',
            'CONNECT' => 'isConnect',
            'PATCH'   => 'isPatch',
        ];

        $request = new \Pop\Http\Server\Request(null, null, null, false, ['REQUEST_METHOD' => 'GET']);

        foreach ($methods as $method => $isMethod) {
            $clone = $request->withMethod($method);
            $this->assertTrue($clone->{$isMethod}(), "{$isMethod}() should be true after withMethod('{$method}')");

            foreach ($methods as $otherMethod => $otherIsMethod) {
                if ($otherMethod !== $method) {
                    $this->assertFalse($clone->{$otherIsMethod}(), "{$otherIsMethod}() should be false after withMethod('{$method}')");
                }
            }
        }
    }

    public function testWithMethodReturnsCloneWithOverride()
    {
        // withMethod() should return a clone with the method override
        $original = new \Pop\Http\Server\Request(null, null, null, false, ['REQUEST_METHOD' => 'GET']);
        $clone = $original->withMethod('POST');

        // Clone should have the new method
        $this->assertEquals('POST', $clone->getMethod());

        // Original should be unaffected
        $this->assertEquals('GET', $original->getMethod());
    }

    public function testWithMethodDoesNotMutateServerArray()
    {
        // withMethod() should not modify the server array
        $original = new \Pop\Http\Server\Request(null, null, null, false, ['REQUEST_METHOD' => 'GET']);
        $clone = $original->withMethod('POST');

        // Clone's server array should still have the original REQUEST_METHOD
        $this->assertEquals('GET', $clone->getServer('REQUEST_METHOD'));

        // Original's server array should be unchanged
        $this->assertEquals('GET', $original->getServer('REQUEST_METHOD'));
    }

    public function testImplementsServerRequestInterface()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false);
        $this->assertInstanceOf('Psr\Http\Message\ServerRequestInterface', $request);
    }

    public function testGetMethodDefaultsToGetAndWithMethodOverrides()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false, []);
        $this->assertEquals('GET', $request->getMethod());

        $clone = $request->withMethod('POST');
        $this->assertNotSame($request, $clone);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('POST', $clone->getMethod());
    }

    public function testGetServerParams()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false, ['FOO' => 'bar']);
        $this->assertEquals(['FOO' => 'bar'], $request->getServerParams());
    }

    public function testCookieParamsGetAndWith()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false);
        $this->assertEquals([], $request->getCookieParams());

        $clone = $request->withCookieParams(['session' => 'abc']);
        $this->assertNotSame($request, $clone);
        $this->assertEquals([], $request->getCookieParams());
        $this->assertEquals(['session' => 'abc'], $clone->getCookieParams());
    }

    public function testQueryParamsGetAndWith()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false);
        $this->assertEquals([], $request->getQueryParams());

        $clone = $request->withQueryParams(['foo' => 'bar']);
        $this->assertNotSame($request, $clone);
        $this->assertEquals(['foo' => 'bar'], $clone->getQueryParams());
    }

    public function testParsedBodyGetAndWith()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false);
        $this->assertNull($request->getParsedBody());

        $clone = $request->withParsedBody(['foo' => 'bar']);
        $this->assertNotSame($request, $clone);
        $this->assertNull($request->getParsedBody());
        $this->assertEquals(['foo' => 'bar'], $clone->getParsedBody());
    }

    public function testAttributesGetSetAndRemove()
    {
        $request = new \Pop\Http\Server\Request(null, null, null, false);
        $this->assertEquals([], $request->getAttributes());
        $this->assertEquals('default', $request->getAttribute('missing', 'default'));

        $withAttr = $request->withAttribute('routeName', 'home');
        $this->assertNotSame($request, $withAttr);
        $this->assertEquals('home', $withAttr->getAttribute('routeName'));
        $this->assertEquals([], $request->getAttributes());

        $withoutAttr = $withAttr->withoutAttribute('routeName');
        $this->assertNotSame($withAttr, $withoutAttr);
        $this->assertNull($withoutAttr->getAttribute('routeName'));
    }

    public function testUploadedFilesGetAndWith()
    {
        // NOTE: the non-override path of getUploadedFiles() calls
        // UploadedFile::createFromFilesArray(), which doesn't exist yet
        // (Task 10). Only the override path is exercised here; the
        // non-override assertion is deferred to Task 10's follow-up pass.
        $request = new \Pop\Http\Server\Request(null, null, null, false);

        $stub  = $this->createStub(\Psr\Http\Message\UploadedFileInterface::class);
        $clone = $request->withUploadedFiles(['avatar' => $stub]);
        $this->assertNotSame($request, $clone);
        $this->assertSame(['avatar' => $stub], $clone->getUploadedFiles());
    }

}
