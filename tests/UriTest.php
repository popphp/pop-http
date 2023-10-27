<?php

namespace Pop\Http\Test;

use Pop\Http\Uri;
use PHPUnit\Framework\TestCase;

class UriTest extends TestCase
{

    public function testConstructor()
    {
        $uri = new Uri('http://localhost/');
        $this->assertInstanceOf('Pop\Http\Uri', $uri);
        $this->assertEquals('localhost', $uri->getHost());
    }

    public function testConstructorException()
    {
        $this->expectException('Pop\Http\Exception');
        $uri = new Uri('http:///localhost');
    }

    public function testGettersAndSetters()
    {
        $uri = Uri::create('https://username:password@www.domain.com:8000/foo/bar?query=123&filter=456#name');
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('www.domain.com', $uri->getHost());
        $this->assertEquals('www.domain.com:8000', $uri->getFullHost());
        $this->assertEquals('username', $uri->getUsername());
        $this->assertEquals('password', $uri->getPassword());
        $this->assertEquals('8000', $uri->getPort());
        $this->assertEquals('/foo/bar', $uri->getUri());
        $this->assertEquals('query=123&filter=456', $uri->getQuery());
        $this->assertEquals(['query' => 123, 'filter' => 456], $uri->getQueryAsArray());
        $this->assertEquals('name', $uri->getFragment());

        $this->assertTrue($uri->hasScheme());
        $this->assertTrue($uri->hasHost());
        $this->assertTrue($uri->hasUsername());
        $this->assertTrue($uri->hasPassword());
        $this->assertTrue($uri->hasPort());
        $this->assertTrue($uri->hasUri());
        $this->assertTrue($uri->hasQuery());
        $this->assertTrue($uri->hasFragment());
    }

    public function testGetAndSetBasePath()
    {
        $uri = Uri::create('/foo/bar', '/my-folder');
        $this->assertTrue($uri->hasBasePath());
        $this->assertEquals('/my-folder', $uri->getBasePath());
        $this->assertEquals('/foo/bar', $uri->getUri());
        $this->assertEquals('/my-folder/foo/bar', $uri->getFullUri());
    }

    public function testGetAndSetSegments()
    {
        $uri = Uri::create('/foo/bar');
        $this->assertTrue($uri->hasSegments());
        $this->assertTrue($uri->hasSegment(0));
        $this->assertTrue($uri->hasSegment(1));
        $this->assertCount(2, $uri->getSegments());
        $this->assertEquals('foo', $uri->getSegment(0));
        $this->assertEquals('bar', $uri->getSegment(1));
    }

    public function testRender()
    {
        $uri = new Uri();
        $uri->setScheme('https')
            ->setUsername('username')
            ->setPassword('password')
            ->setHost('www.domain.com')
            ->setPort(8000)
            ->setUri('/foo/bar')
            ->setQuery(['query' => 123, 'filter=456'])
            ->setFragment('name');

        $this->assertEquals('https://username:password@www.domain.com:8000/foo/bar?query=123&0=filter%3D456#name', (string)$uri);
    }

    public function testServerUri()
    {
        $_SERVER['REQUEST_URI'] = '/foo/bar';
        $uri = new Uri();
        $this->assertEquals('/foo/bar', $uri->getUri());
        $this->assertEquals('/foo/bar', $uri->getFullUri());
    }

}