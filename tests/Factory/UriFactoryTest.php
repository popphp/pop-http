<?php

namespace Pop\Http\Test\Factory;

use Pop\Http\Factory\UriFactory;
use PHPUnit\Framework\TestCase;

class UriFactoryTest extends TestCase
{

    public function testImplementsUriFactoryInterface()
    {
        $this->assertInstanceOf('Psr\Http\Message\UriFactoryInterface', new UriFactory());
    }

    public function testCreateUri()
    {
        $uri = (new UriFactory())->createUri('http://localhost/foo');
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
        $this->assertEquals('localhost', $uri->getHost());
        $this->assertEquals('/foo', $uri->getPath());
    }

    public function testCreateUriWithNoArgument()
    {
        $uri = (new UriFactory())->createUri();
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
        $this->assertEquals('', $uri->getHost());
    }

}
