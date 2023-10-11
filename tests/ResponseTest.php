<?php

namespace Pop\Http\Test;

use Pop\Http\Server\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testConstructor()
    {
        $response = new Response();
        $this->assertInstanceOf('Pop\Http\Server\Response', $response);
    }

    public function testGetterAndSetter()
    {
        $response = Response::create();
        $response->setVersion('1.1')
            ->setCode(200)
            ->setMessage('OK');

        $this->assertEquals('1.1', $response->getVersion());
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('OK', $response->getMessage());
    }

}