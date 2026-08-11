<?php
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

use Pop\Http\Body;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * PSR-3 logging middleware - logs one line per handler invocation (per
 * dispatch attempt), with a level derived from the outcome (info for
 * success, warning for 4xx, error for 5xx or a thrown exception), known-
 * sensitive headers redacted by default, and request/response bodies
 * excluded unless explicitly opted in. Exceptions are logged then
 * re-thrown completely unmodified. No special coupling with
 * RetryMiddleware - see logRetriesTo() for an optional adapter onto its
 * existing onRetry hook.
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class LoggingMiddleware implements MiddlewareInterface
{

    /**
     * The PSR-3 logger to write to
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Header names redacted from the logged context (case-insensitive match)
     * @var array
     */
    protected array $redactedHeaders = ['Authorization', 'Cookie', 'Set-Cookie', 'X-Api-Key', 'Proxy-Authorization'];

    /**
     * Whether to include (truncated) request/response body content in the log context
     * @var bool
     */
    protected bool $includeBody = false;

    /**
     * Maximum body length included in the log context when includeBody is true
     * @var int
     */
    protected int $maxBodyLength = 1000;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the redacted header list, replacing the defaults
     *
     * @param  array $headers
     * @return LoggingMiddleware
     */
    public function setRedactedHeaders(array $headers): LoggingMiddleware
    {
        $this->redactedHeaders = $headers;
        return $this;
    }

    /**
     * Get the redacted header list
     *
     * @return array
     */
    public function getRedactedHeaders(): array
    {
        return $this->redactedHeaders;
    }

    /**
     * Add a header name to the redacted list
     *
     * @param  string $header
     * @return LoggingMiddleware
     */
    public function addRedactedHeader(string $header): LoggingMiddleware
    {
        $this->redactedHeaders[] = $header;
        return $this;
    }

    /**
     * Set whether to include (truncated) request/response body content in the log context
     *
     * @param  bool $include
     * @return LoggingMiddleware
     */
    public function setIncludeBody(bool $include): LoggingMiddleware
    {
        $this->includeBody = $include;
        return $this;
    }

    /**
     * Get whether body content is included in the log context
     *
     * @return bool
     */
    public function getIncludeBody(): bool
    {
        return $this->includeBody;
    }

    /**
     * Set the maximum body length included in the log context when includeBody is true
     *
     * @param  int $length
     * @return LoggingMiddleware
     */
    public function setMaxBodyLength(int $length): LoggingMiddleware
    {
        $this->maxBodyLength = $length;
        return $this;
    }

    /**
     * Get the maximum body length included in the log context
     *
     * @return int
     */
    public function getMaxBodyLength(): int
    {
        return $this->maxBodyLength;
    }

    /**
     * Process the request, logging one line for this invocation once the outcome is known
     *
     * @param  RequestInterface        $request
     * @param  RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $start;
            $this->logger->error(
                'HTTP {method} {uri} -> exception: {exception_class}: {exception_message} ({duration}s)',
                $this->buildContext($request, null, $exception, $duration)
            );
            throw $exception;
        }

        $duration = microtime(true) - $start;
        $this->logger->log(
            $this->levelForStatus($response->getStatusCode()),
            'HTTP {method} {uri} -> {status} ({duration}s)',
            $this->buildContext($request, $response, null, $duration)
        );

        return $response;
    }

    /**
     * Adapt a PSR-3 logger into a callable matching RetryMiddleware::setOnRetry()'s
     * signature, producing a structured "retrying" warning log per attempt with the
     * failure reason (exception class+message, or response status) and computed delay
     *
     * @param  LoggerInterface $logger
     * @return callable
     */
    public static function logRetriesTo(LoggerInterface $logger): callable
    {
        return function (int $attempt, RequestInterface $request, ?ResponseInterface $response, ?\Throwable $exception, float $delaySeconds) use ($logger): void {
            $reason = ($exception !== null)
                ? $exception::class . ': ' . $exception->getMessage()
                : (string)$response?->getStatusCode();

            $logger->warning('Retrying {method} {uri} - attempt {attempt}, delay {delay}s, reason: {reason}', [
                'method'  => $request->getMethod(),
                'uri'     => (string)$request->getUri(),
                'attempt' => $attempt,
                'delay'   => $delaySeconds,
                'reason'  => $reason,
            ]);
        };
    }

    /**
     * Determine the PSR-3 log level for a response status code
     *
     * @param  int $code
     * @return string
     */
    protected function levelForStatus(int $code): string
    {
        if ($code >= 500) {
            return LogLevel::ERROR;
        }
        if ($code >= 400) {
            return LogLevel::WARNING;
        }

        return LogLevel::INFO;
    }

    /**
     * Build the structured log context for a request/response/exception outcome
     *
     * @param  RequestInterface   $request
     * @param  ?ResponseInterface $response
     * @param  ?\Throwable        $exception
     * @param  float              $duration
     * @return array
     */
    protected function buildContext(RequestInterface $request, ?ResponseInterface $response, ?\Throwable $exception, float $duration): array
    {
        $context = [
            'method'   => $request->getMethod(),
            'uri'      => (string)$request->getUri(),
            'duration' => round($duration, 4),
            'headers'  => $this->redact($request->getHeaders()),
        ];

        if ($exception !== null) {
            $context['exception_class']   = $exception::class;
            $context['exception_message'] = $exception->getMessage();
        } else {
            $context['status'] = $response->getStatusCode();
        }

        if ($this->includeBody) {
            $context['request_body'] = $this->bodyForLog($request->getBody());
            if ($response !== null) {
                $context['response_body'] = $this->bodyForLog($response->getBody());
            }
        }

        return $context;
    }

    /**
     * Build the (truncated) logged representation of a request/response body without
     * materializing the whole underlying stream into memory - critical for large,
     * file-backed bodies (e.g. multi-megabyte uploads), where only ~maxBodyLength bytes
     * ever need to exist as a PHP string.
     *
     * For a Pop\Http\Body, reads at most maxBodyLength + 1 raw bytes directly off the
     * underlying stream resource (bypassing render()'s encoding/chunk-split transforms,
     * which are irrelevant - and potentially misleading - for a debug log), saving and
     * restoring the stream's read position exactly as Body::getContent() does, so other
     * consumers of the same Body object are unaffected. Non-seekable streams are skipped
     * gracefully with a placeholder rather than letting a rewind()/fseek() PHP warning leak
     * out of a logging call. Any other StreamInterface implementation falls back to the
     * previous whole-body-then-truncate behavior, since there is no generic PSR-7 API for a
     * position-preserving bounded read.
     *
     * @param  StreamInterface $body
     * @return string
     */
    protected function bodyForLog(StreamInterface $body): string
    {
        if (!($body instanceof Body)) {
            return $this->truncate((string)$body);
        }

        $stream = $body->getStream();
        if ($stream === null) {
            return '';
        }

        if (!$body->isSeekable()) {
            return '[non-seekable stream, body omitted]';
        }

        $length   = max(0, $this->maxBodyLength);
        $position = ftell($stream);
        rewind($stream);
        $content = fread($stream, $length + 1);
        fseek($stream, $position);

        return $this->truncate(($content === false) ? '' : $content);
    }

    /**
     * Redact configured header names from a PSR-7 getHeaders()-shaped array
     *
     * @param  array $headers
     * @return array
     */
    protected function redact(array $headers): array
    {
        $redactedNames = array_map('strtolower', $this->redactedHeaders);

        foreach ($headers as $name => $values) {
            if (in_array(strtolower($name), $redactedNames, true)) {
                $headers[$name] = ['[REDACTED]'];
            }
        }

        return $headers;
    }

    /**
     * Truncate body content to the configured maximum length
     *
     * A negative maxBodyLength is clamped to 0 - mb_substr()/substr() treat a negative
     * length as "omit N characters from the end," not "take N characters," so without
     * clamping, a negative value would silently produce unbounded (merely trailing-trimmed)
     * output that still looks truncated because of the appended '...'.
     *
     * Uses mb_strlen()/mb_substr() when available for multi-byte-safe truncation, falling
     * back to byte-based strlen()/substr() otherwise - ext-mbstring is only suggested, not
     * required, by this package, so an unconditional mb_* call would fatal on a build
     * without it. The byte-based fallback can split a multi-byte character mid-sequence;
     * that's an accepted, honest degradation rather than a fatal error.
     *
     * @param  string $content
     * @return string
     */
    protected function truncate(string $content): string
    {
        $length = max(0, $this->maxBodyLength);

        if (function_exists('mb_strlen')) {
            return (mb_strlen($content) > $length)
                ? mb_substr($content, 0, $length) . '...'
                : $content;
        }

        return (strlen($content) > $length)
            ? substr($content, 0, $length) . '...'
            : $content;
    }

}
