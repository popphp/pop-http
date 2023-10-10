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
     * @param  Client $promiser
     */
    public function __construct(Client $promiser)
    {
        $this->setPromiser($promiser);
    }

    /**
     * Wait method
     *
     * @param  bool $unwrap
     * @throws Exception|Promise\Exception|ReflectionException|Client\Exception|\Pop\Utils\Exception|\Pop\Http\Exception
     * @return Response|null
     */
    public function wait(bool $unwrap = true): Response|null
    {
        if (($this->isFulfilled()) && ($this->promiser->isComplete())) {
            return $this->promiser->getResponse();
        }

        $this->setState(self::PENDING);
        $this->promiser->send();

        if ($this->promiser->isComplete()) {
            if ($this->promiser->isError()) {
                $this->setState(self::REJECTED);
                if ($unwrap) {
                    throw new Exception('Error: ' . $this->promiser->getResponse()->getCode() . ' ' . $this->promiser->getResponse()->getMessage());
                }
            } else {
                $this->setState(self::FULFILLED);
                return $this->promiser->getResponse();
            }
        } else if ($unwrap) {
            throw new Exception('Error: Unable to complete request.');
        }

        return null;
    }

    /**
     * Resolve method
     *
     * @throws Client\Exception|Exception|ReflectionException|\Pop\Utils\Exception|\Pop\Http\Exception
     * @return void
     */
    public function resolve(): void
    {
        if ($this->getState() !== self::PENDING) {
            return;
        }
        $this->promiser->send();

        if ($this->promiser->isComplete()) {
            if ($this->promiser->isSuccess()) {
                if (!$this->hasSuccess()) {
                    throw new Exception('Error: The success callback has not been set.');
                }

                $result = null;
                foreach ($this->success as $i => $success) {
                    // Forward success callbacks to next promise
                    if ($result instanceof Promise) {
                        $result = $this->forward($result, $i);
                        break;
                    // Else, execute callback
                    } else {
                        $result = $success->call(['response' => $this->promiser->getResponse()]);
                    }
                }

                $this->setState(self::FULFILLED);

                if ($result instanceof Promise) {
                    $result->resolve();
                }
            } else if ($this->promiser->isError()) {
                if (!$this->hasFailure()) {
                    throw new Exception('Error: The failure callback has not been set.');
                }
                $this->setState(self::REJECTED);
                $this->failure->call([
                    'response' => $this->promiser->getResponse()
                ]);
            }
        }

        if ($this->hasFinally()) {
            $this->finally->call(['promise' => $this]);
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
        if (!$this->hasCancel()) {
            throw new Exception('Error: The cancel callback has not been set.');
        }
        $this->setState(self::CANCELLED);
        $this->cancel->call(['promise' => $this]);
    }

}