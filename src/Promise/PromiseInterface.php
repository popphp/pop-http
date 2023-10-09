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
     * @param  AbstractClient $client
     * @return PromiseInterface
     */
    public function setClient(AbstractClient $client): PromiseInterface;

    /**
     * Method to get client
     *
     * @return AbstractClient
     */
    public function getClient(): AbstractClient;

    /**
     * Method to check client
     *
     * @return bool
     */
    public function hasClient(): bool;

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
     * @param  callable $onSuccess
     * @param  callable $onFailure
     * @return void
     */
    public function then(callable $onSuccess, callable $onFailure): void;

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