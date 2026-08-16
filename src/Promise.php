<?php
declare(strict_types=1);
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
namespace Pop\Http;

use Pop\Http\Client\Handler\CurlMulti;
use Pop\Http\Client\Response;
use Pop\Http\Promise\Exception;
use ReflectionException;

/**
 * HTTP promise class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Promise extends Promise\AbstractPromise
{

    /**
     * Constructor
     *
     * Instantiate the Promise object
     *
     * @param  Client|CurlMulti $promiser
     */
    public function __construct(Client|CurlMulti $promiser)
    {
        $this->setPromiser($promiser);
    }

    /**
     * Factory to create a Promise object
     *
     * @param  Client|CurlMulti $promiser
     * @return static
     */
    public static function create(Client|CurlMulti $promiser): static
    {
        return new static($promiser);
    }

    /**
     * Wait method
     *
     * @param  bool $unwrap
     * @throws Exception|Promise\Exception|ReflectionException|Client\Exception|\Pop\Utils\Exception|\Pop\Http\Exception
     * @return Response|string|array|null
     */
    public function wait(bool $unwrap = true): Response|string|array|null
    {
        $multi = ($this->promiser instanceof CurlMulti);
        $auto  = (!($multi) && ($this->promiser->hasOption('auto')) &&
            ($this->promiser->getOption('auto')));

        if (($this->isFulfilled()) && ($this->promiser->isComplete())) {
            return $this->resolveResponse($multi, $auto);
        }

        $this->setState(self::PENDING);

        if ($multi) {
            $running = null;
            do {
                $this->promiser->send($running);
            } while ($running);
        } else {
            $this->promiser->dispatch();
        }

        if ($this->promiser->isComplete()) {
            if ($this->promiser->isError()) {
                $this->setState(self::REJECTED);
                if ($unwrap) {
                    if ($multi) {
                        throw new Exception('Error: There was an error with one of the multiple requests.');
                    } else {
                        throw new Exception(
                            'Error: ' . $this->promiser->getResponse()->getCode() . ' ' .
                            $this->promiser->getResponse()->getMessage()
                        );
                    }
                }
            } else {
                $this->setState(self::FULFILLED);
                return $this->resolveResponse($multi, $auto);
            }
        } else if ($unwrap) {
            throw new Exception('Error: Unable to complete request.');
        }

        return null;
    }

    /**
     * Resolve the promiser's current response(s) - the same 3-way resolution
     * (all responses for a multi promiser, auto-parsed vs raw Response otherwise)
     * used by every response-fetching call site in wait()/resolve()
     *
     * @param  bool $multi
     * @param  bool $auto
     * @return Response|string|array|null
     */
    protected function resolveResponse(bool $multi, bool $auto): Response|string|array|null
    {
        if ($multi) {
            return $this->promiser->getAllResponses();
        }

        return (($auto) && ($this->promiser->hasResponse())) ?
            $this->promiser->getResponse()->getParsedResponse() : $this->promiser->getResponse();
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

        $multi = ($this->promiser instanceof CurlMulti);
        $auto  = (!($multi) && ($this->promiser->hasOption('auto')) &&
            ($this->promiser->getOption('auto')));

        if ($multi) {
            $running = null;
            do {
                $this->promiser->send($running);
            } while ($running);
        } else {
            $this->promiser->dispatch();
        }

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
                        $response = $this->resolveResponse($multi, $auto);
                        $result = $success->call([
                            'response' => $response
                        ]);
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
                $response = $this->resolveResponse($multi, $auto);
                $this->failure->call([
                    'response' => $response
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
