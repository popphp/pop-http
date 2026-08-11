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
namespace Pop\Http\Client\Handler;

use Pop\Http\AbstractRequest;
use Pop\Http\Auth;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;

/**
 * HTTP client mock handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Mock extends AbstractHandler
{

    /**
     * Queued responses/throwables, FIFO
     * @var array
     */
    protected array $queue = [];

    /**
     * Registered request matchers, checked in registration order before
     * falling back to the queue
     * @var array
     */
    protected array $matchers = [];

    /**
     * Every request actually dispatched through this handler, in order
     * @var array
     */
    protected array $history = [];

    /**
     * Queue a response or throwable to be returned/thrown by the next send()
     * that doesn't match a registered matcher
     *
     * @param  Response|\Throwable $result
     * @return Mock
     */
    public function queue(Response|\Throwable $result): Mock
    {
        $this->queue[] = $result;
        return $this;
    }

    /**
     * Register a response or throwable to return/throw for any request the
     * given matcher accepts. Checked before the FIFO queue, in registration
     * order - first match wins.
     *
     * @param  callable            $matcher   callable(Request $request): bool
     * @param  Response|\Throwable $result
     * @return Mock
     */
    public function when(callable $matcher, Response|\Throwable $result): Mock
    {
        $this->matchers[] = ['matcher' => $matcher, 'result' => $result];
        return $this;
    }

    /**
     * Set verify peer - no-op stub for interface parity with Curl/Stream
     *
     * SSL settings are meaningless for a transport that sends no bytes. This exists
     * so Client::prepare() can duck-type call it on any non-CurlMulti handler
     * (including Mock) without a fatal 'call to undefined method' error.
     *
     * @param  bool $verify
     * @return Mock
     */
    public function setVerifyPeer(bool $verify = true): Mock
    {
        return $this;
    }

    /**
     * Allow self-signed certs - no-op stub for interface parity with Curl/Stream
     *
     * SSL settings are meaningless for a transport that sends no bytes. This exists
     * so Client::prepare() can duck-type call it on any non-CurlMulti handler
     * (including Mock) without a fatal 'call to undefined method' error.
     *
     * @param  bool $allow
     * @return Mock
     */
    public function allowSelfSigned(bool $allow = true): Mock
    {
        return $this;
    }

    /**
     * Get every request actually dispatched through this handler, in order
     *
     * @return array
     */
    public function getRequests(): array
    {
        return $this->history;
    }

    /**
     * Get the most recently dispatched request, if any
     *
     * @return ?Request
     */
    public function getLastRequest(): ?Request
    {
        return (!empty($this->history)) ? $this->history[array_key_last($this->history)] : null;
    }

    /**
     * Method to prepare the handler
     *
     * Deliberately does not call collectRequestHeaders()/resolveRequestBody() -
     * those build wire-format output for a real transport, and Mock never sends
     * actual bytes. It does add the auth header and prepare request data, same as
     * Curl/Stream, so a recorded request in history/matching is semantically complete.
     *
     * @param  Request|AbstractRequest $request
     * @param  ?Auth                   $auth
     * @return Mock
     */
    public function prepare(Request|AbstractRequest $request, ?Auth $auth = null): Mock
    {
        if ($request instanceof Request) {
            $this->request = $request;
        }

        if ($auth !== null) {
            $request->addHeader($auth->createAuthHeader());
        }

        if (($request->hasData()) && (!$request->getData()->isPrepared())) {
            $request->prepareData();
        }

        $this->uri = $request->getUriAsString();

        return $this;
    }

    /**
     * Method to send the request
     *
     * @throws Exception
     * @return Response
     */
    public function send(): Response
    {
        if ($this->request !== null) {
            $this->history[] = clone $this->request;
        }

        $result = $this->resolveResult();

        if ($result instanceof \Throwable) {
            throw $result;
        }

        return $result;
    }

    /**
     * Resolve the matched/queued result for the current request - registered
     * matchers first (registration order, first match wins), then the FIFO
     * queue, then throw if nothing resolves
     *
     * @throws Exception
     * @return Response|\Throwable
     */
    protected function resolveResult(): Response|\Throwable
    {
        foreach ($this->matchers as $entry) {
            if (($entry['matcher'])($this->request)) {
                return $entry['result'];
            }
        }

        if (!empty($this->queue)) {
            return array_shift($this->queue);
        }

        throw new Exception(
            'Error: No matching handler or queued response for ' . $this->request?->getMethod() . ' ' .
            $this->request?->getUriAsString() . '.',
            0, null, 0, $this->request
        );
    }

    /**
     * Method to reset the handler
     *
     * Deliberately does not clear the queue or history - those are the test's
     * own configuration, not per-request wire-building state. Client::prepare()
     * calls reset() on every reused handler between sequential send() calls on
     * the same Client; clearing test-configured state here would silently break
     * "queue N responses for N sequential calls", the most common use of this
     * handler.
     *
     * @return Mock
     */
    public function reset(): Mock
    {
        return $this;
    }

    /**
     * Close the handler connection
     *
     * Full teardown, unlike reset() - clears the queue and history.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->queue    = [];
        $this->matchers = [];
        $this->history  = [];
        $this->request  = null;
    }

}
