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
    const CANCELLED = 'CANCELLED';

    /**
     * Async client
     * @var ?Client
     */
    protected ?Client $client = null;

    /**
     * Success callable
     * @var ?CallableObject
     */
    protected ?CallableObject $success = null;

    /**
     * Failure callable
     * @var ?CallableObject
     */
    protected ?CallableObject $failure = null;

    /**
     * Cancel callable
     * @var ?CallableObject
     */
    protected ?CallableObject $cancel = null;

    /**
     * Cancel callable
     * @var ?CallableObject
     */
    protected ?CallableObject $finally = null;

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
     * @param  mixed $success
     * @throws Exception
     * @return AbstractPromise
     */
    public function setSuccess(mixed $success): AbstractPromise
    {
        if (!($success instanceof CallableObject) && !is_callable($success)) {
            throw new Exception('Error: The success callback must be an instance of CallableObject or a callable');
        }
        if (!($success instanceof CallableObject) && is_callable($success)) {
            $success = new CallableObject($success);
        }

        $this->success = $success;
        return $this;
    }

    /**
     * Method to get success callable
     *
     * @return CallableObject|null
     */
    public function getSuccess(): CallableObject|null
    {
        return $this->success;
    }

    /**
     * Method to check success callable
     *
     * @return bool
     */
    public function hasSuccess(): bool
    {
        return ($this->success !== null);
    }

    /**
     * Method to set failure callable
     *
     * @param  mixed $failure
     * @return AbstractPromise
     */
    public function setFailure(mixed $failure): AbstractPromise
    {
        if (!($failure instanceof CallableObject) && !is_callable($failure)) {
            throw new Exception('Error: The failure callback must be an instance of CallableObject or a callable');
        }
        if (!($failure instanceof CallableObject) && is_callable($failure)) {
            $failure = new CallableObject($failure);
        }

        $this->failure = $failure;
        return $this;
    }

    /**
     * Method to get failure callable
     *
     * @return CallableObject|null
     */
    public function getFailure(): CallableObject|null
    {
        return $this->failure;
    }

    /**
     * Method to check failure callable
     *
     * @return bool
     */
    public function hasFailure(): bool
    {
        return ($this->failure !== null);
    }

    /**
     * Method to set cancel callable
     *
     * @param  mixed $cancel
     * @return AbstractPromise
     */
    public function setCancel(mixed $cancel): AbstractPromise
    {
        if (!($cancel instanceof CallableObject) && !is_callable($cancel)) {
            throw new Exception('Error: The cancel callback must be an instance of CallableObject or a callable');
        }
        if (!($cancel instanceof CallableObject) && is_callable($cancel)) {
            $cancel = new CallableObject($cancel);
        }

        $this->cancel = $cancel;
        return $this;
    }

    /**
     * Method to get cancel callable
     *
     * @return CallableObject|null
     */
    public function getCancel(): CallableObject|null
    {
        return $this->cancel;
    }

    /**
     * Method to check cancel callable
     *
     * @return bool
     */
    public function hasCancel(): bool
    {
        return ($this->cancel !== null);
    }

    /**
     * Method to set finally callable
     *
     * @param  mixed $finally
     * @return PromiseInterface
     */
    public function setFinally(mixed $finally): AbstractPromise
    {
        if (!($finally instanceof CallableObject) && !is_callable($finally)) {
            throw new Exception('Error: The cancel callback must be an instance of CallableObject or a callable');
        }
        if (!($finally instanceof CallableObject) && is_callable($finally)) {
            $finally = new CallableObject($finally);
        }

        $this->finally = $finally;
        return $this;
    }

    /**
     * Method to get finally callable
     *
     * @return CallableObject|null
     */
    public function getFinally(): CallableObject|null
    {
        return $this->finally;
    }

    /**
     * Method to check finally callable
     *
     * @return bool
     */
    public function hasFinally(): bool
    {
        return ($this->finally !== null);
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
        if (($state !== static::PENDING) && ($state !== static::FULFILLED) && ($state !== static::REJECTED) && ($state !== static::CANCELLED)) {
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
     * Determine is the promise is cancelled
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return ($this->state == static::CANCELLED);
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
     * @param  mixed $success
     * @param  bool  $resolve
     * @return AbstractPromise
     */
    public function then(mixed $success, bool $resolve = false): AbstractPromise
    {
        $this->setSuccess($success);

        if ($resolve) {
            $this->resolve();
        }

        return $this;
    }

    /**
     * Method to set failure callable (alias)
     *
     * @param  mixed $failure
     * @param  bool $resolve
     * @return AbstractPromise
     */
    public function catch(mixed $failure, bool $resolve = false): AbstractPromise
    {
        $this->setFailure($failure);

        if ($resolve) {
            $this->resolve();
        }

        return $this;
    }

    /**
     * Method to set finally callable (alias)
     *
     * @param  mixed $finally
     * @return AbstractPromise
     */
    public function finally(mixed $finally, bool $resolve = false): AbstractPromise
    {
        $this->setFinally($finally);

        if ($resolve) {
            $this->resolve();
        }

        return $this;
    }

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