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
        $request = new Request('http://localhost/', 'POST');
        $request->createAsJson()
            ->setData('{"foo":"bar","baz":"123"}');
        $request->prepareData();
        $this->assertEquals('{"foo":"bar","baz":"123"}', $request->getDataContent());
    }

    public function testPrepareJson2()
    {
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
            'foo'     => 'bar',
            'baz'     => 123,
            'another' => 456,
            'test'    => 789
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
        $request = new Request('http://localhost/', 'POST');
        $request->createAsXml()
            ->setData([
                'file1' => [
                    'filename'    => __DIR__ . '/tmp/data.xml',
                    'contentType' => 'application/xml'
                ]
            ]);
        $request->prepareData();
        $this->assertEquals(file_get_contents(__DIR__ . '/tmp/data.xml'), $request->getDataContent());
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

}
