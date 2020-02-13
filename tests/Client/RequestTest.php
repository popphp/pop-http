<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{

    public function testPrepareQueryWithFilters()
    {
        $request = new Request(['strip_tags']);
        $request->setFields(['foo' => '<b>bar</b>']);

        $this->assertEquals('bar', $request->getField('foo'));
    }

}