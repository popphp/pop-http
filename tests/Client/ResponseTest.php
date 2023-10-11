<?php

namespace Pop\Http\Test\Client;

use Pop\Http;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{

    public function testGetterAndSetter()
    {
        $client = new Http\Client('http://localhost/');
        $client->send();
        $response = $client->getResponse()->getParsedResponse();
        $this->assertTrue(str_contains($response, '<html'));
    }

}