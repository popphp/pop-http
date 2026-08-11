<?php

namespace Pop\Http\Test\Client\Middleware;

use Psr\Log\AbstractLogger;

/**
 * An in-memory Psr\Log\LoggerInterface implementation that records every
 * log() call instead of writing anywhere, for assertions in
 * LoggingMiddlewareTest (and any later test needing a PSR-3 logger double).
 */
class RecordingLogger extends AbstractLogger
{

    /**
     * @var array  each entry: ['level' => string, 'message' => string, 'context' => array]
     */
    public array $records = [];

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = [
            'level'   => (string)$level,
            'message' => (string)$message,
            'context' => $context,
        ];
    }

}
