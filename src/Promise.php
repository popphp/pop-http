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
namespace Pop\Http;

use Pop\Http\Client\Response;
use Pop\Http\Promise\Exception;
use ReflectionException;

/**
 * HTTP promise class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Promise extends Promise\AbstractPromise
{

    /**
     * Constructor
     *
     * Instantiate the Promise object
     *
     * @param  Client $client
     */
    public function __construct(Client $client)
    {
        $this->setClient($client);
    }

    /**
     * Wait method
     *
     * @param  bool $unwrap
     * @throws Exception|Promise\Exception|ReflectionException|\Pop\Http\Client\Exception|\Pop\Utils\Exception
     * @return Response|null
     */
    public function wait(bool $unwrap = true): Response|null
    {
        if (($this->isFulfilled()) && ($this->client->isComplete())) {
            return $this->client->getResponse();
        }

        $this->setState(self::PENDING);
        $this->client->send();

        if ($this->client->isComplete()) {
            if ($this->client->isError()) {
                $this->setState(self::REJECTED);
                if ($unwrap) {
                    throw new Exception('Error: ' . $this->client->getResponse()->getCode() . ' ' . $this->client->getResponse()->getMessage());
                }
            } else {
                $this->setState(self::FULFILLED);
                return $this->client->getResponse();
            }
        } else if ($unwrap) {
            throw new Exception('Error: Unable to complete request.');
        }

        return null;
    }

    /**
     * Then method
     *
     * @param  mixed $onSuccess
     * @param  mixed $onFailure
     * @param  mixed $onCancel
     * @param  bool  $resolve
     * @return Promise
     *@throws Exception|Promise\Exception|ReflectionException|\Pop\Http\Client\Exception|\Pop\Utils\Exception|\Pop\Http\Exception
     */
    public function then(mixed $onSuccess, mixed $onFailure, mixed $onCancel = null, bool $resolve = true): Promise
    {
        $this->setOnSuccess($onSuccess);
        $this->setOnFailure($onFailure);

        if ($onCancel !== null) {
            $this->setOnCancel($onCancel);
        }

        if ($resolve) {
            $this->resolve();
        }

        return $this;
    }

    /**
     * Resolve method
     *
     * @throws Client\Exception|Exception|ReflectionException|\Pop\Utils\Exception|\Pop\Http\Exception
     * @return void
     */
    public function resolve(): void
    {
        $this->client->send();

        if ($this->client->isComplete()) {
            if ($this->client->isSuccess()) {
                if (!$this->hasOnSuccess()) {
                    throw new Exception('Error: The success callback has not been set.');
                }
                $this->setState(self::FULFILLED);
                $this->onSuccess->call([
                    'response' => $this->client->getResponse()
                ]);
            } else if ($this->client->isError()) {
                if (!$this->hasOnFailure()) {
                    throw new Exception('Error: The success callback has not been set.');
                }
                $this->setState(self::REJECTED);
                $this->onFailure->call([
                    'response' => $this->client->getResponse()
                ]);
            }
        }
    }

    /**
     * Cancel method
     *
     * @throws Exception|ReflectionException|\Pop\Utils\Exception
     * @return void
     */
    public function cancel(): void
    {
        if ($this->getState() !== self::PENDING) {
            return;
        }
        if (!$this->hasOnCancel()) {
            throw new Exception('Error: The cancel callback has not been set.');
        }
        $this->onCancel->call(['promise' => $this]);
    }

}