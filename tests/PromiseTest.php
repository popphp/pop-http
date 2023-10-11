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
        $var = null;
        $promise = new Promise(new Client());
        $promise->setCancel(function($value) use (&$var) {
            $var = 123;
        });
        $this->assertTrue($promise->hasCancel());
        $this->assertInstanceOf('Pop\Utils\CallableObject', $promise->getCancel());
        $promise->cancel();
        $this->assertEquals(123, $var);
    }

    public function testCancelException1()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $promise = new Promise(new Client());
        $promise->setCancel('####');
    }

    public function testCancelException2()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $promise = new Promise(new Client());
        $promise->cancel();
    }

    public function testCancelWrongState()
    {
        $promise = new Promise(new Client());
        $promise->setState(Promise::FULFILLED);
        $var = $promise->cancel();
        $this->assertNull($var);
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

    public function testWaitError()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $client  = new Client(new Client\Request('https://www.popphp.org/bad-url'));
        $promise = new Promise($client);
        $response = $promise->wait();
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

    public function testResolveError()
    {
        $client  = new Client(new Client\Request('https://www.popphp.org/bad-url'));
        $var     = null;
        $promise = new Promise($client);
        $promise->then(function(Client\Response $response) {
            echo 123;
        })->catch(function(Client\Response $response) use (&$var) {
            $var = $response->getCode();
        }, true);

        $this->assertEquals(404, $var);
    }

    public function testResolveException1()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $client  = new Client(new Client\Request('http://localhost/'));
        $promise = new Promise($client);
        $promise->resolve();
    }

    public function testResolveException2()
    {
        $this->expectException('Pop\Http\Promise\Exception');
        $client  = new Client(new Client\Request('https://www.popphp.org/bad-url'));
        $promise = new Promise($client);
        $promise->resolve();
    }

    public function testResolveWrongState()
    {
        $promise = new Promise(new Client());
        $promise->setState(Promise::FULFILLED);
        $var = $promise->resolve();
        $this->assertNull($var);
    }

    public function testResolveWithFinally()
    {
        $client  = new Client(new Client\Request('http://localhost/'));
        $var     = null;
        $promise = new Promise($client);
        $promise->then(function(Client\Response $response) {
            $test = 123;
        })->finally(function(Promise $promise) use (&$var) {
            $var = 456;
        }, true);

        $this->assertEquals(456, $var);
    }

    public function testForward()
    {
        $promise1 = Client::getAsync('http://localhost/');
        $promise2 = Client::getAsync('http://localhost/');

        $promise1->then(function(Client\Response $response) use ($promise2) {
            $test = 456;
            return $promise2;
        })->then(function(Client\Response $response) use (&$var) {
            $var = 123;
        })->catch(function(Client\Response $response) {
            $test = 789;
        })->finally(function() {});

        $promise1->setCancel(function(){});

        $promise1->resolve();
        $this->assertEquals(123, $var);
    }

}
