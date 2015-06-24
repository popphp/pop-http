<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Stream;

class StreamTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $client = new Stream('http://www.popphp.org/', ['http'=> ['method' => 'GET']]);
        $this->assertInstanceOf('Pop\Http\Client\Stream', $client);
        $this->assertFalse($client->isPost());
        $this->assertEquals('GET', $client->getContext()['http']['method']);
        $this->assertEquals('r', $client->getMode());
        $client->send();
        $client->disconnect();
        $this->assertFalse($client->hasResource());
    }

}
