<?php

namespace Pop\Http\Test;

use Pop\Http\Promise;
use Pop\Http\Client;
use PHPUnit\Framework\TestCase;

class PromiseTest extends TestCase
{

    public function testConstructor()
    {
        $promise1 = new Promise(new Client());
        $promise2 = Promise::create(new Client());
        $this->assertInstanceOf('Pop\Http\Promise', $promise1);
        $this->assertInstanceOf('Pop\Http\Promise', $promise2);
        $this->assertTrue($promise1->hasPromiser());
        $this->assertTrue($promise2->hasPromiser());
        $this->assertInstanceOf('Pop\Http\Client', $promise1->getPromiser());
        $this->assertInstanceOf('Pop\Http\Client', $promise2->getPromiser());
    }

    public function testSuccess()
    {
        $promise = new Promise(new Client());
        $promise->setSuccess(function($value) { });
        $this->assertTrue($promise->hasSuccess());
        $this->assertTrue($promise->hasSuccess(0));
        $this->assertCount(1, $promise->getSuccess());
        $this->assertInstanceOf('Pop\Utils\CallableObject', $promise->getSuccess()[0]);
        $this->assertInstanceOf('Pop\Utils\CallableObject', $promise->getSuccess(0));
    }

    public function testSuccessException()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $promise = new Promise(new Client());
        $promise->setSuccess('####');
    }

    public function testFailure()
    {
        $promise = new Promise(new Client());
        $promise->setFailure(function($value) { });
        $this->assertTrue($promise->hasFailure());
        $this->assertInstanceOf('Pop\Utils\CallableObject', $promise->getFailure());
    }

    public function testFailureException()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $promise = new Promise(new Client());
        $promise->setFailure('####');
    }

    public function testCancel()
    {
        $promise = new Promise(new Client());
        $promise->setCancel(function($value) { });
        $this->assertTrue($promise->hasCancel());
        $this->assertInstanceOf('Pop\Utils\CallableObject', $promise->getCancel());
    }

    public function testCancelException()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $promise = new Promise(new Client());
        $promise->setCancel('####');
    }

    public function testFinally()
    {
        $promise = new Promise(new Client());
        $promise->setFinally(function($value) { });
        $this->assertTrue($promise->hasFinally());
        $this->assertInstanceOf('Pop\Utils\CallableObject', $promise->getFinally());
    }

    public function testFinallyException()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $promise = new Promise(new Client());
        $promise->setFinally('####');
    }

    public function testState()
    {
        $promise = new Promise(new Client());
        $promise->setState(Promise::FULFILLED);
        $this->assertTrue($promise->hasState());
        $this->assertTrue($promise->isFulfilled());
        $this->assertFalse($promise->isPending());
        $this->assertFalse($promise->isRejected());
        $this->assertFalse($promise->isCancelled());
        $this->assertEquals(Promise::FULFILLED, $promise->getState());
    }

    public function testStateException()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $promise = new Promise(new Client());
        $promise->setState('BAD_STATE');
    }

    public function testWait()
    {
        $client = new Client(new Client\Request('http://localhost/'));
        $promise = new Promise($client);
        $response = $promise->wait();
        $this->assertTrue(str_contains($response->getParsedResponse(), '<html'));
        $response = $promise->wait();
        $this->assertTrue(str_contains($response->getParsedResponse(), '<html'));
    }

    public function testResolve()
    {
        $client  = new Client(new Client\Request('http://localhost/'));
        $var     = null;
        $promise = new Promise($client);
        $promise->then(function(Client\Response $response) use (&$var){
            $var = $response->getParsedResponse();
        }, true);

        $this->assertTrue(str_contains($var, '<html'));
    }

}
