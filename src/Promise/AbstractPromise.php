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

use Pop\Http\Client;
use Pop\Http\Client\Response;
use Pop\Utils\CallableObject;

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
     * @var ?Client
     */
    protected ?Client $client = null;

    /**
     * Success callable
     * @var ?CallableObject
     */
    protected ?CallableObject $onSuccess = null;

    /**
     * Failure callable
     * @var ?CallableObject
     */
    protected ?CallableObject $onFailure = null;

    /**
     * Cancel callable
     * @var ?CallableObject
     */
    protected ?CallableObject $onCancel = null;

    /**
     * Current state
     * @var string
     */
    protected string $state = self::PENDING;

    /**
     * Method to set client
     *
     * @param  Client $client
     * @return PromiseInterface
     */
    public function setClient(Client $client): AbstractPromise
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Method to get client
     *
     * @return Client
     */
    public function getClient(): Client
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
     * Method to set success callable
     *
     * @param  mixed $onSuccess
     * @throws Exception
     * @return AbstractPromise
     */
    public function setOnSuccess(mixed $onSuccess): AbstractPromise
    {
        if (!($onSuccess instanceof CallableObject) && !is_callable($onSuccess)) {
            throw new Exception('Error: The success callback must be an instance of CallableObject or a callable');
        }
        if (!($onSuccess instanceof CallableObject) && is_callable($onSuccess)) {
            $onSuccess = new CallableObject($onSuccess);
        }

        $this->onSuccess = $onSuccess;
        return $this;
    }

    /**
     * Method to get success callable
     *
     * @return CallableObject|null
     */
    public function getOnSuccess(): CallableObject|null
    {
        return $this->onSuccess;
    }

    /**
     * Method to check success callable
     *
     * @return bool
     */
    public function hasOnSuccess(): bool
    {
        return ($this->onSuccess !== null);
    }

    /**
     * Method to set failure callable
     *
     * @param  mixed $onFailure
     * @return AbstractPromise
     */
    public function setOnFailure(mixed $onFailure): AbstractPromise
    {
        if (!($onFailure instanceof CallableObject) && !is_callable($onFailure)) {
            throw new Exception('Error: The failure callback must be an instance of CallableObject or a callable');
        }
        if (!($onFailure instanceof CallableObject) && is_callable($onFailure)) {
            $onFailure = new CallableObject($onFailure);
        }

        $this->onFailure = $onFailure;
        return $this;
    }

    /**
     * Method to get failure callable
     *
     * @return CallableObject|null
     */
    public function getOnFailure(): CallableObject|null
    {
        return $this->onFailure;
    }

    /**
     * Method to check failure callable
     *
     * @return bool
     */
    public function hasOnFailure(): bool
    {
        return ($this->onFailure !== null);
    }

    /**
     * Method to set cancel callable
     *
     * @param  mixed $onCancel
     * @return AbstractPromise
     */
    public function setOnCancel(mixed $onCancel): AbstractPromise
    {
        if (!($onCancel instanceof CallableObject) && !is_callable($onCancel)) {
            throw new Exception('Error: The cancel callback must be an instance of CallableObject or a callable');
        }
        if (!($onCancel instanceof CallableObject) && is_callable($onCancel)) {
            $onCancel = new CallableObject($onCancel);
        }

        $this->onCancel = $onCancel;
        return $this;
    }

    /**
     * Method to get cancel callable
     *
     * @return CallableObject|null
     */
    public function getOnCancel(): CallableObject|null
    {
        return $this->onCancel;
    }

    /**
     * Method to check cancel callable
     *
     * @return bool
     */
    public function hasOnCancel(): bool
    {
        return ($this->onCancel !== null);
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
     * @param  mixed $onSuccess
     * @param  mixed $onFailure
     * @param  mixed $onCancel
     * @param  bool  $resolve
     * @return AbstractPromise
     */
    abstract public function then(mixed $onSuccess, mixed $onFailure, mixed $onCancel = null, bool $resolve = true): AbstractPromise;

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