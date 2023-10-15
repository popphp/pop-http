<?php

namespace Pop\Http\Test\Client\Handler;

use Pop\Http\Client;
use Pop\Http\Client\Handler\Curl;
use Pop\Http\Client\Handler\CurlMulti;
use PHPUnit\Framework\TestCase;

class CurlMultiTest extends TestCase
{

    public function testConstructor()
    {
        $curlMulti = new CurlMulti();
        $this->assertInstanceOf('Pop\Http\Client\Handler\CurlMulti', $curlMulti);
    }

    public function testCreate()
    {
        $curlMulti = CurlMulti::create();
        $this->assertInstanceOf('Pop\Http\Client\Handler\CurlMulti', $curlMulti);
    }

    public function testSetOption()
    {
        $curlMulti = new CurlMulti();
        $curlMulti->setOption(CURLMOPT_MAXCONNECTS, 10);
        $this->assertTrue($curlMulti->hasOption(CURLMOPT_MAXCONNECTS));
        $this->assertEquals(10, $curlMulti->getOption(CURLMOPT_MAXCONNECTS));
    }

    public function testError()
    {
        $curlMulti = new CurlMulti();
        $this->assertEquals(0, $curlMulti->getErrorNumber());
        $this->assertEquals('No error', $curlMulti->getErrorMessage());
    }

    public function testRemoveOption()
    {
        $curlMulti = new CurlMulti();
        $curlMulti->setOption(CURLMOPT_MAXCONNECTS, 10);
        $this->assertTrue($curlMulti->hasOption(CURLMOPT_MAXCONNECTS));
        $curlMulti->removeOption(CURLMOPT_MAXCONNECTS);
        $this->assertFalse($curlMulti->hasOption(CURLMOPT_MAXCONNECTS));
    }

    public function testAddClients()
    {
        $curlMulti     = new CurlMulti();
        $request2 = new Client('http://localhost/', new Curl());
        $request3 = new Client('http://localhost/', new Curl());
        $curlMulti->addClients([
            'request1' => new Client('http://localhost/', new Curl()),
            'request2' => $request2,
            2          => $request3,
        ]);
        $this->assertTrue($curlMulti->hasClient('request1'));
        $this->assertInstanceOf('Pop\Http\Client', $curlMulti->getClient('request1'));
        $this->assertCount(3, $curlMulti->getClients());

        $curlMulti->removeClient('request1');
        $curlMulti->removeClient(null, $request2);

        $this->assertFalse($curlMulti->hasClient('request1'));
        $this->assertCount(1, $curlMulti->getClients());
    }

    public function testGetClientContent()
    {
        $curlMulti     = new CurlMulti();
        $request1 = new Client('http://localhost/', new Curl());
        $request2 = new Client('http://localhost/', new Curl());
        $curlMulti->addClients([
            'request1' => $request1,
            'request2' => $request2,
        ]);
        $this->assertEmpty($curlMulti->getClientContent('request1'));
    }

    public function testRemoveClientException()
    {
        $this->expectException('Pop\Http\Client\Handler\Exception');
        $curlMulti = new CurlMulti();
        $curlMulti->removeClient();
    }

    public function testGetInfo()
    {
        $curlMulti = new CurlMulti();
        $this->assertFalse($curlMulti->getInfo());
    }

    public function testReset()
    {
        $curlMulti = new CurlMulti();
        $curlMulti->addClients([
            'request1' => new Client('http://localhost/', new Curl()),
            'request2' => new Client('http://localhost/', new Curl()),
        ]);
        $this->assertTrue($curlMulti->hasClient('request1'));
        $curlMulti->reset();
        $this->assertFalse($curlMulti->hasClient('request1'));
    }

    public function testDisconnect()
    {
        $curlMulti = new CurlMulti();
        $this->assertTrue($curlMulti->hasResource());
        $curlMulti->disconnect();
        $this->assertFalse($curlMulti->hasResource());
    }

    public function testSend()
    {
        $multiHandler = new CurlMulti();
        $client1      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $client2      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $multiHandler->setWait();
        $running  = null;
        $progress = 0;

        do {
            $multiHandler->send($running);
        } while ($running);

        $info = $multiHandler->getInfo();
        $responses = $multiHandler->getAllResponses();
        $this->assertTrue(is_array($responses));
        $this->assertCount(2, $responses);
        $this->assertEquals(1, $info['msg']);
        $this->assertTrue($multiHandler->isSuccess());
        $this->assertNull($multiHandler->isError());
    }

    public function testSendError()
    {
        $multiHandler = new CurlMulti();
        $client1      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $client2      = new Client(new Client\Request('https://www.popphp.org/bad-url'), $multiHandler);
        $multiHandler->setWait();
        $running  = null;
        $progress = 0;

        do {
            $multiHandler->send($running);
        } while ($running);

        $info = $multiHandler->getInfo();
        $responses = $multiHandler->getAllResponses();
        $this->assertTrue(is_array($responses));
        $this->assertCount(2, $responses);
        $this->assertEquals(1, $info['msg']);
        $this->assertTrue($multiHandler->isError());
    }

    public function testSendAsyncWait()
    {
        $multiHandler = new CurlMulti();
        $client1      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $client2      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $promise      = $multiHandler->sendAsync();
        $response     = $promise->wait();
        $this->assertInstanceOf('Pop\Http\Promise', $promise);
        $this->assertCount(2, $response);
    }

    public function testSendAsyncWaitException()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $multiHandler = new CurlMulti();
        $client1      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $client2      = new Client(new Client\Request('https://www.popphp.org/bad-url'), $multiHandler);
        $promise      = $multiHandler->sendAsync();
        $response     = $promise->wait();
    }

    public function testSendAsyncResolve()
    {
        $multiHandler = new CurlMulti();
        $client1      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $client2      = new Client(new Client\Request('http://localhost/'), $multiHandler);
        $var          = null;
        $promise      = $multiHandler->sendAsync();
        $promise->then(function ($responses) use (&$var) {
            $var = $responses;
        }, true);
        $this->assertInstanceOf('Pop\Http\Promise', $promise);
        $this->assertCount(2, $var);
    }
}