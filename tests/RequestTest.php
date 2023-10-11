<?php

namespace Pop\Http\Test;

use Pop\Http\Client\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    public function testConstructor()
    {
        $request = new Request('http://localhost/');
        $this->assertInstanceOf('Pop\Http\Client\Request', $request);
    }

    public function testGetterAndSetter()
    {
        $request = new Request('http://localhost/');
        $this->assertTrue($request->hasUri());
        $this->assertInstanceOf('Pop\Http\Uri', $request->getUri());
        $this->assertEquals('http://localhost/', $request->getUriAsString());
    }

}