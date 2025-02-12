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
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Promise;

/**
 * HTTP client curl multi handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.3.2
 */
class CurlMulti extends AbstractCurl
{

    /**
     * Curl clients
     * @var array
     */
    protected array $clients = [];

    /**
     * Constructor
     *
     * Instantiate the Curl multi handler object
     *
     * @param  ?array $options
     * @throws Exception
     */
    public function __construct(?array $options = null)
    {
        if (!function_exists('curl_multi_init')) {
            throw new Exception('Error: Curl multi handler support is not available.');
        }

        $this->resource = curl_multi_init();

        if (!empty($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Factory method to create a Curl multi handler
     *
     * @param  ?array $options
     * @return CurlMulti
     */
    public static function create(?array $options = null): CurlMulti
    {
        return new self($options);
    }

    /**
     * Set Curl option
     *
     * @param  int   $opt
     * @param  mixed $val
     * @return AbstractCurl
     */
    public function setOption(int $opt, mixed $val): AbstractCurl
    {
        parent::setOption($opt, $val);
        curl_multi_setopt($this->resource, $opt, $val);

        return $this;
    }

    /**
     * Add Curl client
     *
     * @param  Client $curlClient
     * @param  ?string $name
     * @return CurlMulti
     */
    public function addClient(Client $curlClient, ?string $name = null): CurlMulti
    {
        if (!in_array($curlClient, $this->clients) && ($curlClient->hasHandler())) {
            if ($name !== null) {
                $this->clients[$name] = $curlClient;
            } else {
                $this->clients[] = $curlClient;
            }

            $curlClient->getHandler()->prepare($curlClient->getRequest(), $curlClient->getAuth());

            curl_multi_add_handle($this->resource, $curlClient->getHandler()->resource());
        }

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
        if (!empty($response)) {
            $curlClient->getHandler()->setResponse($response);
            return $curlClient;
        } else {
            return $response;
        }
    }

    /**
     * Get Curl response content
     *
     * @param  string|Client $curlClient
     * @return mixed
     */
    public function parseResponse(string|Client $curlClient): mixed
    {
        $response = $this->getClientContent($curlClient);

        if ($curlClient instanceof Client) {
            return $curlClient->getHandler()->parseResponse();
        } else {
            return $response;
        }
    }

    /**
     * Get all responses
     *
     * @return array
     */
    public function getAllResponses(): array
    {
        $responses = [];

        foreach ($this->clients as $curlClient) {
            $response = $this->parseResponse($curlClient);
            if ($response instanceof Response) {
                $auto        = (($curlClient->hasOption('auto')) && ($curlClient->getOption('auto')));
                $responses[] = [
                    'client_uri' => $curlClient->getRequest()->getUriAsString(),
                    'method'     => $curlClient->getRequest()->getMethod(),
                    'code'       => $response->getCode(),
                    'response'   => ($auto) ? $response->getParsedResponse() : $response
                ];
            } else {
                $responses[] = $response;
            }
        }

        return $responses;
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
     * Determine if the response is complete
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        $info = $this->getInfo();
        return (is_array($info) && isset($info['msg']) && ($info['msg'] == CURLMSG_DONE));
    }

    /**
     * Determine if the response is a success
     *
     * @param  bool $strict
     * @return bool|null
     */
    public function isSuccess(bool $strict = true): bool|null
    {
        $result = null;

        if ($this->isComplete()) {
            $responses = $this->getAllResponses();
            $result    = true;
            foreach ($responses as $response) {
                if (!empty($response['code'])) {
                    $codeResult = floor($response['code'] / 100);
                    if (($strict) && (!(($codeResult == 1) || ($codeResult == 2) || ($codeResult == 3)))) {
                        $result = false;
                        break;
                    } else if ((!$strict) && ((($codeResult == 1) || ($codeResult == 2) || ($codeResult == 3)))) {
                        $result = true;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Determine if the response is an error
     *
     * @param  bool $strict
     * @return bool|null
     */
    public function isError(bool $strict = false): bool|null
    {
        $result = null;

        if ($this->isComplete()) {
            $responses = $this->getAllResponses();
            foreach ($responses as $response) {
                if (!empty($response['code'])) {
                    $codeResult = floor($response['code'] / 100);
                    if (($strict) && (!(($codeResult == 4) || ($codeResult == 5)))) {
                        $result = false;
                        break;
                    } else if ((!$strict) && ((($codeResult == 4) || ($codeResult == 5)))) {
                        $result = true;
                        break;
                    }
                }
            }
        }

        return $result;
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
     * Method to send the request asynchronously
     *
     * @return Promise
     */
    public function sendAsync(): Promise
    {
        return new Promise($this);
    }

    /**
     * Method to reset the handler
     *
     * @return CurlMulti
     */
    public function reset(): CurlMulti
    {
        foreach ($this->clients as $key => $curlClient) {
            $this->removeClient($key);
        }

        $this->clients = [];

        return $this;
    }

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->hasResource()) {
            curl_multi_close($this->resource);
            $this->resource = null;
            $this->options  = [];
            $this->clients  = [];
        }
    }

}
