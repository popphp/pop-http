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
        $client = new Client(new Client\Request('http://localhost'), new Client\Handler\CurlMulti());
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
                'base_uri'          => 'http://localhost',
                'headers'           => ['Authorization' => 'Bearer 123456'],
                'query'             => ['filter' => '123'],
                'type'              => 'application/x-www-form-urlencoded',
                'verify_peer'       => true,
                'allow_self_signed' => false
            ]
        );
        $client->prepare('/foo/bar');
        $this->assertTrue($client->hasHandler());
    }

    public function testIsComplete()
    {
        $response = new Client\Response([
            'code'    => 200,
            'message' => 'OK'
        ]);

        $client = new Client($response);
        $this->assertTrue($client->isComplete());
    }

    public function testIsContinue()
    {
        $response = new Client\Response([
            'code'    => 100,
            'message' => 'Continue'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isContinue());
        $this->assertNull($client2->isContinue());
    }

    public function testIsOk()
    {
        $response = new Client\Response([
            'code'    => 200,
            'message' => 'OK'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isOk());
        $this->assertNull($client2->isOk());
    }

    public function testIsSuccess()
    {
        $response = new Client\Response([
            'code'    => 200,
            'message' => 'OK'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isSuccess());
        $this->assertNull($client2->isSuccess());
    }

    public function testIsRedirect()
    {
        $response = new Client\Response([
            'code'    => 302,
            'message' => 'Redirect'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isRedirect());
        $this->assertNull($client2->isRedirect());
    }

    public function testIsError()
    {
        $response = new Client\Response([
            'code'    => 404,
            'message' => 'Not Found'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isError());
        $this->assertNull($client2->isError());
    }

    public function testIsClientError()
    {
        $response = new Client\Response([
            'code'    => 404,
            'message' => 'Not Found'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isClientError());
        $this->assertNull($client2->isClientError());
    }

    public function testIsServerError()
    {
        $response = new Client\Response([
            'code'    => 500,
            'message' => 'Server Error'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isServerError());
        $this->assertNull($client2->isServerError());
    }

    public function testSendAsync()
    {
        $client = new Client('http://localhost/', ['async' => true]);
        $promise = $client->send();
        $this->assertInstanceOf('Pop\Http\Promise', $promise);
    }

    public function testMagicCall()
    {
        $client = new Client('http://localhost/');
        $this->assertInstanceOf('Pop\Http\Client\Response', $client->get());
    }

    public function testMagicCallAsync()
    {
        $client = new Client();
        $this->assertInstanceOf('Pop\Http\Promise', $client->getAsync('http://localhost/'));
    }

    public function testMagicCallStatic()
    {
        $this->assertInstanceOf('Pop\Http\Client\Response', Client::get('http://localhost/'));
    }

    public function testMagicCallStaticArguments1()
    {
        $response = Client::get(
            'http://localhost/', new Client\Response(), new Client\Handler\Stream(), Auth::createBearer(123456), ['async' => false]
        );
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
    }

    public function testMagicCallStaticArguments2()
    {
        $response = Client::get(
            new Client\Request('http://localhost/'), new Client\Response(),
            new Client\Handler\Stream(), Auth::createBearer(123456), ['async' => false]
        );
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
    }

    public function testMagicCallStaticAsync()
    {
        $this->assertInstanceOf('Pop\Http\Promise', Client::getAsync('http://localhost/'));
    }

}
