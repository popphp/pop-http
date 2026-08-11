<?php

namespace Pop\Http\Test\Client\Middleware;

use Pop\Http\Client\Middleware\RequestHandlerInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A RequestHandlerInterface that returns/throws a scripted sequence of
 * results in order (Response or Throwable), repeating the last entry once
 * the script is exhausted. Records every request it receives and how many
 * times it was called, for assertions in RetryMiddlewareTest.
 */
class ScriptedHandler implements RequestHandlerInterface
{

    public int $callCount = 0;

    public array $receivedRequests = [];

    protected array $results;

    /**
     * @param array $results  array of Response|\Throwable, consumed in order
     */
    public function __construct(array $results)
    {
        $this->results = $results;
    }

    public function handle(RequestInterface $request): ResponseInterface
    {
        $this->receivedRequests[] = $request;
        $index = $this->callCount;
        $this->callCount++;

        $result = $this->results[$index] ?? end($this->results);

        if ($result instanceof \Throwable) {
            throw $result;
        }

        return $result;
    }

}
