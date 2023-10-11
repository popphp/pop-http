<?php

namespace Pop\Http\Test;

use Pop\Http\Client;
use Pop\Http\Auth;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    public function testConstructor()
    {
        $client = new Client(
            new Client\Request(),
            new Client\Response(),
            new Client\Handler\Stream(),
            new Auth(),
            ['base_uri' => 'http://localhost']
        );
        $this->assertInstanceOf('Pop\Http\Client', $client);
        $this->assertTrue($client->hasRequest());
        $this->assertTrue($client->hasResponse());
        $this->assertTrue($client->hasHandler());
        $this->assertTrue($client->hasAuth());
        $this->assertTrue($client->hasBaseUri());
        $this->assertTrue($client->hasOptions());
        $this->assertTrue($client->hasOption('base_uri'));
        $this->assertInstanceOf('Pop\Http\Client\Request', $client->getRequest());
        $this->assertInstanceOf('Pop\Http\Client\Response', $client->getResponse());
        $this->assertInstanceOf('Pop\Http\Client\Handler\Stream', $client->getHandler());
        $this->assertInstanceOf('Pop\Http\Auth', $client->getAuth());
        $this->assertEquals('http://localhost', $client->getBaseUri());
        $this->assertEquals('http://localhost', $client->getOption('base_uri'));
        $this->assertCount(1, $client->getOptions());
    }

    public function testMultihandler()
    {
        $client = new Client(new Client\Request('http://localhost'));
        $client->setMultiHandler(new Client\Handler\CurlMulti());
        $this->assertTrue($client->hasMultiHandler());
        $this->assertInstanceOf('Pop\Http\Client\Handler\CurlMulti', $client->getMultiHandler());
    }

    public function testPrepareException()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client();
        $client->prepare();
    }

    public function testPrepare()
    {
        $client = new Client(
            [
                'base_uri' => 'http://localhost',
                'headers'  => ['Authorization' => 'Bearer 123456'],
                'query'    => ['filter' => '123'],
                'type'     => 'application/x-www-form-urlencoded'
            ]
        );
        $client->prepare('/foo/bar');
        $this->assertTrue($client->hasHandler());
    }

}
