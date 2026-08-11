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

    public function testImplementsUriInterface()
    {
        $uri = new Uri('http://localhost/');
        $this->assertInstanceOf('Psr\Http\Message\UriInterface', $uri);
    }

    public function testGetPortReturnsNullForStandardPort()
    {
        $uri = new Uri('http://localhost:80/');
        $this->assertNull($uri->getPort());

        $uri = new Uri('https://localhost:443/');
        $this->assertNull($uri->getPort());
    }

    public function testGetPortReturnsIntForNonStandardPort()
    {
        $uri = new Uri('http://localhost:8000/');
        $this->assertSame(8000, $uri->getPort());
    }

    public function testGetPortReturnsNullWhenAbsent()
    {
        $uri = new Uri('http://localhost/');
        $this->assertNull($uri->getPort());
    }

    public function testGetAuthority()
    {
        $uri = new Uri('https://user:pass@www.domain.com:8000/foo');
        $this->assertEquals('user:pass@www.domain.com:8000', $uri->getAuthority());
    }

    public function testGetAuthorityIsEmptyWithoutHost()
    {
        $uri = new Uri();
        $this->assertEquals('', $uri->getAuthority());
    }

    public function testGetUserInfo()
    {
        $uri = new Uri('https://user:pass@www.domain.com/foo');
        $this->assertEquals('user:pass', $uri->getUserInfo());

        $uri = new Uri('https://www.domain.com/foo');
        $this->assertEquals('', $uri->getUserInfo());
    }

    public function testGetPath()
    {
        $uri = new Uri('https://www.domain.com/foo/bar?query=123');
        $this->assertEquals('/foo/bar', $uri->getPath());
    }

    public function testEmptyComponentsReturnEmptyStringNotNull()
    {
        $uri = new Uri();
        $this->assertSame('', $uri->getScheme());
        $this->assertSame('', $uri->getHost());
        $this->assertSame('', $uri->getQuery());
        $this->assertSame('', $uri->getFragment());
        $this->assertSame('', $uri->getUserInfo());
    }

    public function testWithMethodsReturnDistinctImmutableClones()
    {
        $uri     = new Uri('https://www.domain.com/foo?query=1#frag');
        $withNew = $uri->withScheme('http')
            ->withHost('other.com')
            ->withPort(9000)
            ->withPath('/bar')
            ->withQuery('a=1')
            ->withFragment('top')
            ->withUserInfo('bob', 'secret');

        $this->assertNotSame($uri, $withNew);
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('www.domain.com', $uri->getHost());
        $this->assertEquals('/foo', $uri->getPath());
        $this->assertEquals('query=1', $uri->getQuery());
        $this->assertEquals('frag', $uri->getFragment());

        $this->assertEquals('http', $withNew->getScheme());
        $this->assertEquals('other.com', $withNew->getHost());
        $this->assertEquals(9000, $withNew->getPort());
        $this->assertEquals('/bar', $withNew->getPath());
        $this->assertEquals('a=1', $withNew->getQuery());
        $this->assertEquals('top', $withNew->getFragment());
        $this->assertEquals('bob:secret', $withNew->getUserInfo());
    }

    public function testWithSchemeEmptyStringRemovesScheme()
    {
        $uri = new Uri('https://www.domain.com/foo');
        $this->assertTrue($uri->hasScheme());
        $this->assertEquals('https', $uri->getScheme());

        $withoutScheme = $uri->withScheme('');
        $this->assertFalse($withoutScheme->hasScheme());
        $this->assertSame('', $withoutScheme->getScheme());
        $this->assertNotSame($uri, $withoutScheme);
    }

    public function testWithHostEmptyStringRemovesHost()
    {
        $uri = new Uri('https://www.domain.com:8000/foo');
        $this->assertTrue($uri->hasHost());
        $this->assertEquals('www.domain.com', $uri->getHost());

        $withoutHost = $uri->withHost('');
        $this->assertFalse($withoutHost->hasHost());
        $this->assertSame('', $withoutHost->getHost());
        $this->assertNotSame($uri, $withoutHost);
    }

}