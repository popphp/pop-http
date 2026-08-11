<?php

namespace Pop\Http\Test\Factory;

use Pop\Http\Factory\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{

    public function testImplementsResponseFactoryInterface()
    {
        $this->assertInstanceOf('Psr\Http\Message\ResponseFactoryInterface', new ResponseFactory());
    }

    public function testCreateResponseDefaults()
    {
        $response = (new ResponseFactory())->createResponse();
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateResponseWithCodeAndReasonPhrase()
    {
        $response = (new ResponseFactory())->createResponse(404, 'Custom Not Found');
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Custom Not Found', $response->getReasonPhrase());
    }

}
