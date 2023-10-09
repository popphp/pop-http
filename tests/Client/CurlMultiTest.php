<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Curl;
use PHPUnit\Framework\TestCase;

class CurlMultiTest extends TestCase
{

    public function testConstructor()
    {
        $mh1 = new Curl\MultiHandler([CURLMOPT_MAXCONNECTS => 1]);
        $mh2 = Curl\MultiHandler::create();
        $this->assertInstanceOf('Pop\Http\Client\Curl\MultiHandler', $mh1);
        $this->assertInstanceOf('Pop\Http\Client\Curl\MultiHandler', $mh2);
        $this->assertEquals(1, $mh1->getOption(CURLMOPT_MAXCONNECTS));
        $this->assertTrue($mh1->hasOption(CURLMOPT_MAXCONNECTS));
        $this->assertTrue($mh1->hasResource());
        $this->assertNotNull($mh1->getResource());
        $this->assertNotNull($mh1->resource());
        $this->assertEquals(0, $mh1->getErrorNumber());
        $this->assertEquals('No error', $mh1->getErrorMessage());
        $this->assertNotNull($mh1->version());
        $this->assertFalse($mh1->getInfo());
        $this->assertEquals(0, $mh1->setWait(2.0));
    }

    public function testAddRequest()
    {
        $mh = new Curl\MultiHandler();
        $curl1 = new Curl('http://localhost/');

        $mh->addRequests([$curl1]);
        $this->assertEquals(1, count($mh->getRequests()));

        $mh->removeRequest(null, $curl1);
        $this->assertEquals(0, count($mh->getRequests()));
    }

    public function testAddRequests()
    {
        $mh = new Curl\MultiHandler();
        $curl1 = new Curl('http://localhost/');
        $curl2 = new Curl('http://localhost/');

        $mh->addRequests(['request1' => $curl1, 'request2' => $curl2]);

        $this->assertEquals($curl1, $mh->getRequest('request1'));
        $this->assertEquals($curl2, $mh->getRequest('request2'));
        $this->assertTrue($mh->hasRequest('request1'));
        $this->assertTrue($mh->hasRequest('request2'));
        $this->assertEquals(2, count($mh->getRequests()));

        $mh->removeRequest('request1');
        $mh->removeRequest('request2');

        $this->assertNull($mh->getRequest('request1'));
        $this->assertNull($mh->getRequest('request2'));
        $this->assertFalse($mh->hasRequest('request1'));
        $this->assertFalse($mh->hasRequest('request2'));
    }

    public function testSend()
    {
        $mh = new Curl\MultiHandler();
        $curl = new Curl('http://localhost/');;

        $mh->addRequest($curl);
        $running  = null;
        do {
            $mh->send($running);
        } while ($running);

        $curl = $mh->processResponse($curl);

        $this->assertEquals(200, $curl->getResponseCode());
    }

    public function testRemoveRequestException()
    {
        $this->expectException('Pop\Http\Client\Curl\Exception');
        $mh = new Curl\MultiHandler();
        $mh->removeRequest();
    }

    public function testReset()
    {
        $mh = new Curl\MultiHandler();
        $curl1 = new Curl('http://localhost/');

        $mh->addRequests(['request1' => $curl1]);
        $mh->reset();

        $this->assertEquals(0, count($mh->getRequests()));
    }

    public function testDisconnect()
    {
        $mh = new Curl\MultiHandler();
        $curl1 = new Curl('http://localhost/');

        $mh->addRequests(['request1' => $curl1]);
        $mh->disconnect();

        $this->assertEquals(0, count($mh->getRequests()));
        $this->assertNull($mh->resource());
    }

}
