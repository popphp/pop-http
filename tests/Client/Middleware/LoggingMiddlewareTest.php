<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Body;
use Pop\Http\Client\Handler\Exception as HandlerException;
use Pop\Http\Client\Middleware\CallableRequestHandler;
use Pop\Http\Client\Middleware\LoggingMiddleware;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Psr\Log\LogLevel;
use PHPUnit\Framework\TestCase;

class LoggingMiddlewareTest extends TestCase
{

    public function testSuccessfulResponseLogsAtInfoLevel()
    {
        $logger     = new RecordingLogger();
        $middleware = new LoggingMiddleware($logger);
        $request    = new Request('http://localhost/', 'GET');
        $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $response = $middleware->process($request, $handler);

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(1, $logger->records);
        $this->assertEquals(LogLevel::INFO, $logger->records[0]['level']);
        $this->assertEquals(200, $logger->records[0]['context']['status']);
        $this->assertEquals('GET', $logger->records[0]['context']['method']);
        $this->assertEquals('http://localhost/', $logger->records[0]['context']['uri']);
        $this->assertIsFloat($logger->records[0]['context']['duration']);
    }

    public function testClientErrorResponseLogsAtWarningLevel()
    {
        $logger     = new RecordingLogger();
        $middleware = new LoggingMiddleware($logger);
        $request    = new Request('http://localhost/', 'GET');
        $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 404]));

        $middleware->process($request, $handler);

        $this->assertCount(1, $logger->records);
        $this->assertEquals(LogLevel::WARNING, $logger->records[0]['level']);
        $this->assertEquals(404, $logger->records[0]['context']['status']);
    }

    public function testServerErrorResponseLogsAtErrorLevel()
    {
        $logger     = new RecordingLogger();
        $middleware = new LoggingMiddleware($logger);
        $request    = new Request('http://localhost/', 'GET');
        $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 503]));

        $middleware->process($request, $handler);

        $this->assertCount(1, $logger->records);
        $this->assertEquals(LogLevel::ERROR, $logger->records[0]['level']);
        $this->assertEquals(503, $logger->records[0]['context']['status']);
    }

    public function testExceptionLogsAtErrorLevelAndIsRethrownUnmodified()
    {
        $logger     = new RecordingLogger();
        $middleware = new LoggingMiddleware($logger);
        $request    = new Request('http://localhost/', 'GET');
        $exception  = new HandlerException('Simulated network blip.');
        $handler    = new CallableRequestHandler(function ($req) use ($exception) {
            throw $exception;
        });

        $this->expectException(HandlerException::class);
        $this->expectExceptionMessage('Simulated network blip.');

        try {
            $middleware->process($request, $handler);
        } finally {
            $this->assertCount(1, $logger->records);
            $this->assertEquals(LogLevel::ERROR, $logger->records[0]['level']);
            $this->assertEquals(HandlerException::class, $logger->records[0]['context']['exception_class']);
            $this->assertEquals('Simulated network blip.', $logger->records[0]['context']['exception_message']);
            $this->assertArrayNotHasKey('status', $logger->records[0]['context']);
        }
    }

    public function testDefaultRedactedHeadersAreMasked()
    {
        $logger     = new RecordingLogger();
        $middleware = new LoggingMiddleware($logger);
        $request    = (new Request('http://localhost/', 'GET'))
            ->addHeader('Authorization', 'Bearer secret-token')
            ->addHeader('X-Custom', 'visible-value');
        $handler = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $middleware->process($request, $handler);

        $headers = $logger->records[0]['context']['headers'];
        $this->assertEquals(['[REDACTED]'], $headers['Authorization']);
        $this->assertEquals(['visible-value'], $headers['X-Custom']);
    }

    public function testRedactionIsCaseInsensitive()
    {
        $logger     = new RecordingLogger();
        $middleware = new LoggingMiddleware($logger);
        $request    = (new Request('http://localhost/', 'GET'))
            ->addHeader('authorization', 'Bearer secret-token');
        $handler = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $middleware->process($request, $handler);

        $headers = $logger->records[0]['context']['headers'];
        $this->assertEquals(['[REDACTED]'], $headers['authorization']);
    }

    public function testCustomRedactedHeadersReplaceDefaults()
    {
        $logger     = new RecordingLogger();
        $middleware = (new LoggingMiddleware($logger))->setRedactedHeaders(['X-Secret']);
        $request    = (new Request('http://localhost/', 'GET'))
            ->addHeader('Authorization', 'Bearer secret-token')
            ->addHeader('X-Secret', 'hidden');
        $handler = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $middleware->process($request, $handler);

        $headers = $logger->records[0]['context']['headers'];
        $this->assertEquals(['Bearer secret-token'], $headers['Authorization']);
        $this->assertEquals(['[REDACTED]'], $headers['X-Secret']);
    }

    public function testAddRedactedHeaderAppendsToDefaults()
    {
        $logger     = new RecordingLogger();
        $middleware = (new LoggingMiddleware($logger))->addRedactedHeader('X-Secret');
        $request    = (new Request('http://localhost/', 'GET'))
            ->addHeader('Authorization', 'Bearer secret-token')
            ->addHeader('X-Secret', 'hidden');
        $handler = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $middleware->process($request, $handler);

        $headers = $logger->records[0]['context']['headers'];
        $this->assertEquals(['[REDACTED]'], $headers['Authorization']);
        $this->assertEquals(['[REDACTED]'], $headers['X-Secret']);
    }

    public function testBodyExcludedByDefault()
    {
        $logger     = new RecordingLogger();
        $middleware = new LoggingMiddleware($logger);
        $request    = (new Request('http://localhost/', 'POST'))->setBody('secret-payload');
        $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 200, 'body' => 'response-payload']));

        $middleware->process($request, $handler);

        $this->assertArrayNotHasKey('request_body', $logger->records[0]['context']);
        $this->assertArrayNotHasKey('response_body', $logger->records[0]['context']);
    }

    public function testBodyIncludedWhenOptedIn()
    {
        $logger     = new RecordingLogger();
        $middleware = (new LoggingMiddleware($logger))->setIncludeBody(true);
        $request    = (new Request('http://localhost/', 'POST'))->setBody('request-payload');
        $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 200, 'body' => 'response-payload']));

        $middleware->process($request, $handler);

        $this->assertEquals('request-payload', $logger->records[0]['context']['request_body']);
        $this->assertEquals('response-payload', $logger->records[0]['context']['response_body']);
    }

    public function testBodyTruncatedToMaxLength()
    {
        $logger     = new RecordingLogger();
        $middleware = (new LoggingMiddleware($logger))->setIncludeBody(true)->setMaxBodyLength(10);
        $request    = (new Request('http://localhost/', 'POST'))->setBody('this is a much longer payload than ten characters');
        $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $middleware->process($request, $handler);

        $this->assertEquals('this is a ...', $logger->records[0]['context']['request_body']);
    }

    public function testBodyTruncationOnLargeFileBackedStreamDoesNotMaterializeWholeFile()
    {
        $tmpFile   = tempnam(sys_get_temp_dir(), 'pop-http-logging-test-');
        $chunkSize = 64 * 1024;
        $chunks    = 128;
        $fileSize  = $chunkSize * $chunks; // 8MB - well beyond the default 1000-char
                                            // maxBodyLength, but fast to write/read in a test,
                                            // and big enough that "materialize the whole thing"
                                            // vs. "read ~1KB" is an unmistakable memory-usage
                                            // signal rather than noise.
        //
        // Written in small chunks (rather than via a single str_repeat()) so building the
        // fixture itself never allocates an 8MB PHP string - otherwise that allocation alone
        // would push memory_get_peak_usage() up before the assertion even starts, masking
        // whether the code under test materializes the file.
        $handle = fopen($tmpFile, 'wb');
        $chunk  = str_repeat('A', $chunkSize);
        for ($i = 0; $i < $chunks; $i++) {
            fwrite($handle, $chunk);
        }
        fclose($handle);
        unset($chunk);

        try {
            $body = new Body();
            $body->setContentFromFile($tmpFile);

            $logger     = new RecordingLogger();
            $middleware = (new LoggingMiddleware($logger))->setIncludeBody(true);
            $request    = (new Request('http://localhost/', 'POST'))->setBody($body);
            $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

            // Use peak-usage deltas (not current usage) so a transient allocation that gets
            // freed by refcounting before this line runs still shows up - memory_get_usage()
            // alone would miss a spike-then-free and give a false pass.
            $peakBefore = memory_get_peak_usage(true);
            $middleware->process($request, $handler);
            $peakAfter  = memory_get_peak_usage(true);

            $expected = str_repeat('A', 1000) . '...';
            $this->assertEquals($expected, $logger->records[0]['context']['request_body']);

            // The underlying stream position must be restored, not left at EOF, so other
            // consumers of the same Body (e.g. the handler that already sent it) are unaffected.
            $this->assertEquals(0, ftell($body->getStream()));

            // A bounded read should cost on the order of maxBodyLength bytes, not the file
            // size. Assert the peak-memory delta stays a small fraction of the file size - a
            // generous margin (well under the ~8MB it would take to materialize the whole
            // file) that still clearly fails if the fix regresses to full materialization.
            $this->assertLessThan($fileSize / 4, max(0, $peakAfter - $peakBefore));
        } finally {
            unlink($tmpFile);
        }
    }

    public function testBodyLoggingOnNonSeekableStreamOmitsBodyWithoutWarning()
    {
        $stream = popen('printf hi', 'r');

        try {
            $body = new Body();
            $body->setContentFromStream($stream);

            $logger     = new RecordingLogger();
            $middleware = (new LoggingMiddleware($logger))->setIncludeBody(true);
            $request    = (new Request('http://localhost/', 'POST'))->setBody($body);
            $handler    = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

            $middleware->process($request, $handler);

            $this->assertEquals(
                '[non-seekable stream, body omitted]',
                $logger->records[0]['context']['request_body']
            );
        } finally {
            pclose($stream);
        }
    }

    public function testSetMaxBodyLengthWithNegativeValueBehavesLikeZero()
    {
        $logger        = new RecordingLogger();
        $zeroMiddleware = (new LoggingMiddleware($logger))->setIncludeBody(true)->setMaxBodyLength(0);
        $negativeLogger = new RecordingLogger();
        $negativeMiddleware = (new LoggingMiddleware($negativeLogger))->setIncludeBody(true)->setMaxBodyLength(-1);

        $request = fn() => (new Request('http://localhost/', 'POST'))->setBody('abcdef');
        $handler = new CallableRequestHandler(fn($req) => new Response(['code' => 200]));

        $zeroMiddleware->process($request(), $handler);
        $negativeMiddleware->process($request(), $handler);

        $this->assertEquals('...', $logger->records[0]['context']['request_body']);
        $this->assertEquals(
            $logger->records[0]['context']['request_body'],
            $negativeLogger->records[0]['context']['request_body']
        );
    }

    public function testGetIncludeBodyAndGetMaxBodyLengthReflectConfiguration()
    {
        $middleware = new LoggingMiddleware(new RecordingLogger());

        $this->assertFalse($middleware->getIncludeBody());
        $this->assertEquals(1000, $middleware->getMaxBodyLength());

        $middleware->setIncludeBody(true)->setMaxBodyLength(50);

        $this->assertTrue($middleware->getIncludeBody());
        $this->assertEquals(50, $middleware->getMaxBodyLength());
    }

    public function testGetRedactedHeadersReflectsConfiguration()
    {
        $middleware = new LoggingMiddleware(new RecordingLogger());

        $this->assertEquals(
            ['Authorization', 'Cookie', 'Set-Cookie', 'X-Api-Key', 'Proxy-Authorization'],
            $middleware->getRedactedHeaders()
        );

        $middleware->setRedactedHeaders(['X-Only']);

        $this->assertEquals(['X-Only'], $middleware->getRedactedHeaders());
    }

    public function testLogRetriesToProducesWarningLogWithExceptionReason()
    {
        $logger  = new RecordingLogger();
        $closure = LoggingMiddleware::logRetriesTo($logger);
        $request = new Request('http://localhost/', 'GET');
        $exception = new HandlerException('Connection refused.');

        $closure(1, $request, null, $exception, 0.21);

        $this->assertCount(1, $logger->records);
        $this->assertEquals(LogLevel::WARNING, $logger->records[0]['level']);
        $this->assertEquals(1, $logger->records[0]['context']['attempt']);
        $this->assertEquals(0.21, $logger->records[0]['context']['delay']);
        $this->assertStringContainsString('Connection refused.', $logger->records[0]['context']['reason']);
    }

    public function testLogRetriesToProducesWarningLogWithResponseReason()
    {
        $logger  = new RecordingLogger();
        $closure = LoggingMiddleware::logRetriesTo($logger);
        $request = new Request('http://localhost/', 'GET');
        $response = new Response(['code' => 503]);

        $closure(1, $request, $response, null, 0.21);

        $this->assertCount(1, $logger->records);
        $this->assertEquals(LogLevel::WARNING, $logger->records[0]['level']);
        $this->assertEquals('503', $logger->records[0]['context']['reason']);
    }

}
