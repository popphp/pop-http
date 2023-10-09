<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    public function testFilter()
    {
        $request = new Request(['strip_tags']);
        $this->assertEquals('foo', $request->filter('<b>foo</b>'));
    }

    public function testPrepareQueryWithFilters()
    {
        $request = new Request(['strip_tags']);
        $request->setFields(['foo' => '<b>bar</b>']);

        $this->assertEquals('bar', $request->getField('foo'));
    }

    public function testFactory()
    {
        $request  = Request::create();
        $response = Response::create();
        $this->assertInstanceOf('Pop\Http\Client\Request', $request);
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
    }

}