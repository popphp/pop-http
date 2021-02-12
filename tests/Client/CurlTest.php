<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Curl;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{

    public function testConstructor()
    {
        $client = new Curl('http://localhost/', 'POST', [
            CURLOPT_POST => true
        ]);
        $client->setField('foo', 'bar');
        $this->assertInstanceOf('Pop\Http\Client\Curl', $client);
        $this->assertEquals('POST', $client->getMethod());
        $this->assertEquals('http://localhost/', $client->getUrl());
        $this->assertEquals('bar', $client->getField('foo'));
        $this->assertEquals('bar', $client->getFields()['foo']);
        $client->removeField('foo');
        $this->assertNull($client->getField('foo'));
        $this->assertNull($client->getResponseHeader('header'));
        $this->assertTrue($client->hasResource());
        $this->assertTrue(is_resource($client->getResource()));
        $this->assertTrue(is_resource($client->curl()));
        $this->assertTrue($client->getOption(CURLOPT_POST));
        $client->setFields([
            'var' => 123
        ]);
        $this->assertEquals(123, $client->getField('var'));
        $client->send();
        $this->assertNotEmpty($client->resource());
    }

    public function testCustomRequest()
    {
        $client = new Curl('http://localhost/', 'PUT');
        $this->assertTrue($client->hasOption(CURLOPT_CUSTOMREQUEST));
        $this->assertEquals('PUT', $client->getOption(CURLOPT_CUSTOMREQUEST));
    }

    public function testSetReturnHeader()
    {
        $client = new Curl('http://localhost/');
        $client->setReturnHeader();
        $this->assertTrue($client->isReturnHeader());
    }

    public function testSetReturnTransfer()
    {
        $client = new Curl('http://localhost/');
        $client->setReturnTransfer();
        $this->assertTrue($client->isReturnTransfer());
    }

    public function testSendGetQuery()
    {
        $client = new Curl('http://localhost/');
        $client->setFields([
            'var' => '123',
            'foo' => 'bar'
        ]);

        $client->send();
        $this->assertTrue(is_array($client->version()));
        $client->disconnect();
        $this->assertFalse($client->hasResource());
    }

    public function testSetMethodException()
    {
        $this->expectException('Pop\Http\Client\Exception');
        $client = new Curl('http://localhost/', 'BAD');
    }

    public function testClientRequest()
    {
        $client = new Curl('http://localhost/');
        $client->setRequest(new Request());
        $client->addRequestHeaders([
            'Content-Type' => 'text/plain'
        ]);
        $client->addRequestHeader('Content-Length', 123);
        $client->request()->setBody('Hello World!');
        $this->assertTrue($client->hasRequest());
        $this->assertInstanceOf('Pop\Http\Client\Request', $client->request());
        $this->assertTrue($client->hasRequestHeaders());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertEquals(2, count($client->getRequestHeaders()));
        $this->assertEquals('text/plain', $client->getRequestHeader('Content-Type')->getValue());
        $this->assertEquals('Hello World!', $client->getRequestBody()->getContent());
    }

    public function testClientResponse()
    {
        $client = new Curl('http://localhost/');
        $client->setResponse(new Response());
        $client->addResponseHeaders([
            'Content-Type' => 'text/plain'
        ]);
        $client->addResponseHeader('Content-Length', 123);
        $client->response()->setCode(200);
        $client->response()->setBody('Hello World!');
        $this->assertTrue($client->hasResponse());
        $this->assertInstanceOf('Pop\Http\Client\Response', $client->response());
        $this->assertTrue($client->hasResponseHeaders());
        $this->assertTrue($client->hasResponseHeader('Content-Type'));
        $this->assertEquals(2, count($client->getResponseHeaders()));
        $this->assertEquals('text/plain', $client->getResponseHeader('Content-Type')->getValue());
        $this->assertEquals(200, $client->getResponseCode());
        $this->assertEquals('Hello World!', $client->getResponseBody()->getContent());
    }

    public function testQuery()
    {
        $client = new Curl('http://localhost/', 'GET');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $this->assertEquals('username=admin', $client->request()->getQuery());
        $this->assertTrue($client->request()->hasQuery());
    }

    public function testCreateAsJson()
    {
        $client = new Curl('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->createAsJson();
        $client->open();
        $this->assertTrue($client->isJson());
        $this->assertEquals('application/json', $client->request()->getFormType());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertEquals('application/json', $client->getRequestHeader('Content-Type')->getValue());
    }

    public function testCreateUrlForm()
    {
        $client = new Curl('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->createUrlEncodedForm();
        $client->open();
        $this->assertTrue($client->isUrlEncodedForm());
        $this->assertEquals('application/x-www-form-urlencoded', $client->request()->getFormType());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertEquals('application/x-www-form-urlencoded', $client->getRequestHeader('Content-Type')->getValue());
    }

    public function testCreateMultipartForm()
    {
        $client = new Curl('http://localhost/', 'POST');
        $client->setRequest(new Request());
        $client->setFields(['username' => 'admin']);
        $client->createMultipartForm();
        $client->open();
        $this->assertTrue($client->isMultipartForm());
        $this->assertTrue($client->hasRequestHeader('Content-Type'));
        $this->assertTrue($client->hasRequestHeader('Content-Length'));
        $this->assertStringContainsString('multipart/form-data', $client->getRequestHeader('Content-Type')->getValue());
    }

}
