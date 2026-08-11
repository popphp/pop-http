<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Client\Handler\Exception as HandlerException;
use Pop\Http\Client\Middleware\RetryMiddleware;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Body;
use PHPUnit\Framework\TestCase;

class RetryMiddlewareTest extends TestCase
{

    protected function noSleep(): callable
    {
        return function (float $seconds): void {
            // no-op: tests never actually wait
        };
    }

    public function testRetriesOnRetryableStatusCodeUpToMaxThenReturnsFinalResponse()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 503]),
            new Response(['code' => 503]),
            new Response(['code' => 503]),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $request    = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals(4, $handler->callCount);
    }

    public function testStopsRetryingAfterMaxRetriesAndReturnsLastResponse()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 503]),
            new Response(['code' => 503]),
            new Response(['code' => 503]),
            new Response(['code' => 503]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $request    = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(503, $response->getCode());
        $this->assertEquals(4, $handler->callCount);
    }

    public function testNonIdempotentMethodNotRetriedByDefaultEvenOnRetryableStatus()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 503]),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $request    = new Request('http://localhost/', 'POST');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(503, $response->getCode());
        $this->assertEquals(1, $handler->callCount);
    }

    public function testNonRetryableStatusCodeNotRetried()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 404]),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $request    = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(404, $response->getCode());
        $this->assertEquals(1, $handler->callCount);
    }

    public function testRetriesOnDefaultNetworkException()
    {
        $handler = new ScriptedHandler([
            new HandlerException('Simulated network blip.'),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $request    = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals(2, $handler->callCount);
    }

    public function testNonRetryableExceptionTypePropagatesImmediately()
    {
        $handler = new ScriptedHandler([
            new \RuntimeException('Not a network exception.'),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $request    = new Request('http://localhost/', 'GET');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not a network exception.');

        try {
            $middleware->process($request, $handler);
        } finally {
            $this->assertEquals(1, $handler->callCount);
        }
    }

    public function testExceptionExhaustionRethrowsLastExceptionUnmodified()
    {
        $handler = new ScriptedHandler([
            new HandlerException('failure 1'),
            new HandlerException('failure 2'),
            new HandlerException('failure 3'),
            new HandlerException('failure 4 - final'),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $request    = new Request('http://localhost/', 'GET');

        $this->expectException(HandlerException::class);
        $this->expectExceptionMessage('failure 4 - final');

        try {
            $middleware->process($request, $handler);
        } finally {
            $this->assertEquals(4, $handler->callCount);
        }
    }

    public function testRetryAfterHeaderInSecondsOverridesComputedDelay()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 429, 'headers' => ['Retry-After' => '2']]),
            new Response(['code' => 200]),
        ]);

        $recordedDelays = [];
        $middleware = (new RetryMiddleware(3))->setSleeper(function (float $seconds) use (&$recordedDelays) {
            $recordedDelays[] = $seconds;
        });
        $request = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(1, $recordedDelays);
        $this->assertEqualsWithDelta(2.0, $recordedDelays[0], 0.0001);
    }

    public function testRetryAfterHeaderIsCaseInsensitive()
    {
        // HTTP/2 and HTTP/3 mandate lowercase field names on the wire, so a real
        // server's header arrives stored as 'retry-after', not 'Retry-After'.
        $handler = new ScriptedHandler([
            new Response(['code' => 429, 'headers' => ['retry-after' => '2']]),
            new Response(['code' => 200]),
        ]);

        $recordedDelays = [];
        $middleware = (new RetryMiddleware(3))->setSleeper(function (float $seconds) use (&$recordedDelays) {
            $recordedDelays[] = $seconds;
        });
        $request = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(1, $recordedDelays);
        $this->assertEqualsWithDelta(2.0, $recordedDelays[0], 0.0001);
    }

    public function testRetryAfterHeaderIsClampedToMaxDelay()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 429, 'headers' => ['Retry-After' => '3600']]),
            new Response(['code' => 200]),
        ]);

        $recordedDelays = [];
        $middleware = (new RetryMiddleware(3))
            ->setMaxDelay(5.0)
            ->setSleeper(function (float $seconds) use (&$recordedDelays) {
                $recordedDelays[] = $seconds;
            });
        $request = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(1, $recordedDelays);
        $this->assertEqualsWithDelta(5.0, $recordedDelays[0], 0.0001);
    }

    public function testRetryAfterHeaderAsHttpDateOverridesComputedDelay()
    {
        $future = gmdate('D, d M Y H:i:s \G\M\T', time() + 5);

        $handler = new ScriptedHandler([
            new Response(['code' => 503, 'headers' => ['Retry-After' => $future]]),
            new Response(['code' => 200]),
        ]);

        $recordedDelays = [];
        $middleware = (new RetryMiddleware(3))->setSleeper(function (float $seconds) use (&$recordedDelays) {
            $recordedDelays[] = $seconds;
        });
        $request = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(1, $recordedDelays);
        $this->assertEqualsWithDelta(5.0, $recordedDelays[0], 1.0);
    }

    public function testNonSeekableBodySkipsRetryOnResponsePath()
    {
        $body = new Body();
        $body->setContentFromStream(fopen('php://output', 'w'));

        $request = new Request('http://localhost/', 'PUT');
        $request->setBody($body);

        $handler = new ScriptedHandler([
            new Response(['code' => 503]),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());

        $response = $middleware->process($request, $handler);

        $this->assertEquals(503, $response->getCode());
        $this->assertEquals(1, $handler->callCount);
    }

    public function testNonSeekableStreamReportingZeroSizeSkipsRetry()
    {
        // Body::getSize() returns a real 0 (not null) for streams fstat() CAN stat but
        // which report zero length - e.g. pipes and sockets - not just for genuinely
        // unstatable streams like php://output. A non-rewindable socket must still be
        // caught by the seekability gate even though its reported size is 0, otherwise
        // canRetryBody() waves it through and a retry resends a partially-consumed,
        // non-rewindable stream.
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
        $this->assertIsArray($pair, 'stream_socket_pair() must succeed for this test to be meaningful.');
        [$socket, $peer] = $pair;

        $body = new Body();
        $body->setContentFromStream($socket);

        // Sanity-check the premise this test depends on: a real, statable stream
        // reporting size 0 while being non-seekable.
        $this->assertSame(0, $body->getSize());
        $this->assertFalse($body->isSeekable());

        $request = new Request('http://localhost/', 'PUT');
        $request->setBody($body);

        $handler = new ScriptedHandler([
            new Response(['code' => 503]),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());

        $response = $middleware->process($request, $handler);

        fclose($socket);
        fclose($peer);

        $this->assertEquals(503, $response->getCode());
        $this->assertEquals(1, $handler->callCount);
    }

    public function testNonSeekableBodySkipsRetryOnExceptionPath()
    {
        $body = new Body();
        $body->setContentFromStream(fopen('php://output', 'w'));

        $request = new Request('http://localhost/', 'PUT');
        $request->setBody($body);

        $handler = new ScriptedHandler([
            new HandlerException('Simulated network blip.'),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());

        $this->expectException(HandlerException::class);

        try {
            $middleware->process($request, $handler);
        } finally {
            $this->assertEquals(1, $handler->callCount);
        }
    }

    public function testBodyIsRewoundBeforeRetry()
    {
        $body = new Body();
        $body->setContent('payload');

        $request = new Request('http://localhost/', 'PUT');
        $request->setBody($body);

        $positionsAtCallStart = [];

        $handler = new class($positionsAtCallStart) implements \Pop\Http\Client\Middleware\RequestHandlerInterface {
            public int $callCount = 0;
            public function __construct(protected array &$positionsRef) {}
            public function handle(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->positionsRef[] = $request->getBody()->tell();
                $request->getBody()->read(3);
                $this->callCount++;
                return ($this->callCount === 1)
                    ? new Response(['code' => 503])
                    : new Response(['code' => 200]);
            }
        };

        $middleware = (new RetryMiddleware(3))->setSleeper($this->noSleep());
        $middleware->process($request, $handler);

        $this->assertEquals([0, 0], $positionsAtCallStart);
    }

    public function testOnRetryCallbackFiresWithCorrectArguments()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 503]),
            new Response(['code' => 200]),
        ]);

        $captured = [];
        $middleware = (new RetryMiddleware(3))
            ->setSleeper($this->noSleep())
            ->setOnRetry(function (int $attempt, $request, $response, $exception, float $delaySeconds) use (&$captured) {
                $captured[] = [$attempt, $request, $response, $exception, $delaySeconds];
            });
        $request = new Request('http://localhost/', 'GET');

        $middleware->process($request, $handler);

        $this->assertCount(1, $captured);
        [$attempt, $capturedRequest, $capturedResponse, $capturedException, $delay] = $captured[0];
        $this->assertEquals(1, $attempt);
        $this->assertSame($request, $capturedRequest);
        $this->assertEquals(503, $capturedResponse->getCode());
        $this->assertNull($capturedException);
        $this->assertIsFloat($delay);
    }

    public function testCustomRetryableMethodsAllowsPostToBeRetried()
    {
        $handler = new ScriptedHandler([
            new Response(['code' => 503]),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))
            ->setSleeper($this->noSleep())
            ->setRetryableMethods(['GET', 'POST']);
        $request = new Request('http://localhost/', 'POST');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals(2, $handler->callCount);
    }

    public function testCustomShouldRetryExceptionPredicateOverridesDefault()
    {
        $handler = new ScriptedHandler([
            new \RuntimeException('custom-retryable'),
            new Response(['code' => 200]),
        ]);

        $middleware = (new RetryMiddleware(3))
            ->setSleeper($this->noSleep())
            ->setShouldRetryException(fn(\Throwable $e) => $e instanceof \RuntimeException);
        $request = new Request('http://localhost/', 'GET');

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals(2, $handler->callCount);
    }

}
