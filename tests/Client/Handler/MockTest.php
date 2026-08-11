<?php

namespace Pop\Http\Test\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\Mock;
use PHPUnit\Framework\TestCase;

class MockTest extends TestCase
{

    public function testImplementsHandlerInterface()
    {
        $mock = new Mock();
        $this->assertInstanceOf('Pop\Http\Client\Handler\HandlerInterface', $mock);
    }

    public function testQueueReturnsResponsesInFifoOrder()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 200]));
        $mock->queue(new Response(['code' => 201]));

        $mock->prepare(new Request('http://localhost/a'));
        $response1 = $mock->send();

        $mock->prepare(new Request('http://localhost/b'));
        $response2 = $mock->send();

        $this->assertEquals(200, $response1->getCode());
        $this->assertEquals(201, $response2->getCode());
    }

    public function testSendThrowsWhenQueueIsEmpty()
    {
        $mock = new Mock();
        $mock->prepare(new Request('http://localhost/'));

        $this->expectException('Pop\Http\Client\Handler\Exception');
        $mock->send();
    }

    public function testSendThrowsQueuedThrowableVerbatim()
    {
        $mock      = new Mock();
        $exception = new \RuntimeException('Simulated connection failure.');
        $mock->queue($exception);
        $mock->prepare(new Request('http://localhost/'));

        try {
            $mock->send();
            $this->fail('Expected the queued exception to be thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame($exception, $e);
        }
    }

    public function testGetRequestsRecordsEveryDispatchedRequestInOrder()
    {
        $mock = new Mock();
        $mock->queue(new Response());
        $mock->queue(new Response());

        $requestA = new Request('http://localhost/a', 'GET');
        $mock->prepare($requestA);
        $mock->send();

        $requestB = new Request('http://localhost/b', 'POST');
        $mock->prepare($requestB);
        $mock->send();

        // History entries are clones (snapshots), not the same instances - see
        // testGetRequestsSnapshotsEachRequestOnReusedClient for the regression this guards.
        $requests = $mock->getRequests();
        $this->assertCount(2, $requests);
        $this->assertNotSame($requestA, $requests[0]);
        $this->assertEquals('http://localhost/a', $requests[0]->getUriAsString());
        $this->assertEquals('GET', $requests[0]->getMethod());
        $this->assertNotSame($requestB, $requests[1]);
        $this->assertEquals('http://localhost/b', $requests[1]->getUriAsString());
        $this->assertEquals('POST', $requests[1]->getMethod());
        $this->assertSame($requests[1], $mock->getLastRequest());
    }

    public function testGetLastRequestReturnsNullWhenNothingSent()
    {
        $mock = new Mock();
        $this->assertNull($mock->getLastRequest());
    }

    public function testPrepareAddsAuthHeader()
    {
        $mock    = new Mock();
        $request = new Request('http://localhost/');
        $auth    = Auth::createBearer('token123');

        $mock->prepare($request, $auth);

        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertEquals('Bearer token123', $request->getHeaderLine('Authorization'));
    }

    public function testPreparePreparesUnpreparedData()
    {
        $mock    = new Mock();
        $request = Request::createJson('http://localhost/', 'POST', ['foo' => 'bar']);

        $this->assertFalse($request->getData()->isPrepared());
        $mock->prepare($request);
        $this->assertTrue($request->getData()->isPrepared());
    }

    public function testResetDoesNotClearQueueOrHistory()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 200]));
        $mock->queue(new Response(['code' => 201]));

        $mock->prepare(new Request('http://localhost/a'));
        $mock->send();

        $mock->reset();

        $mock->prepare(new Request('http://localhost/b'));
        $response2 = $mock->send();

        $this->assertEquals(201, $response2->getCode());
        $this->assertCount(2, $mock->getRequests());
    }

    public function testDisconnectClearsQueueAndHistory()
    {
        $mock = new Mock();
        $mock->queue(new Response());

        $mock->prepare(new Request('http://localhost/'));
        $mock->send();

        $mock->disconnect();

        $this->assertCount(0, $mock->getRequests());
        $this->assertNull($mock->getLastRequest());

        $mock->prepare(new Request('http://localhost/'));
        $this->expectException('Pop\Http\Client\Handler\Exception');
        $mock->send();
    }

    public function testHasResourceIsFalseAndResourceIsNull()
    {
        $mock = new Mock();
        $this->assertFalse($mock->hasResource());
        $this->assertNull($mock->getResource());
        $this->assertNull($mock->resource());
    }

    public function testWhenMatcherTakesPriorityOverQueue()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 500]));
        $mock->when(
            fn(Request $request) => $request->getMethod() === 'GET',
            new Response(['code' => 200])
        );

        $mock->prepare(new Request('http://localhost/users', 'GET'));
        $response = $mock->send();

        $this->assertEquals(200, $response->getCode());
    }

    public function testWhenFallsThroughToQueueWhenNoMatcherMatches()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 201]));
        $mock->when(
            fn(Request $request) => $request->getMethod() === 'DELETE',
            new Response(['code' => 500])
        );

        $mock->prepare(new Request('http://localhost/users', 'POST'));
        $response = $mock->send();

        $this->assertEquals(201, $response->getCode());
    }

    public function testWhenChecksMatchersInRegistrationOrderFirstMatchWins()
    {
        $mock = new Mock();
        $mock->when(fn(Request $request) => true, new Response(['code' => 200]));
        $mock->when(fn(Request $request) => true, new Response(['code' => 201]));

        $mock->prepare(new Request('http://localhost/'));
        $response = $mock->send();

        $this->assertEquals(200, $response->getCode());
    }

    public function testWhenMatcherCanThrowVerbatim()
    {
        $mock      = new Mock();
        $exception = new \RuntimeException('Simulated timeout.');
        $mock->when(fn(Request $request) => true, $exception);
        $mock->prepare(new Request('http://localhost/'));

        try {
            $mock->send();
            $this->fail('Expected the matched exception to be thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame($exception, $e);
        }
    }

    public function testDisconnectClearsMatchersToo()
    {
        $mock = new Mock();
        $mock->when(fn(Request $request) => true, new Response(['code' => 200]));
        $mock->disconnect();

        $mock->prepare(new Request('http://localhost/'));
        $this->expectException('Pop\Http\Client\Handler\Exception');
        $mock->send();
    }

    public function testPrepareSetsUri()
    {
        $mock    = new Mock();
        $request = new Request('http://localhost/foo', 'GET');

        $mock->prepare($request);

        $this->assertTrue($mock->hasUri());
        $this->assertEquals($request->getUriAsString(), $mock->getUri());
    }

    public function testClientRenderDoesNotThrowWithMockHandler()
    {
        $mock   = new Mock();
        $client = new \Pop\Http\Client('http://localhost/foo', $mock);

        $rendered = $client->render();

        $this->assertStringContainsString('GET /foo HTTP/', $rendered);
        $this->assertStringContainsString('Host: localhost', $rendered);
    }

    public function testClientAcceptsVerifyPeerAndAllowSelfSignedOptionsWithMockHandler()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 200, 'body' => 'ok']));

        $client = new \Pop\Http\Client('https://localhost/', $mock, [
            'verify_peer'       => true,
            'allow_self_signed' => true,
        ]);

        $response = $client->send();

        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
        $this->assertEquals(200, $response->getCode());
    }

    public function testSendWithoutPrepareDoesNotThrowCloneError()
    {
        // Regression test for: clone $this->request throwing Error when $this->request is null
        // This verifies that send() can be called directly without a prior prepare(),
        // which is a legitimate pattern in tests (driver directly tests the handler).
        $mock = new Mock();
        $mock->queue(new Response(['code' => 200]));

        // Call send() WITHOUT prepare() - $this->request is null
        $response = $mock->send();

        // Verify the queued response is returned
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
        $this->assertEquals(200, $response->getCode());

        // Verify getRequests() stays empty (not [null]) - no null entry was recorded
        $requests = $mock->getRequests();
        $this->assertCount(0, $requests);
    }

}
