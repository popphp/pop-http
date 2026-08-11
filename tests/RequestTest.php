<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Data;
use Pop\Http\Client\Request;
use PHPUnit\Framework\TestCase;
use Pop\Mime\Part\Header;

class RequestTest extends TestCase
{

    public function testConstructor()
    {
        $request = new Request('http://localhost/', 'GET', ['foo' => 'bar'], Request::JSON);
        $this->assertInstanceOf('Pop\Http\Client\Request', $request);
        $this->assertInstanceOf('Pop\Http\Client\Data', $request->getData());
    }

    public function testCreateJson()
    {
        $request = Request::createJson('http://localhost/', 'GET', ['foo' => 'bar']);
        $this->assertTrue($request->isJson());
    }

    public function testCreateXml()
    {
        $request = Request::createXml('http://localhost/', 'GET', ['foo' => 'bar']);
        $this->assertTrue($request->isXml());
    }

    public function testCreateUrlForm()
    {
        $request = Request::createUrlEncoded('http://localhost/', 'GET', ['foo' => 'bar']);
        $this->assertTrue($request->isUrlEncoded());
    }

    public function testCreateMultipart()
    {
        $request = Request::createMultipart('http://localhost/', 'GET', ['foo' => 'bar']);
        $this->assertTrue($request->isMultipart());
    }

    public function testGetterAndSetter()
    {
        $request = Request::create('http://localhost/');
        $this->assertTrue($request->hasUri());
        $this->assertInstanceOf('Pop\Http\Uri', $request->getUri());
        $this->assertEquals('http://localhost/', $request->getUriAsString());
    }

    public function testMethod()
    {
        $request = new Request('http://localhost/', 'POST');
        $this->assertTrue($request->hasMethod());
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testMethodException()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $request = new Request('http://localhost/', 'BAD', null, null, true);
        $this->assertEquals('POST', $request->getMethod());
    }

