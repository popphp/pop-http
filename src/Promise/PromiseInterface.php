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
 * HTTP promise interface
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
interface PromiseInterface
{

    /**
     * Method to set client
     *
     * @param  Client $client
     * @return PromiseInterface
     */
    public function setClient(Client $client): PromiseInterface;

    /**
     * Method to get client
     *
     * @return Client
     */
    public function getClient(): Client;

    /**
     * Method to check client
     *
     * @return bool
     */
    public function hasClient(): bool;

    /**
     * Method to set success callable
     *
     * @param  mixed $onSuccess
     * @return PromiseInterface
     */
    public function setOnSuccess(mixed $onSuccess): PromiseInterface;

    /**
     * Method to get success callable
     *
     * @return CallableObject|null
     */
    public function getOnSuccess(): CallableObject|null;

    /**
     * Method to check success callable
     *
     * @return bool
     */
    public function hasOnSuccess(): bool;

    /**
     * Method to set failure callable
     *
     * @param  mixed $onFailure
     * @return PromiseInterface
     */
    public function setOnFailure(mixed $onFailure): PromiseInterface;

    /**
     * Method to get failure callable
     *
     * @return CallableObject|null
     */
    public function getOnFailure(): CallableObject|null;

    /**
     * Method to check failure callable
     *
     * @return bool
     */
    public function hasOnFailure(): bool;

    /**
     * Method to set cancel callable
     *
     * @param  mixed $onCancel
     * @return PromiseInterface
     */
    public function setOnCancel(mixed $onCancel): PromiseInterface;

    /**
     * Method to get cancel callable
     *
     * @return CallableObject|null
     */
    public function getOnCancel(): CallableObject|null;

    /**
     * Method to check cancel callable
     *
     * @return bool
     */
    public function hasOnCancel(): bool;

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
     * Wait method
     *
     * @param  bool $unwrap
     * @return Response|null
     */
    public function wait(bool $unwrap = true): Response|null;

    /**
     * Then method
     *
     * @param  mixed $onSuccess
     * @param  mixed $onFailure
     * @param  mixed $onCancel
     * @param  bool  $resolve
     * @return PromiseInterface
     */
    public function then(mixed $onSuccess, mixed $onFailure, mixed $onCancel = null, bool $resolve = true): PromiseInterface;

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

}