<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Promise;

use Pop\Http\Client\AbstractClient;
use Pop\Http\Client\Response;

/**
 * Abstract HTTP promise class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractPromise implements PromiseInterface
{

    /**
     * Status constants
     * @var string
     */
    const PENDING   = 'PENDING';
    const FULFILLED = 'FULFILLED';
    const REJECTED  = 'REJECTED';

    /**
     * Async client
     * @var ?AbstractClient
     */
    protected ?AbstractClient $client = null;

    /**
     * Current state
     * @var ?string
     */
    protected ?string $state = null;

    /**
     * Method to set client
     *
     * @param  AbstractClient $client
     * @return PromiseInterface
     */
    public function setClient(AbstractClient $client): AbstractPromise
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Method to get client
     *
     * @return AbstractClient
     */
    public function getClient(): AbstractClient
    {
        return $this->client;
    }

    /**
     * Method to check client
     *
     * @return bool
     */
    public function hasClient(): bool
    {
        return ($this->client !== null);
    }

    /**
     * Method to set current state
     *
     * @param  string $state
     * @throws Exception
     * @return AbstractPromise
     */
    public function setState(string $state): AbstractPromise
    {
        if (($state !== static::PENDING) && ($state !== static::FULFILLED) && ($state !== static::REJECTED)) {
            throw new Exception('Error: That state is not allowed.');
        }
        $this->state = $state;
        return $this;
    }

    /**
     * Method to get current state
     *
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * Method to check current state
     *
     * @return bool
     */
    public function hasState(): bool
    {
        return ($this->state !== null);
    }

    /**
     * Determine is the promise is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return ($this->state == static::PENDING);
    }

    /**
     * Determine is the promise is fulfilled
     *
     * @return bool
     */
    public function isFulfilled(): bool
    {
        return ($this->state == static::FULFILLED);
    }

    /**
     * Determine is the promise is rejected
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return ($this->state == static::REJECTED);
    }

    /**
     * Wait method
     *
     * @param  bool $unwrap
     * @return Response|null
     */
    abstract public function wait(bool $unwrap = true): Response|null;

    /**
     * Then method
     *
     * @param  callable $onSuccess
     * @param  callable $onFailure
     * @return void
     */
    abstract public function then(callable $onSuccess, callable $onFailure): void;

    /**
     * Resolve method
     *
     * @return void
     */
    abstract public function resolve(): void;

    /**
     * Cancel method
     *
     * @return void
     */
    abstract public function cancel(): void;

}