    public function testRequestType()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'text/html');
        $request->addHeader('Content-Length', 'text/html');
        $request->setRequestType(Request::JSON);
        $this->assertEquals(Request::JSON, $request->getRequestType());
        $this->assertTrue($request->isJson());
        $this->assertTrue($request->hasRequestType());
        $request->setRequestType(Request::XML);
        $this->assertEquals(Request::XML, $request->getRequestType());
        $this->assertTrue($request->isXml());
        $request->setRequestType(Request::URLENCODED);
        $this->assertEquals(Request::URLENCODED, $request->getRequestType());
        $this->assertTrue($request->isUrlEncoded());
        $request->setRequestType(Request::MULTIPART);
        $this->assertEquals(Request::MULTIPART, $request->getRequestType());
        $this->assertTrue($request->isMultipart());
        $request->setRequestType(null);
        $this->assertFalse($request->hasRequestType());
    }

    public function testAddHeader()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->addHeader(0, 'Content-Type: application/json');
        $this->assertTrue($request->hasHeader('Content-Type'));
    }

    public function testAddHeaderMultipart()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->addHeader(0, 'Content-Type: multipart/form-data; boundary=1234567890');
        $this->assertTrue($request->hasHeader('Content-Type'));
    }

    public function testSetHeaders()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->addHeaders([
            new Header('Content-Type', 'application/json'),
            new Header('Authorization', 'Bearer 123456')
        ]);
        $this->assertCount(2, $request->getHeaders());
        $request->setHeaders([new Header('Content-Type', 'application/json')]);
        $this->assertCount(1, $request->getHeaders());
    }

    public function testAddHeaders()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->addHeaders([new Header('Content-Type', 'application/json')]);
        $this->assertTrue($request->hasHeader('Content-Type'));
    }

    public function testDataString()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->setData('foo=bar');
        $request->prepareData();
        $this->assertTrue($request->hasData());
        $this->assertEquals(7, $request->getDataContentLength());
    }

    public function testDataObject()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->setData(new Data(['foo' => 'bar']));
        $this->assertTrue($request->hasData());
        $this->assertTrue($request->getData()->hasRequest());
    }

    public function testGetUriAsString()
    {
        $request = new Request('http://localhost/', 'GET');
        $request->setData(['foo' => 'bar']);
        $this->assertEquals('http://localhost/?foo=bar', $request->getUriAsString());
    }

    public function testBodyContent()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->setBody('This is a text body');
        $this->assertEquals('This is a text body', $request->getBodyContent());
        $this->assertEquals(19, $request->getBodyContentLength());
    }

    public function testBodyContentZero()
    {
        $request = new Request('http://localhost/', 'POST');
        $this->assertEquals(0, $request->getBodyContentLength());
    }

    public function testPrepareJson1()
    {
        // Sending pre-built JSON content as-is now requires the explicit
        // setRawData(true) flag - shape-based "already encoded" guessing
        // was removed, so a plain setData() of a JSON-looking string is
        // always encoded as a JSON string value instead of passed through.
        $request = new Request('http://localhost/', 'POST');
        $request->createAsJson()
            ->setRawData(true)
            ->setData('{"foo":"bar","baz":"123"}');
        $request->prepareData();
        $this->assertEquals('{"foo":"bar","baz":"123"}', $request->getDataContent());
    }

    public function testPrepareJson2()
    {
        // prepareJson() no longer merges the contents of referenced JSON
        // files into the payload - JSON requests now always encode the
        // data array verbatim.
        $request = new Request('http://localhost/', 'POST');
        $request->createAsJson()
            ->setData([
                'file1' => [
                    'filename'    => __DIR__ . '/tmp/data.json',
                    'contentType' => 'application/json'
                ],
                'file2' => [
                    'filename'    => __DIR__ . '/tmp/data2.json',
                    'contentType' => 'application/json'
                ]
            ]);
        $request->prepareData();
        $jsonArray = [
            'file1' => [
                'filename'    => __DIR__ . '/tmp/data.json',
                'contentType' => 'application/json'
            ],
            'file2' => [
                'filename'    => __DIR__ . '/tmp/data2.json',
                'contentType' => 'application/json'
            ]
        ];

        $this->assertEquals($jsonArray, json_decode($request->getDataContent(), true));
    }

    public function testPrepareXml1()
    {
        $request = new Request('http://localhost/', 'POST');
        $request->createAsXml()
            ->setData('<?xml version="1.0" encoding="utf-8"?><root><foo>bar</foo><baz>123</baz></root>');
        $request->prepareData();
        $this->assertEquals('<?xml version="1.0" encoding="utf-8"?><root><foo>bar</foo><baz>123</baz></root>', $request->getDataContent());
    }

    public function testPrepareXml2()
    {
        // prepareXml() no longer merges the contents of referenced XML files
        // into the payload (the old "magic-key" filename/contentType file
        // detection was removed) - XML requests now just concatenate the
        // data array's values verbatim.
        $request = new Request('http://localhost/', 'POST');
        $request->createAsXml()
            ->setData([
                'foo' => '<foo>bar</foo>',
                'baz' => '<baz>123</baz>'
            ]);
        $request->prepareData();
        $this->assertEquals('<foo>bar</foo><baz>123</baz>', $request->getDataContent());
    }

    public function testSetDataBeforeCreateAsJsonIsNotStale()
    {
        // Regression: Data::setData() eagerly prepares against whatever request
        // type is set at that moment. Calling setData() BEFORE createAsJson()
        // used to bake in stale (url-encoded) Content-Type/dataContent that
        // survived createAsJson() entirely, because Data::$prepared was already
        // true and the handlers only re-prepare when isPrepared() is false.
        //
        // This test deliberately mirrors Curl::prepare()/Stream::prepare()'s
        // guarded re-preparation (`if hasData() && !isPrepared() -> prepareData()`)
        // instead of calling $request->prepareData() unconditionally, since an
        // unconditional call would mask the bug entirely.
        $request = new Request('http://localhost/', 'POST');
        $request->setData(['flag' => true]);
        $request->createAsJson();

        if ($request->hasData() && !$request->getData()->isPrepared()) {
            $request->prepareData();
        }

        $this->assertEquals(Request::JSON, (string)$request->getHeaderObject('Content-Type')->getValue());
        $this->assertEquals(json_encode(['flag' => true], JSON_PRETTY_PRINT), $request->getDataContent());
    }

    public function testSetDataBeforeCreateAsMultipartIsNotStale()
    {
        $tmpFile = __DIR__ . '/tmp/data.json';

        $request = new Request('http://localhost/', 'POST');
        $request->setData([
            'upload' => [
                'filename'    => $tmpFile,
                'contentType' => 'text/plain'
            ]
        ]);
        $request->createAsMultipart();

        if ($request->hasData() && !$request->getData()->isPrepared()) {
            $request->prepareData();
        }

        $this->assertStringContainsString('multipart/form-data', (string)$request->getHeaderObject('Content-Type')->getValue());
    }

    public function testSetDataAfterCreateAsJsonStillWorks()
    {
        // The already-correct, documented order continues to work unchanged.
        $request = new Request('http://localhost/', 'POST');
        $request->createAsJson();
        $request->setData(['flag' => true]);
        $request->prepareData();

        $this->assertEquals(Request::JSON, (string)$request->getHeaderObject('Content-Type')->getValue());
        $this->assertEquals(json_encode(['flag' => true], JSON_PRETTY_PRINT), $request->getDataContent());
    }

    public function testMagicMethod()
    {
        $request = new Request('http://localhost/');
        $this->assertTrue($request->isGet());
    }

    public function testMagicMethodException1()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $request = new Request('http://localhost/');
        $this->assertTrue($request->isBad());
    }

    public function testMagicMethodException2()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $request = new Request('http://localhost/');
        $this->assertTrue($request->badMethod());
    }

    public function testImplementsRequestInterface()
    {
        $request = new Request('http://localhost/');
        $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $request);
    }

    public function testGetProtocolVersionDefaultsAndWithProtocolVersion()
    {
        $request = new Request('http://localhost/');
        $this->assertEquals('1.1', $request->getProtocolVersion());

        $clone = $request->withProtocolVersion('2.0');
        $this->assertNotSame($request, $clone);
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('2.0', $clone->getProtocolVersion());
    }

    public function testGetRequestTargetDefaultsToPathAndQuery()
    {
        $request = new Request('http://localhost/foo?bar=1');
        $this->assertEquals('/foo?bar=1', $request->getRequestTarget());
    }

    public function testGetRequestTargetDefaultsToSlashWithNoUri()
    {
        $request = new Request();
        $this->assertEquals('/', $request->getRequestTarget());
    }

    public function testWithRequestTargetOverridesAndReturnsDistinctClone()
    {
        $request = new Request('http://localhost/foo');
        $clone   = $request->withRequestTarget('*');

        $this->assertNotSame($request, $clone);
        $this->assertEquals('/foo', $request->getRequestTarget());
        $this->assertEquals('*', $clone->getRequestTarget());
    }

    public function testWithUriReturnsDistinctCloneAndUpdatesHostHeader()
    {
        $request = new Request('http://localhost/foo');
        $newUri  = new \Pop\Http\Uri('http://example.com/bar');
        $clone   = $request->withUri($newUri);

        $this->assertNotSame($request, $clone);
        $this->assertEquals('localhost', $request->getUri()->getHost());
        $this->assertEquals('example.com', $clone->getUri()->getHost());
        $this->assertEquals('example.com', $clone->getHeaderLine('Host'));
    }

    public function testWithUriPreserveHostKeepsExistingHostHeader()
    {
        $request = new Request('http://localhost/foo');
        $request->addHeader('Host', 'localhost');
        $newUri  = new \Pop\Http\Uri('http://example.com/bar');
        $clone   = $request->withUri($newUri, true);

        $this->assertEquals('localhost', $clone->getHeaderLine('Host'));
    }

    public function testGetMethodDefaultsToGetWhenUnset()
    {
        $request = new Request('http://localhost/', null);
        $this->assertEquals('GET', $request->getMethod());
    }

    public function testWithMethodReturnsDistinctClone()
    {
        $request = new Request('http://localhost/', 'GET');
        $clone   = $request->withMethod('POST');

        $this->assertNotSame($request, $clone);
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('POST', $clone->getMethod());
    }

    public function testFullyImplementsRequestInterfaceOnInstantiation()
    {
        $request = new Request('http://localhost/');
        $this->assertInstanceOf('Psr\Http\Message\RequestInterface', $request);
    }

    public function testGetUriReturnsTransientEmptyUriWhenUnset()
    {
        $request = new Request();
        $uri = $request->getUri();

        $this->assertInstanceOf('Pop\Http\Uri', $uri);
        $this->assertFalse($request->hasUri());
    }

    public function testCloneDeepCopiesUriIndependently()
    {
        $request = new Request('http://localhost/foo');
        $clone   = clone $request;

        $clone->getUri()->setHost('evil.com');

        $this->assertEquals('localhost', $request->getUri()->getHost());
        $this->assertEquals('evil.com', $clone->getUri()->getHost());
    }

    public function testCloneDeepCopiesDataAndRepointsBackReference()
    {
        $request = Request::createJson('http://localhost/', 'POST', ['foo' => 'bar']);
        $clone   = clone $request;

        $this->assertNotSame($request->getData(), $clone->getData());

        $clone->addData('extra', 'value');

        $this->assertFalse($request->getData()->hasData('extra'));
        $this->assertTrue($clone->getData()->hasData('extra'));

        // Cloned Data's back-reference must point at the clone, not the original -
        // preparing the clone must not write Content-Length/Content-Type to $request.
        $clone->prepareData();
        $this->assertSame($clone, $clone->getData()->getRequest());
        $this->assertSame($request, $request->getData()->getRequest());
    }

    public function testCloneDeepCopiesQueryAndRepointsBackReference()
    {
        $request = new Request('http://localhost/', 'GET');
        $request->addQuery('foo', 'bar');

        $clone = clone $request;
        $this->assertNotSame($request->getQuery(), $clone->getQuery());

        $clone->addQuery('extra', 'value');

        $this->assertFalse($request->getQuery()->hasData('extra'));
        $this->assertTrue($clone->getQuery()->hasData('extra'));
        $this->assertSame($clone, $clone->getQuery()->getRequest());
    }

    public function testWithHeaderClonePreservesIndependentContentLengthState()
    {
        // Realistic scenario: a JSON request whose Data back-reference must survive
        // cloning through withHeader() so the clone's own prepared state (Content-Length,
        // body-derived) stays internally consistent rather than mixing stale state.
        $request = Request::createJson('http://localhost/', 'POST', ['foo' => 'bar']);
        $request->prepareData();
        $originalContentLength = $request->getHeaderLine('Content-Length');

        $clone = $request->withHeader('X-Foo', 'bar');
        $clone->addData('extra', 'a much longer value than before');
        $clone->prepareData();

        $this->assertEquals($originalContentLength, $request->getHeaderLine('Content-Length'));
        $this->assertNotEquals($originalContentLength, $clone->getHeaderLine('Content-Length'));
        $this->assertFalse($request->getData()->hasData('extra'));
    }

}
