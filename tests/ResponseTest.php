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

    public function testAddHeader()
    {
        $response = new Response();
        $response->addHeaders([
            'Content-Type: application/json'
        ]);
        $this->assertTrue($response->hasHeader('Content-Type'));
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

    public function testImplementsResponseInterface()
    {
        $response = new Response();
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    public function testGetProtocolVersionDelegatesToVersion()
    {
        $response = new Response(['version' => '2.0']);
        $this->assertEquals('2.0', $response->getProtocolVersion());
    }

    public function testWithProtocolVersionReturnsDistinctClone()
    {
        $response = new Response();
        $clone    = $response->withProtocolVersion('2.0');

        $this->assertNotSame($response, $clone);
        $this->assertEquals('1.1', $response->getProtocolVersion());
        $this->assertEquals('2.0', $clone->getProtocolVersion());
    }

    public function testGetStatusCodeAndReasonPhrase()
    {
        $response = new Response(['code' => 404]);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }

    public function testWithStatusReturnsDistinctClone()
    {
        $response = new Response();
        $clone    = $response->withStatus(201, 'Custom Created');

        $this->assertNotSame($response, $clone);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(201, $clone->getStatusCode());
        $this->assertEquals('Custom Created', $clone->getReasonPhrase());
    }

}