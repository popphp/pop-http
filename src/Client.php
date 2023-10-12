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

use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\Curl;
use Pop\Http\Client\Handler\CurlMulti;
use Pop\Http\Client\Handler\HandlerInterface;

/**
 * HTTP client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Client extends AbstractHttp
{

    /**
     * Base URI
     * @var ?string
     */
    protected ?string $baseUri = null;

    /**
     * Client options
     * @var array
     */
    protected array $options = [];

    /**
     * Request handler
     * @var ?HandlerInterface
     */
    protected ?HandlerInterface $handler = null;

    /**
     * Request multi-handler
     * @var ?CurlMulti
     */
    protected ?CurlMulti $multiHandler = null;

    /**
     * HTTP auth object
     * @var ?Auth
     */
    protected ?Auth $auth = null;

    /**
     * Instantiate the client object
     *
     * Optional parameters are a request URI string, client request instance, a client response instance,
     * a client handler instance, an auth instance, or an options array
     */
    public function __construct()
    {
        $args     = func_get_args();
        $request  = null;
        $response = null;
        $handler  = null;

        foreach ($args as $arg) {
            if (is_string($arg)) {
                $request = new Request($arg);
            } else if ($arg instanceof Client\Request) {
                $request = $arg;
            } else if ($arg instanceof Client\Response) {
                $response = $arg;
            } else if ($arg instanceof Client\Handler\HandlerInterface) {
                $handler = $arg;
            } else if ($arg instanceof Auth) {
                $this->setAuth($arg);
            } else if (is_array($arg)) {
                $this->setOptions($arg);
            }
        }

        parent::__construct($request, $response);

        if ($handler !== null) {
            if ($handler instanceof CurlMulti) {
                $this->setMultiHandler($handler);
            } else {
                $this->setHandler($handler);
            }
        }
    }

    /**
     * Set base URI
     *
     * @param  ?string $baseUri
     * @return Client
     */
    public function setBaseUri(?string $baseUri = null): Client
    {
        $this->baseUri = $baseUri;
        return $this;
    }

    /**
     * Get base URI
     *
     * @return string
     */
    public function getBaseUri(): string
    {
        return $this->baseUri;
    }

    /**
     * Has base URI
     *
     * @return bool
     */
    public function hasBaseUri(): bool
    {
        return ($this->baseUri !== null);
    }

    /**
     * Set options
     *
     * Supported options
     *  - 'base_uri'
     *  - 'headers'
     *  - 'query'
     *  - 'async'
     *  - 'type'
     *  - 'verify_peer'
     *  - 'allow_self_signed'
     *
     * @param  array $options
     * @return Client
     */
    public function setOptions(array $options): Client
    {
        if (isset($options['base_uri'])) {
            $this->setBaseUri($options['base_uri']);
        }
        $this->options = $options;
        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get options
     *
     * @param  string $name
     * @return mixed
     */
    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Has options
     *
     * @return bool
     */
    public function hasOptions(): bool
    {
        return !empty($this->options);
    }

    /**
     * Has option
     *
     * @return bool
     */
    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    /**
     * Set handler
     *
     * @param  HandlerInterface $handler
     * @return Client
     */
    public function setHandler(HandlerInterface $handler): Client
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * Get handler
     *
     * @return HandlerInterface|null
     */
    public function getHandler(): HandlerInterface|null
    {
        return $this->handler;
    }

    /**
     * Has handler
     *
     * @return bool
     */
    public function hasHandler(): bool
    {
        return ($this->handler !== null);
    }

    /**
     * Set multi-handler
     *
     * @param  CurlMulti $multiHandler
     * @return Client
     */
    public function setMultiHandler(CurlMulti $multiHandler): Client
    {
        $this->multiHandler = $multiHandler;

        if (!($this->handler instanceof Curl)) {
            $this->handler = new Curl();
            $this->multiHandler->addClient($this);
        }

        return $this;
    }

    /**
     * Get multi-handler
     *
     * @return CurlMulti
     */
    public function getMultiHandler(): CurlMulti
    {
        return $this->multiHandler;
    }

    /**
     * Has multi-handler
     *
     * @return bool
     */
    public function hasMultiHandler(): bool
    {
        return ($this->multiHandler !== null);
    }

    /**
     * Set auth
     *
     * @param  Auth $auth
     * @return Client
     */
    public function setAuth(Auth $auth): Client
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Get auth
     *
     * @return Auth|null
     */
    public function getAuth(): Auth|null
    {
        return $this->auth;
    }

    /**
     * Has auth
     *
     * @return bool
     */
    public function hasAuth(): bool
    {
        return ($this->auth !== null);
    }

    /**
     * Prepare the client request
     *
     * @param  ?string $uri
     * @param  ?string  $method
     * @throws Exception|Client\Exception
     * @return Client
     */
    public function prepare(?string $uri = null, string $method = null): Client
    {
        if ((!$this->hasRequest()) && ($uri === null)) {
            throw new Exception('Error: There is no request URI to send.');
        }
        if ($uri !== null) {
            $request = new Request(new Uri($uri), ($method ?? 'GET'));
            $this->setRequest($request);
        } else if ($method !== null) {
            $this->request->setMethod($method);
        }

        if (($this->hasOption('headers')) && is_array($this->options['headers'])) {
            $this->request->addHeaders($this->options['headers']);
        }
        if (($this->hasOption('query')) && is_array($this->options['query'])) {
            $this->request->setData($this->options['query']);
        }
        if ($this->hasOption('type')) {
            $this->request->setRequestType($this->options['type']);
        }

        if (!$this->hasHandler()) {
            $this->setHandler(new Curl());
        }

        if (!($this->handler instanceof CurlMulti)) {
            if ($this->hasOption('verify_peer')) {
                $this->handler->setVerifyPeer((bool)$this->options['verify_peer']);
            }
            if ($this->hasOption('allow_self_signed')) {
                $this->handler->allowSelfSigned((bool)$this->options['allow_self_signed']);
            }
        }

        if (($this->hasBaseUri()) && !str_starts_with($this->request->getUriAsString(), $this->getBaseUri())) {
            $this->request->setUri($this->getBaseUri() . $this->request->getUriAsString());
        }

        return $this;
    }

    /**
     * Send the client request
     *
     * @param  ?string $uri
     * @param  string  $method
     * @throws Exception|Client\Exception
     * @return Response|Promise
     */
    public function send(?string $uri = null, string $method = null): Response|Promise
    {
        if (isset($this->options['async']) && ($this->options['async'] === true)) {
            return $this->sendAsync();
        } else {
            $this->prepare($uri, $method);
            $this->response = $this->handler->prepare($this->request, $this->auth)->send();
            return $this->response;
        }
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
     * Magic method to send requests by the method name, i.e. $client->get('http://localhost/');
     *
     * @param  string $methodName
     * @param  array  $arguments
     * @throws Exception|Client\Exception
     * @return Response|Promise
     */
    public function __call(string $methodName, array $arguments): Response|Promise
    {
        if (str_contains($methodName, 'Async')) {
            if (isset($arguments[0])) {
                $methodName = strtoupper(substr($methodName, 0, strpos($methodName, 'Async')));
                $this->prepare($arguments[0], $methodName);
            }
            return $this->sendAsync();
        } else {
            return (isset($arguments[0])) ? $this->send($arguments[0], strtoupper($methodName)) : $this->send();
        }
    }

    /**
     * Magic method to send requests by the static method name, i.e. Client::get('http://localhost/');
     *
     * @param  string $methodName
     * @param  array  $arguments
     * @throws Exception|Client\Exception
     * @return Response|Promise
     */
    public static function __callStatic(string $methodName, array $arguments): Response|Promise
    {
        $client = new static();
        $uri    = null;

        if (count($arguments) > 1) {
            foreach ($arguments as $arg) {
                if (is_string($arg)) {
                    $client->setRequest(new Client\Request($arg));
                } else if ($arg instanceof Client\Request) {
                    $client->setRequest($arg);
                } else if ($arg instanceof Client\Response) {
                    $client->setResponse($arg);
                } else if ($arg instanceof Client\Handler\HandlerInterface) {
                    $client->setHandler($arg);
                } else if ($arg instanceof Auth) {
                    $client->setAuth($arg);
                } else if (is_array($arg)) {
                    $client->setOptions($arg);
                }
            }
        }

        if ((!$client->hasRequest()) && isset($arguments[0])) {
            $uri = ($arguments[0]);
        }

        if (str_contains($methodName, 'Async')) {
            $methodName = strtoupper(substr($methodName, 0, strpos($methodName, 'Async')));
            $client->prepare($uri, $methodName);
            return $client->sendAsync();
        } else {
            return $client->send($uri, strtoupper($methodName));
        }
    }

}
