<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Stream;
use Pop\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

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

}