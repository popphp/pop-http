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
namespace Pop\Http\Client\Curl;

use Pop\Http\Client;
use Pop\Http\Client\Curl;
use CurlHandle;

/**
 * HTTP curl multi-handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class MultiHandler
{

    /**
     * Multi-handler resource object
     * @var mixed
     */
    protected mixed $resource = null;

    /**
     * Curl requests
     * @var array
     */
    protected array $requests = [];

    /**
     * Curl options
     * @var array
     */
    protected array $options = [];

    /**
     * Constructor
     *
     * Instantiate the Curl multi-handler object
     *
     * @param  ?array $opts
     * @throws Exception
     */
    public function __construct(?array $opts = null)
    {
        if (!function_exists('curl_multi_init')) {
            throw new Exception('Error: Curl is not available.');
        }

        $this->resource = curl_multi_init();

        if ($opts !== null) {
            $this->setOptions($opts);
        }
    }

    /**
     * Factory method to create a multi-handler object
     *
     * @param  ?array $opts
     * @throws Exception
     * @return MultiHandler
     */
    public static function create(?array $opts = null): MultiHandler
    {
        return new self($opts);
    }

    /**
     * Determine whether or not resource is available
     *
     * @return bool
     */
    public function hasResource(): bool
    {
        return ($this->resource !== null);
    }

    /**
     * Get the resource
     *
     * @return mixed
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }

    /**
     * Get the resource (alias method)
     *
     * @return mixed
     */
    public function resource(): mixed
    {
        return $this->resource;
    }

    /**
     * Set Curl option
     *
     * @param  int   $opt
     * @param  mixed $val
     * @return MultiHandler
     */
    public function setOption(int $opt, mixed $val): MultiHandler
    {
        // Set the protected property to keep track of the Curl options.
        $this->options[$opt] = $val;
        curl_multi_setopt($this->resource, $opt, $val);

        return $this;
    }

    /**
     * Set Curl options
     *
     * @param  array $opts
     * @return MultiHandler
     */
    public function setOptions(array $opts): MultiHandler
    {
        // Set the protected property to keep track of the Curl options.
        foreach ($opts as $k => $v) {
            $this->setOption($k, $v);
        }

        return $this;
    }

    /**
     * Get a Curl multi option
     *
     * @param  int $opt
     * @return mixed
     */
    public function getOption(int $opt): mixed
    {
        return $this->options[$opt] ?? null;
    }

    /**
     * Has a Curl multi option
     *
     * @param  int $opt
     * @return bool
     */
    public function hasOption(int $opt): bool
    {
        return (isset($this->options[$opt]));
    }

    /**
     * Add Curl request
     *
     * @param  Client\Curl|CurlHandle $curlRequest
     * @param  ?string $name
     * @return MultiHandler
     */
    public function addRequest(Client\Curl|CurlHandle $curlRequest, ?string $name = null): MultiHandler
    {
        if ($name !== null) {
            $this->requests[$name] = $curlRequest;
        } else {
            $this->requests[] = $curlRequest;
        }

        if ($curlRequest instanceof Client\Curl) {
            $curlRequest->open();
            $curlRequest = $curlRequest->resource();
        }

        curl_multi_add_handle($this->resource, $curlRequest);

        return $this;
    }

    /**
     * Add Curl requests
     *
     * @param  array $requests
     * @return MultiHandler
     */
    public function addRequests(array $requests): MultiHandler
    {
        foreach ($requests as $name => $request) {
            if (is_numeric($name)) {
                $name = null;
            }
            $this->addRequest($request, $name);
        }

        return $this;
    }

    /**
     * Get Curl request
     *
     * @return Client\Curl|CurlHandle|null
     */
    public function getRequest(string $name): Client\Curl|CurlHandle|null
    {
        return $this->requests[$name] ?? null;
    }

    /**
     * Has Curl request
     *
     * @return bool
     */
    public function hasRequest(string $name): bool
    {
        return isset($this->requests[$name]);
    }

    /**
     * Get Curl requests
     *
     * @return array
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Remove Curl request
     *
     * @param  ?string $name
     * @param  Client\Curl|CurlHandle|null $curlRequest
     * @throws Exception
     * @return MultiHandler
     */
    public function removeRequest(?string $name = null, Client\Curl|CurlHandle|null $curlRequest = null): MultiHandler
    {
        if (($name !== null) && isset($this->requests[$name])) {
            $curlRequest = $this->requests[$name];
            unset($this->requests[$name]);
        } else if ($curlRequest !== null) {
            foreach ($this->requests as $i => $request) {
                if ($request == $curlRequest) {
                    unset($this->requests[$i]);
                }
            }
        } else {
            throw new Exception('Error: You must pass at least a name or request parameter.');
        }

        if ($curlRequest instanceof Client\Curl) {
            $curlRequest = $curlRequest->resource();
        }

        curl_multi_remove_handle($this->resource, $curlRequest);

        return $this;
    }

    /**
     * Get Curl request content
     *
     * @param  string|Client\Curl|CurlHandle $curlRequest
     * @return mixed
     */
    public function getRequestContent(string|Client\Curl|CurlHandle $curlRequest): mixed
    {
        if (is_string($curlRequest) && isset($this->requests[$curlRequest])) {
            if ($this->requests[$curlRequest] instanceof Client\Curl) {
                $curlRequest = $this->requests[$curlRequest]->resource();
            }
        } else if ($curlRequest instanceof Client\Curl) {
            $curlRequest = $curlRequest->resource();
        }

        return curl_multi_getcontent($curlRequest);
    }

    /**
     * Process Curl response content
     *
     * @param  string|Client\Curl|CurlHandle $curlRequest
     * @return mixed
     */
    public function processResponse(string|Client\Curl|CurlHandle $curlRequest): mixed
    {
        $response = $this->getRequestContent($curlRequest);

        if ($curlRequest instanceof Client\Curl) {
            $curlRequest->parseResponse($response);
            return $curlRequest;
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
     * Return the Curl version
     *
     * @return array
     */
    public function version(): array
    {
        return curl_version();
    }

    /**
     * Get Curl error number
     *
     * @return int
     */
    public function getErrorNumber(): int
    {
        return curl_multi_errno($this->resource);
    }

    /**
     * Get Curl error number
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return curl_multi_strerror(curl_multi_errno($this->resource));
    }

    /**
     * Method to reset the MultiHandler object
     *
     * @return MultiHandler
     */
    public function reset(): MultiHandler
    {
        foreach ($this->requests as $curlRequest) {
            curl_multi_remove_handle($this->resource, $curlRequest->resource());
        }
        $this->requests = [];

        return $this;
    }

    /**
     * Close the Curl connection
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->hasResource()) {
            curl_multi_close($this->resource);
            $this->resource = null;
            $this->options  = [];
            $this->requests = [];
        }
    }

}