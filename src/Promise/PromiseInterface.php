<?php
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Promise;

use Pop\Http\Client;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\CurlMulti;
use Pop\Utils\CallableObject;

/**
 * HTTP promise interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
interface PromiseInterface
{

    /**
     * Method to set client promiser
     *
     * @param  Client|CurlMulti $promiser
     * @return PromiseInterface
     */
    public function setPromiser(Client|CurlMulti $promiser): PromiseInterface;

    /**
     * Method to get client promiser
     *
     * @return Client|CurlMulti
     */
    public function getPromiser(): Client|CurlMulti;

    /**
     * Method to check client promiser
     *
     * @return bool
     */
    public function hasPromiser(): bool;

    /**
     * Method to set success callable
     *
     * @param  mixed $success
     * @return PromiseInterface
     */
    public function setSuccess(mixed $success): PromiseInterface;

    /**
     * Method to get success callable
     *
     * @param  ?int $i
     * @return array|CallableObject|null
     */
    public function getSuccess(?int $i = null): array|CallableObject|null;

    /**
     * Method to check success callable
     *
     * @param  ?int $i
     * @return bool
     */
    public function hasSuccess(?int $i = null): bool;

    /**
     * Method to set failure callable
     *
     * @param  mixed $failure
     * @return PromiseInterface
     */
    public function setFailure(mixed $failure): PromiseInterface;

    /**
     * Method to get failure callable
     *
     * @return CallableObject|null
     */
    public function getFailure(): CallableObject|null;

    /**
     * Method to check failure callable
     *
     * @return bool
     */
    public function hasFailure(): bool;

    /**
     * Method to set cancel callable
     *
     * @param  mixed $cancel
     * @return PromiseInterface
     */
    public function setCancel(mixed $cancel): PromiseInterface;

    /**
     * Method to get cancel callable
     *
     * @return CallableObject|null
     */
    public function getCancel(): CallableObject|null;

    /**
     * Method to check cancel callable
     *
     * @return bool
     */
    public function hasCancel(): bool;

    /**
     * Method to set finally callable
     *
     * @param  mixed $finally
     * @return PromiseInterface
     */
    public function setFinally(mixed $finally): PromiseInterface;

    /**
     * Method to get finally callable
     *
     * @return CallableObject|null
     */
    public function getFinally(): CallableObject|null;

    /**
     * Method to check finally callable
     *
     * @return bool
     */
    public function hasFinally(): bool;

    /**
     * Method to set current state
     *
     * @param  string $state
     * @return PromiseInterface
     */
    public function setState(string $state): PromiseInterface;

    /**
     * Method to get current state
     *
     * @return string
     */
    public function getState(): string;

    /**
     * Method to check current state
     *
     * @return bool
     */
    public function hasState(): bool;

    /**
     * Determine is the promise is pending
     *
     * @return bool
     */
    public function isPending(): bool;

    /**
     * Determine is the promise is fulfilled
     *
     * @return bool
     */
    public function isFulfilled(): bool;

    /**
     * Determine is the promise is rejected
     *
     * @return bool
     */
    public function isRejected(): bool;

    /**
     * Determine is the promise is cancelled
     *
     * @return bool
     */
    public function isCancelled(): bool;

    /**
     * Then method
     *
     * @param  mixed $success
     * @param  bool  $resolve
     * @return PromiseInterface
     */
    public function then(mixed $success, bool $resolve = false): PromiseInterface;

    /**
     * Method to set failure callable
     *
     * @param  mixed $failure
     * @param  bool $resolve
     * @return PromiseInterface
     */
    public function catch(mixed $failure, bool $resolve = false): PromiseInterface;

    /**
     * Method to set finally callable
     *
     * @param  mixed $finally
     * @param  bool $resolve
     * @return PromiseInterface
     */
    public function finally(mixed $finally, bool $resolve = false): PromiseInterface;

    /**
     * Wait method
     *
     * @param  bool $unwrap
     * @return Response|array|null
     */
    public function wait(bool $unwrap = true): Response|array|null;

    /**
     * Resolve method
     *
     * @return void
     */
    public function resolve(): void;

    /**
     * Cancel method
     *
     * @return void
     */
    public function cancel(): void;

    /**
     * Forward method
     *
     * @param  PromiseInterface $nextPromise
     * @param  int              $i
     * @return PromiseInterface
     */
    public function forward(PromiseInterface $nextPromise, int $i = 0): PromiseInterface;

}
