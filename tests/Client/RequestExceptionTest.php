<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Request;
use Pop\Http\Client\RequestException;
use PHPUnit\Framework\TestCase;

class RequestExceptionTest extends TestCase
{

    public function testImplementsRequestExceptionInterface()
    {
        $request   = new Request('http://localhost/');
        $exception = new RequestException('Error: bad request.', $request);

        $this->assertInstanceOf('Psr\Http\Client\RequestExceptionInterface', $exception);
        $this->assertInstanceOf('Psr\Http\Client\ClientExceptionInterface', $exception);
        $this->assertSame($request, $exception->getRequest());
        $this->assertEquals('Error: bad request.', $exception->getMessage());
    }

}
