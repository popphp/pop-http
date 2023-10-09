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
use Pop\Utils\CallableObject;
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
class Promise extends AbstractPromise
{

    /**
     * Constructor
     *
     * Instantiate the Promise object
     *
     * @param  AbstractClient $client
     */
    public function __construct(AbstractClient $client)
    {
        $this->setClient($client);
    }

    /**
     * Wait method
     *
     * @param  bool $unwrap
     * @throws Exception
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
                    throw new Exception('Error: ' . $this->client->getResponseCode() . ' ' . $this->client->getResponseMessage());
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
     * @param  callable $onSuccess
     * @param  callable $onFailure
     * @return void
     * @throws Exception|ReflectionException|\Pop\Utils\Exception
     */
    public function then(callable $onSuccess, callable $onFailure): void
    {
        $this->setState(self::PENDING);
        $this->client->send();

        $successCallback = new CallableObject($onSuccess);
        $failureCallback = new CallableObject($onFailure);

        if ($this->client->isComplete()) {
            if ($this->client->isSuccess()) {
                $this->setState(self::FULFILLED);
                $successCallback->addNamedParameter('response', $this->client->getParsedResponse());
                $successCallback->call([
                    'response' => $this->client->getResponse()
                ]);
            } else if ($this->client->isError()) {
                $this->setState(self::REJECTED);
                $failureCallback->call([
                    'response' => $this->client->getResponse()
                ]);
            }
        }
    }

    /**
     * Resolve method
     *
     * @return void
     */
    public function resolve(): void
    {

    }

    /**
     * Cancel method
     *
     * @return void
     */
    public function cancel(): void
    {

    }

}