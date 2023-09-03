<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Auth;
use Pop\Http\Client\Stream;
use Pop\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    public function testSetAndGetUrl()
    {
        $client = new Stream();
        $client->setUrl('http://localhost');

        $this->assertEquals('http://localhost', $client->getUrl());

        $client->appendToUrl('/page');

        $this->assertEquals('http://localhost/page', $client->getUrl());
    }

    public function testSetAndGetAuth()
    {
        $client = new Stream('http://localhost');
        $client->setAuth(Auth::createBearer('sdchsjdklh23lkhsldkfcmsdf'));
        $this->assertTrue($client->hasAuth());
        $this->assertInstanceOf('Pop\Http\Auth', $client->getAuth());
    }

    public function testGetParsedResponse()
    {
        $response = new Response([
            'version' => '1.1',
            'code'    => 200,
            'message' => 'OK'
        ]);
        $response->addHeader('Content-Type', 'appliction/json');
        $response->setBody(json_encode(['foo' => 'bar']));

        $client = new Stream();
        $client->setResponse($response);

        $parsedResponse = $client->getParsedResponse();

        $this->assertEquals('bar', $parsedResponse['foo']);
    }

    public function testFields()
    {
        $client = new Stream();
        $client->setField('foo', 'bar');
        $client->setField('var', '123');
        $this->assertTrue($client->hasFields());
        $this->assertTrue($client->hasField('foo'));
        $this->assertTrue($client->hasField('var'));
        $this->assertEquals('bar', $client->getField('foo'));
        $this->assertEquals('123', $client->getField('var'));
        $client->removeField('var');
        $this->assertFalse($client->hasField('var'));
        $client->removeFields();
        $this->assertFalse($client->hasField('foo'));
    }

}