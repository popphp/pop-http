<?php

namespace Pop\Http\Test\Client;

use Pop\Http;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testGetterAndSetter()
    {
        $client = new Http\Client('http://localhost/');
        $client->send();
        $response = $client->getResponse()->getParsedResponse();
        $this->assertTrue(str_contains($response, '<html'));
    }

    public function testCollect()
    {
        $response = new Http\Client\Response([
            'code'    => 200,
            'message' => 'Ok'
        ]);
        $response->setBody(json_encode(['foo' => 'bar'], JSON_PRETTY_PRINT));
        $data = $response->collect();
        $this->assertInstanceOf('Pop\Utils\Collection', $data);
        $this->assertEquals('bar', $data['foo']);
    }

    public function testCollectEmpty()
    {
        $response = new Http\Client\Response([
            'code'    => 200,
            'message' => 'Ok'
        ]);
        $response->setBody('<html><body><h1>Hello World</h1></body></html>');
        $data = $response->collect(false);
        $this->assertInstanceOf('Pop\Utils\Collection', $data);
        $this->assertEmpty($data->toArray());
    }

}