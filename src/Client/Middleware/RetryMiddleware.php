<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client\Middleware;

use Pop\Http\Client\Handler\Exception as HandlerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Retry/backoff middleware - retries a request on transient network exceptions
 * and/or specific response status codes, with exponential backoff and full
 * jitter between attempts. Honors a Retry-After response header over the
 * computed delay when present. Skips retrying entirely when the request has
 * a non-seekable body, since a partially-consumed stream can't be safely
 * resent.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class RetryMiddleware implements MiddlewareInterface
{

    /**
     * Maximum number of retries (attempts beyond the first try)
     * @var int
     */
    protected int $maxRetries = 3;

    /**
     * HTTP methods eligible for retry (uppercase)
     * @var array
     */
    protected array $retryableMethods = ['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS'];

    /**
     * Response status codes eligible for retry
     * @var array
     */
    protected array $retryableStatusCodes = [429, 502, 503, 504];

    /**
     * Predicate deciding whether a caught exception is retryable
     * callable(\Throwable $exception): bool
     * @var callable
     */
    protected $shouldRetryException;

    /**
     * Base delay, in seconds, for the exponential backoff calculation
     * @var float
     */
    protected float $baseDelay = 0.1;

    /**
     * Maximum delay, in seconds, before jitter is applied
     * @var float
     */
    protected float $maxDelay = 10.0;

    /**
     * Optional observability callback, fired before each retry's sleep
     * callable(int $attempt, RequestInterface $request, ?ResponseInterface $response, ?\Throwable $exception, float $delaySeconds): void
     * @var ?callable
     */
    protected $onRetry = null;

    /**
     * Sleep function, callable(float $seconds): void - defaults to a usleep()
     * wrapper, overridable so tests don't have to actually sleep
     * @var callable
     */
    protected $sleeper;

    /**
     * Constructor
     *
     * @param int $maxRetries
     */
    public function __construct(int $maxRetries = 3)
    {
        $this->maxRetries           = $maxRetries;
        $this->shouldRetryException = function (\Throwable $exception): bool {
            return $exception instanceof HandlerException;
        };
        $this->sleeper = function (float $seconds): void {
            usleep((int)round($seconds * 1_000_000));
        };
    }

    /**
     * Set the maximum number of retries
     *
     * @param  int $maxRetries
     * @return RetryMiddleware
     */
    public function setMaxRetries(int $maxRetries): RetryMiddleware
    {
        $this->maxRetries = $maxRetries;
        return $this;
    }

    /**
     * Get the maximum number of retries
     *
     * @return int
     */
    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }

    /**
     * Set the HTTP methods eligible for retry
     *
     * @param  array $methods
     * @return RetryMiddleware
     */
    public function setRetryableMethods(array $methods): RetryMiddleware
    {
        $this->retryableMethods = array_map('strtoupper', $methods);
        return $this;
    }

    /**
     * Get the HTTP methods eligible for retry
     *
     * @return array
     */
    public function getRetryableMethods(): array
    {
        return $this->retryableMethods;
    }

    /**
     * Set the response status codes eligible for retry
     *
     * @param  array $statusCodes
     * @return RetryMiddleware
     */
    public function setRetryableStatusCodes(array $statusCodes): RetryMiddleware
    {
        $this->retryableStatusCodes = $statusCodes;
        return $this;
    }

    /**
     * Get the response status codes eligible for retry
     *
     * @return array
     */
    public function getRetryableStatusCodes(): array
    {
        return $this->retryableStatusCodes;
    }

    /**
     * Set the predicate deciding whether a caught exception is retryable
     *
     * @param  callable $predicate  callable(\Throwable $exception): bool
     * @return RetryMiddleware
     */
    public function setShouldRetryException(callable $predicate): RetryMiddleware
    {
        $this->shouldRetryException = $predicate;
        return $this;
    }

    /**
     * Set the base delay, in seconds, for the exponential backoff calculation
     *
     * @param  float $seconds
     * @return RetryMiddleware
     */
    public function setBaseDelay(float $seconds): RetryMiddleware
    {
        $this->baseDelay = $seconds;
        return $this;
    }

    /**
     * Get the base delay, in seconds
     *
     * @return float
     */
    public function getBaseDelay(): float
    {
        return $this->baseDelay;
    }

    /**
     * Set the maximum delay, in seconds, before jitter is applied
     *
     * @param  float $seconds
     * @return RetryMiddleware
     */
    public function setMaxDelay(float $seconds): RetryMiddleware
    {
        $this->maxDelay = $seconds;
        return $this;
    }

    /**
     * Get the maximum delay, in seconds
     *
     * @return float
     */
    public function getMaxDelay(): float
    {
        return $this->maxDelay;
    }

    /**
     * Set the observability callback, fired before each retry's sleep
     *
     * @param  callable $callback  callable(int $attempt, RequestInterface $request, ?ResponseInterface $response, ?\Throwable $exception, float $delaySeconds): void
     * @return RetryMiddleware
     */
    public function setOnRetry(callable $callback): RetryMiddleware
    {
        $this->onRetry = $callback;
        return $this;
    }

    /**
     * Set the sleep function - test-only override point, defaults to a usleep() wrapper
     *
     * @param  callable $sleeper  callable(float $seconds): void
     * @return RetryMiddleware
     */
    public function setSleeper(callable $sleeper): RetryMiddleware
    {
        $this->sleeper = $sleeper;
        return $this;
    }

    /**
     * Process the request, retrying on transient failures per the configured policy
     *
     * @param  RequestInterface        $request
     * @param  RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $handler->handle($request);
            } catch (\Throwable $exception) {
                if (($attempt >= $this->maxRetries) || (!$this->isMethodRetryable($request)) ||
                    (!($this->shouldRetryException)($exception)) || (!$this->canRetryBody($request))) {
                    throw $exception;
                }

                $this->wait($request, $attempt, null, $exception);
                $attempt++;
                continue;
            }

            if (($attempt >= $this->maxRetries) || (!$this->isMethodRetryable($request)) ||
                (!in_array($response->getStatusCode(), $this->retryableStatusCodes, true)) ||
                (!$this->canRetryBody($request))) {
                return $response;
            }

            $this->wait($request, $attempt, $response, null);
            $attempt++;
        }
    }

    /**
     * Compute the delay, fire the onRetry callback, sleep, and rewind the request body
     *
     * @param  RequestInterface   $request
     * @param  int                $attempt
     * @param  ?ResponseInterface $response
     * @param  ?\Throwable        $exception
     * @return void
     */
    protected function wait(RequestInterface $request, int $attempt, ?ResponseInterface $response, ?\Throwable $exception): void
    {
        $delay = $this->computeDelay($response, $attempt);

        if ($this->onRetry !== null) {
            ($this->onRetry)($attempt + 1, $request, $response, $exception, $delay);
        }

        ($this->sleeper)($delay);
        $this->rewindBody($request);
    }

    /**
     * Determine if the request's method is eligible for retry
     *
     * @param  RequestInterface $request
     * @return bool
     */
    protected function isMethodRetryable(RequestInterface $request): bool
    {
        return in_array(strtoupper($request->getMethod()), $this->retryableMethods, true);
    }

    /**
     * Determine if the request's body can be safely resent - true when
     * there's no underlying stream to worry about, or the body is seekable.
     *
     * Deliberately does not use getSize() === 0 as the "no body" signal:
     * a real, statable stream (e.g. a pipe, socket, or php://stdin) can
     * legitimately report a size of 0 while still being non-rewindable, and
     * that must fall through to the isSeekable() check rather than being
     * waved through. A Body with no stream attached at all has no metadata
     * (getMetadata() returns []), which is what actually means "no body".
     *
     * @param  RequestInterface $request
     * @return bool
     */
    protected function canRetryBody(RequestInterface $request): bool
    {
        $body = $request->getBody();

        return (($body->getMetadata() === []) || $body->isSeekable());
    }

    /**
     * Rewind the request's body, if it has an underlying stream. Uses the
     * same "no stream attached" signal as canRetryBody() (see its docblock)
     * so the two never drift out of sync.
     *
     * @param  RequestInterface $request
     * @return void
     */
    protected function rewindBody(RequestInterface $request): void
    {
        $body = $request->getBody();
        if ($body->getMetadata() !== []) {
            $body->rewind();
        }
    }

    /**
     * Compute the delay before the next attempt - honors a Retry-After response
     * header over the computed exponential-with-jitter delay when present, clamped
     * to the configured maximum delay
     *
     * @param  ?ResponseInterface $response
     * @param  int                $attempt
     * @return float
     */
    protected function computeDelay(?ResponseInterface $response, int $attempt): float
    {
        if ($response !== null) {
            $header = $response->getHeaderLine('Retry-After');
            if ($header !== '') {
                $retryAfter = $this->parseRetryAfter($header);
                if ($retryAfter !== null) {
                    return min($this->maxDelay, $retryAfter);
                }
            }
        }

        $exponential = $this->baseDelay * (2 ** $attempt);
        $capped      = min($this->maxDelay, $exponential);

        return $capped * (mt_rand() / mt_getrandmax());
    }

    /**
     * Parse a Retry-After header value - either an integer number of seconds,
     * or an HTTP-date to wait until (RFC 9110)
     *
     * @param  string $value
     * @return ?float
     */
    protected function parseRetryAfter(string $value): ?float
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            return (float)$trimmed;
        }

        $timestamp = strtotime($trimmed);
        if ($timestamp === false) {
            return null;
        }

        $delay = $timestamp - time();

        return ($delay > 0) ? (float)$delay : 0.0;
    }

}
