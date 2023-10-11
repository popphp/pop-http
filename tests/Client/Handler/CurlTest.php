<?php

namespace Pop\Http\Test\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\Curl;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{

    public function testConstructor()
    {
        $curl = new Curl([CURLOPT_RETURNTRANSFER => 1]);
        $this->assertInstanceOf('Pop\Http\Client\Handler\Curl', $curl);
        $this->assertInstanceOf('CurlHandle', $curl->curl());
        $this->assertInstanceOf('CurlHandle', $curl->resource());
        $this->assertInstanceOf('CurlHandle', $curl->getResource());
        $this->assertTrue($curl->hasOption(CURLOPT_RETURNTRANSFER));
        $this->assertEquals(1, $curl->getOption(CURLOPT_RETURNTRANSFER));
        $this->assertTrue(isset($curl->version()['version_number']));
        $this->assertTrue($curl->hasResource());
    }

    public function testResponse()
    {
        $curl = new Curl();
        $curl->setResponse(new Response());
        $this->assertTrue($curl->hasResponse());
        $this->assertInstanceOf('Pop\Http\Client\Response', $curl->getResponse());
    }

    public function testError()
    {
        $curl = new Curl();
        $this->assertEquals(0, $curl->getErrorNumber());
        $this->assertEquals('', $curl->getErrorMessage());
    }

    public function testReturnTransfer()
    {
        $curl = new Curl();
        $curl->setReturnTransfer();
        $this->assertTrue($curl->isReturnTransfer());
    }

    public function testReturnHeader()
    {
        $curl = new Curl();
        $curl->setReturnHeader();
        $this->assertTrue($curl->isReturnHeader());
    }

    public function testRemoveOption()
    {
        $curl = new Curl();
        $curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->assertTrue($curl->hasOption(CURLOPT_RETURNTRANSFER));
        $curl->removeOption(CURLOPT_RETURNTRANSFER);
        $this->assertFalse($curl->hasOption(CURLOPT_RETURNTRANSFER));
    }

    public function testParseResponse()
    {
        $curl = new Curl([CURLOPT_URL => 'http://localhost/']);
        $curl->setReturnHeader(false);
        $response = $curl->send();
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
        $this->assertTrue(str_contains($response->getBodyContent(), '<html'));
    }

    public function testReset()
    {
        $curl = new Curl();
        $curl->setResponse(new Response());
        $this->assertTrue($curl->hasResponse());
        $curl->reset();
        $this->assertFalse($curl->hasResponse());
    }

    public function testDisconnect()
    {
        $curl = new Curl();
        $this->assertTrue($curl->hasResource());
        $curl->disconnect();
        $this->assertFalse($curl->hasResource());
    }

    public function testPrepareWithAuth()
    {
        $curl   = new Curl();
        $client = new Client('http://localhost/', Auth::createBearer(123456), $curl);
        $client->getHandler()->prepare($client->getRequest(), $client->getAuth());
        $this->assertTrue($client->getRequest()->hasHeader('Authorization'));
        $this->assertEquals('Authorization: Bearer 123456', $client->getRequest()->getHeaderAsString('Authorization'));
    }

    public function testPrepareWithGetData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/');
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertTrue($request->getData()->hasQueryString());
        $this->assertEquals('foo=bar', $request->getData()->getQueryString());
    }

    public function testPrepareWithJsonData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::JSON);
        $request->setData(['foo' => 'bar']);

        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals("{\n    \"foo\": \"bar\"\n}", $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testPrepareWithPostUrlFormData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::URLFORM);
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testPrepareWithPostMultipartFormData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::MULTIPART);
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertTrue(str_contains($curl->getOption(CURLOPT_POSTFIELDS), "Content-Disposition: form-data; name=foo\r\n\r\nbar\r\n"));
    }

    public function testPrepareWithPostFormData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals(['foo' => 'bar'], $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testPrepareWithBodyData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setBody('foo=bar');
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $curl->getOption(CURLOPT_POSTFIELDS));
    }

}