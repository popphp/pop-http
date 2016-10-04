<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Curl;

class CurlTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $client = new Curl('http://www.popphp.org/version', [
            CURLOPT_POST => true
        ]);
        $client->setField('foo', 'bar');
        $this->assertInstanceOf('Pop\Http\Client\Curl', $client);
        $this->assertEquals('http://www.popphp.org/version', $client->getUrl());
        $this->assertEquals('bar', $client->getField('foo'));
        $this->assertEquals('bar', $client->getFields()['foo']);
        $client->removeField('foo');
        $this->assertNull($client->getField('foo'));
        $this->assertNull($client->getHeader('header'));
        $this->assertNull($client->getRawHeader());
        $this->assertNull($client->getResponse());
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
        $client = new Curl('http://www.popphp.org/version');
        $client->setReturnHeader();
        $this->assertTrue($client->isReturnHeader());
    }

    public function testSetReturnTransfer()
    {
        $client = new Curl('http://www.popphp.org/version');
        $client->setReturnTransfer();
        $this->assertTrue($client->isReturnTransfer());
    }

    public function testSetPost()
    {
        $client = new Curl('http://www.popphp.org/version');
        $client->setPost();
        $this->assertTrue($client->isPost());
    }

    public function testSendGetQuery()
    {
        $client = new Curl('http://www.popphp.org/version');
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
