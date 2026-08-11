<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Request;
use PHPUnit\Framework\TestCase;

class AbstractRequestResponseTest extends TestCase
{

    public function testGetHeaderObjectReturnsHeaderInstance()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');
        $this->assertInstanceOf('Pop\Mime\Part\Header', $request->getHeaderObject('Content-Type'));
    }

    public function testGetHeaderObjectReturnsNullWhenAbsent()
    {
        $request = new Request('http://localhost/');
        $this->assertNull($request->getHeaderObject('Content-Type'));
    }

    public function testGetHeaderObjectsReturnsAssocArrayOfHeaderInstances()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');
        $objects = $request->getHeaderObjects();
        $this->assertCount(1, $objects);
        $this->assertInstanceOf('Pop\Mime\Part\Header', $objects['Content-Type']);
    }

    public function testGetHeaderReturnsArrayOfStrings()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');
        $this->assertEquals(['application/json'], $request->getHeader('Content-Type'));
    }

    public function testGetHeaderReturnsEmptyArrayWhenAbsent()
    {
        $request = new Request('http://localhost/');
        $this->assertEquals([], $request->getHeader('Content-Type'));
    }

    public function testGetHeadersReturnsArrayWrappedShape()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');
        $this->assertEquals(['Content-Type' => ['application/json']], $request->getHeaders());
    }

    public function testGetHeaderLineJoinsWithComma()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('', $request->getHeaderLine('X-Missing'));
    }

    public function testWithHeaderReturnsDistinctCloneAndReplaces()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('X-Foo', 'one');
        $clone = $request->withHeader('X-Foo', 'two');

        $this->assertNotSame($request, $clone);
        $this->assertEquals(['one'], $request->getHeader('X-Foo'));
        $this->assertEquals(['two'], $clone->getHeader('X-Foo'));
    }

    public function testWithAddedHeaderAppendsWithoutMutatingOriginal()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('X-Foo', 'one');
        $clone = $request->withAddedHeader('X-Foo', 'two');

        $this->assertEquals(['one'], $request->getHeader('X-Foo'));
        $this->assertEquals(['one', 'two'], $clone->getHeader('X-Foo'));
    }

    public function testWithoutHeaderRemovesOnCloneOnly()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('X-Foo', 'one');
        $clone = $request->withoutHeader('X-Foo');

        $this->assertTrue($request->hasHeader('X-Foo'));
        $this->assertFalse($clone->hasHeader('X-Foo'));
    }

    public function testWithBodyReturnsDistinctCloneWithIndependentContent()
    {
        $request = new Request('http://localhost/');
        $request->setBody('original');
        $newBody = new \Pop\Http\Body('replacement');
        $clone   = $request->withBody($newBody);

        $this->assertNotSame($request, $clone);
        $this->assertEquals('original', $request->getBodyContent());
        $this->assertEquals('replacement', $clone->getBodyContent());
    }

    public function testCloneDoesNotShareHeaderOrBodyState()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('X-Foo', 'one');
        $request->setBody('original');

        $clone = clone $request;
        $clone->addHeader('X-Foo', 'mutated');
        $clone->setBody('mutated');

        $this->assertEquals(['one'], $request->getHeader('X-Foo'));
        $this->assertEquals('original', $request->getBodyContent());
        $this->assertEquals(['mutated'], $clone->getHeader('X-Foo'));
        $this->assertEquals('mutated', $clone->getBodyContent());
    }

    public function testCloneBodyIsIndependentStreamResource()
    {
        $request = new Request('http://localhost/');
        $request->setBody('original');

        $clone = clone $request;
        $clone->getBody()->setContent('mutated');

        $this->assertEquals('original', $request->getBodyContent());
        $this->assertEquals('mutated', $clone->getBodyContent());
    }

    public function testCloneHeaderObjectsAreIndependent()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('X-Foo', 'one');

        $clone = clone $request;
        $clone->getHeaderObject('X-Foo')->addValue('added');

        $this->assertEquals(['one'], $request->getHeader('X-Foo'));
        $this->assertEquals(['one', 'added'], $clone->getHeader('X-Foo'));
    }

    public function testGetBodyReturnsTransientEmptyBodyWhenUnset()
    {
        $request = new Request('http://localhost/');
        $body    = $request->getBody();

        $this->assertInstanceOf('Pop\Http\Body', $body);
        $this->assertEquals('', $body->getContent());
        $this->assertFalse($request->hasBody());
    }

    public function testGetHeaderIsCaseInsensitive()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');

        $this->assertEquals(['application/json'], $request->getHeader('content-type'));
        $this->assertEquals(['application/json'], $request->getHeader('CONTENT-TYPE'));
        $this->assertEquals('application/json', $request->getHeaderLine('CONTENT-TYPE'));
    }

    public function testWithHeaderCaseInsensitiveReplacesRatherThanDuplicates()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');

        $clone = $request->withHeader('CONTENT-TYPE', 'text/plain');

        $headers = $clone->getHeaders();
        $this->assertCount(1, $headers);
        $this->assertEquals(['text/plain'], $clone->getHeader('Content-Type'));
        // Original casing is preserved since resolveHeaderName() found the existing key
        $this->assertArrayHasKey('Content-Type', $headers);
    }

    public function testWithAddedHeaderCaseInsensitiveAppendsToExistingKey()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');

        $clone = $request->withAddedHeader('content-type', 'text/plain');

        $headers = $clone->getHeaders();
        $this->assertCount(1, $headers);
        $this->assertEquals(['application/json', 'text/plain'], $clone->getHeader('Content-Type'));
    }

    public function testWithoutHeaderCaseInsensitiveRemovesExistingKey()
    {
        $request = new Request('http://localhost/');
        $request->addHeader('Content-Type', 'application/json');

        $clone = $request->withoutHeader('content-type');

        $this->assertFalse($clone->hasHeader('Content-Type'));
        $this->assertCount(0, $clone->getHeaders());
    }

}
