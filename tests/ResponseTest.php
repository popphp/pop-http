<?php

namespace Pop\Http\Test;

use Pop\Http\Server\Response;
use PHPUnit\Framework\TestCase;
use Pop\Mime\Part\Header;

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
        $this->assertTrue($response->hasVersion());
        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($response->hasCode());
        $this->assertEquals('OK', $response->getMessage());
        $this->assertTrue($response->hasMessage());
        $this->assertTrue($response->isOk());
    }

    public function testIsContinue()
    {
        $response = Response::create();
        $response->setVersion('1.1')
            ->setCode(100)
            ->setMessage('Coninue');

        $this->assertTrue($response->isContinue());
    }

    public function testHeaders()
    {
        $response = new Response();
        $response->addHeader('X-Resource', 'users');
        $this->assertTrue($response->hasHeaders());
        $this->assertTrue($response->hasHeader('X-Resource'));
        $this->assertEquals('users', $response->getHeaderValueAsString('X-Resource'));
        $response->removeHeader('X-Resource');
        $this->assertFalse($response->hasHeader('X-Resource'));
        $response->removeHeaders();
        $this->assertFalse($response->hasHeaders());
    }

    public function testGetHeadersAsArray()
    {
        $response = new Response();
        $response->addHeader('X-Resource', 'users')
            ->addHeader(new Header('X-Permissions', ['index', 'create']));
        $this->assertGreaterThanOrEqual(2, count($response->getHeadersAsArray()));
    }

    public function testRemoveBody()
    {
        $response = new Response();
        $response->setBody('This is a body');
        $this->assertTrue($response->hasBody());
        $response->removeBody();
        $this->assertFalse($response->hasBody());
    }

}