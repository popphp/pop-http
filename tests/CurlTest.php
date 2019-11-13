<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Curl;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{

    public function testConstructor()
    {
        $client = new Curl('https://www.popphp.org/version', 'POST', [
            CURLOPT_POST => true
        ]);
        $client->setField('foo', 'bar');
        $this->assertInstanceOf('Pop\Http\Client\Curl', $client);
        $this->assertEquals('https://www.popphp.org/version', $client->getUrl());
        $this->assertEquals('bar', $client->getField('foo'));
        $this->assertEquals('bar', $client->getFields()['foo']);
        $client->removeField('foo');
        $this->assertNull($client->getField('foo'));
        $this->assertNull($client->getResponseHeader('header'));
        $this->assertTrue($client->hasResource());
        $this->assertTrue(is_resource($client->getResource()));
        $this->assertTrue(is_resource($client->curl()));
        $this->assertTrue($client->getOption(CURLOPT_POST));
        $client->setFields([
            'var' => 123
        ]);
        $this->assertEquals(123, $client->getField('var'));
        $client->send();
    }

    public function testSetReturnHeader()
    {
        $client = new Curl('https://www.popphp.org/version');
        $client->setReturnHeader();
        $this->assertTrue($client->isReturnHeader());
    }

    public function testSetReturnTransfer()
    {
        $client = new Curl('https://www.popphp.org/version');
        $client->setReturnTransfer();
        $this->assertTrue($client->isReturnTransfer());
    }

    public function testSendGetQuery()
    {
        $client = new Curl('https://www.popphp.org/version');
        $client->setFields([
            'var' => '123',
            'foo' => 'bar'
        ]);

        $client->send();
        $this->assertTrue(is_array($client->version()));
        $client->disconnect();
        $this->assertFalse($client->hasResource());
    }

}
