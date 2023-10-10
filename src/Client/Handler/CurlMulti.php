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
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;

/**
 * HTTP client curl multi handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class CurlMulti extends AbstractCurl
{

    /**
     * Curl clients
     * @var array
     */
    protected array $clients = [];

    /**
     * Add Curl client
     *
     * @param  Client $curlClient
     * @param  ?string $name
     * @return CurlMulti
     */
    public function addClient(Client $curlClient, ?string $name = null): CurlMulti
    {
        if ($name !== null) {
            $this->clients[$name] = $curlClient;
        } else {
            $this->clients[] = $curlClient;
        }

        $curlClient->getHandler()->prepare($curlClient->getRequest(), $curlClient->getAuth());

        curl_multi_add_handle($this->resource, $curlClient->getHandler()->resource());

        return $this;
    }

    /**
     * Add Curl clients
     *
     * @param  array $clients
     * @return CurlMulti
     */
    public function addClients(array $clients): CurlMulti
    {
        foreach ($clients as $name => $client) {
            if (is_numeric($name)) {
                $name = null;
            }
            $this->addClient($client, $name);
        }

        return $this;
    }

    /**
     * Get Curl client
     *
     * @return Client|null
     */
    public function getClient(string $name): Client|null
    {
        return $this->clients[$name] ?? null;
    }

    /**
     * Has Curl client
     *
     * @return bool
     */
    public function hasClient(string $name): bool
    {
        return isset($this->clients[$name]);
    }

    /**
     * Get Curl clients
     *
     * @return array
     */
    public function getClients(): array
    {
        return $this->clients;
    }

    /**
     * Remove Curl client
     *
     * @param  ?string      $name
     * @param  Client|null $curlClient
     * @throws Exception
     * @return CurlMulti
     */
    public function removeClient(?string $name = null, Client|null $curlClient = null): CurlMulti
    {
        if (($name !== null) && isset($this->clients[$name])) {
            $curlClient = $this->clients[$name];
            unset($this->clients[$name]);
        } else if ($curlClient !== null) {
            foreach ($this->clients as $i => $client) {
                if ($client == $curlClient) {
                    unset($this->clients[$i]);
                }
            }
        } else {
            throw new Exception('Error: You must pass at least a name or client parameter.');
        }

        $curlResource = $curlClient?->getHandler()?->resource();

        if (!empty($curlResource)) {
            curl_multi_remove_handle($this->resource, $curlResource);
        }

        return $this;
    }

    /**
     * Get Curl client content
     *
     * @param  string|Client $curlClient
     * @return Client|string|null
     */
    public function getClientContent(string|Client $curlClient): Client|string|null
    {
        if (is_string($curlClient) && isset($this->clients[$curlClient])) {
            if ($this->clients[$curlClient] instanceof Client) {
                $curlClient  = $this->clients[$curlClient];
                $curlResource = $curlClient?->getHandler()?->resource();
            }
        } else {
            $curlResource = $curlClient?->getHandler()?->resource();
        }

        $response = (!empty($curlResource)) ? curl_multi_getcontent($curlResource) : null;
        if ($response !== null) {
            $curlClient->getHandler()->setResponse($response);
            return $curlClient;
        } else {
            return $response;
        }
    }

    /**
     * Process Curl response content
     *
     * @param  string|Client $curlClient
     * @return mixed
     */
    public function processResponse(string|Client $curlClient): mixed
    {
        $response = $this->getClientContent($curlClient);

        if ($curlClient instanceof Client) {
            return $curlClient->getHandler()->parseResponse();
        } else {
            return $response;
        }
    }

    /**
     * Get info about the Curl multi-handler
     *
     * @return array|false
     */
    public function getInfo(): array|false
    {
        return curl_multi_info_read($this->resource);
    }

    /**
     * Set a wait time until there is any activity on a connection
     *
     * @return int
     */
    public function setWait(float $timeout = 1.0): int
    {
        return curl_multi_select($this->resource, $timeout);
    }

    /**
     * Method to prepare the handler
     *
     * @param  Request $request
     * @param  ?Auth   $auth
     * @return CurlMulti
     */
    public function prepare(Request $request, ?Auth $auth = null): CurlMulti
    {
        return $this;
    }

    /**
     * Method to send the multiple Curl connections
     *
     * @param  ?int $active
     * @return int
     */
    public function send(?int &$active = null): int
    {
        return curl_multi_exec($this->resource, $active);
    }

    /**
     * Parse the response
     *
     * @return Response
     */
    public function parseResponse(): Response
    {

    }

    /**
     * Method to reset the handler
     *
     * @return CurlMulti
     */
    public function reset(): CurlMulti
    {
        return $this;
    }

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void
    {

    }

}
