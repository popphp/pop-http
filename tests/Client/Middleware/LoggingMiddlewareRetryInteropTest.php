<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Client;
use Pop\Http\Client\Handler\Mock;
use Pop\Http\Client\Middleware\LoggingMiddleware;
use Pop\Http\Client\Middleware\RetryMiddleware;
use Pop\Http\Client\Response;
use PHPUnit\Framework\TestCase;

class LoggingMiddlewareRetryInteropTest extends TestCase
{

    public function testLoggingInnermostRelativeToRetryLogsEveryAttempt()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 503]));
        $mock->queue(new Response(['code' => 200]));

        $logger = new RecordingLogger();
        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware((new RetryMiddleware(3))->setSleeper(function (float $s): void {}));
        $client->addMiddleware(new LoggingMiddleware($logger));

        $response = $client->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(2, $logger->records);
        $this->assertEquals(503, $logger->records[0]['context']['status']);
        $this->assertEquals(200, $logger->records[1]['context']['status']);
    }

    public function testLoggingOutermostRelativeToRetryLogsOnlyNetOutcome()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 503]));
        $mock->queue(new Response(['code' => 200]));

        $logger = new RecordingLogger();
        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(new LoggingMiddleware($logger));
        $client->addMiddleware((new RetryMiddleware(3))->setSleeper(function (float $s): void {}));

        $response = $client->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(1, $logger->records);
        $this->assertEquals(200, $logger->records[0]['context']['status']);
    }

    public function testLogRetriesToWiredIntoOnRetryProducesRetryLogLine()
    {
        $mock = new Mock();
        $mock->queue(new Response(['code' => 503]));
        $mock->queue(new Response(['code' => 200]));

        $retryLogger   = new RecordingLogger();
        $outcomeLogger = new RecordingLogger();
        $client        = new Client('http://localhost/', $mock);
        $client->addMiddleware(
            (new RetryMiddleware(3))
                ->setSleeper(function (float $s): void {})
                ->setOnRetry(LoggingMiddleware::logRetriesTo($retryLogger))
        );
        $client->addMiddleware(new LoggingMiddleware($outcomeLogger));

        $client->send();

        $this->assertCount(1, $retryLogger->records);
        $this->assertEquals('warning', $retryLogger->records[0]['level']);
        $this->assertEquals(1, $retryLogger->records[0]['context']['attempt']);
        $this->assertCount(2, $outcomeLogger->records);
    }

}
