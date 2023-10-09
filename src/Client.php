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

use Pop\Http\Client\Handler\HandlerInterface;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;

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
     * HTTP auth object
     * @var ?Auth
     */
    protected ?Auth $auth = null;

    /**
     * Instantiate the client object
     *
     * Optional parameters are a client request instance, a client response instance,
     * a client handler instance, an auth instance, a base path string or an options array
     */
    public function __construct()
    {
        $args     = func_get_args();
        $request  = null;
        $response = null;

        foreach ($args as $arg) {
            if ($arg instanceof Client\Request) {
                $request = $arg;
            } else if ($arg instanceof Client\Response) {
                $response = $arg;
            } else if ($arg instanceof Client\Handler\HandlerInterface) {
                $this->setHandler($arg);
            } else if ($arg instanceof Auth) {
                $this->setAuth($arg);
            } else if (is_string($arg)) {
                $this->setBaseUri($arg);
            } else if (is_array($arg)) {
                $this->setOptions($arg);
            }
        }

        parent::__construct($request, $response);
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
     *  - 'headers'
     *  - 'query'
     *  - 'async'
     *  - 'type'
     *
     * @param  array $options
     * @return Client
     */
    public function setOptions(array $options): Client
    {
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
     * @return HandlerInterface
     */
    public function getHandler(): HandlerInterface
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
     * @return Auth $auth
     */
    public function getAuth(): Auth
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
     * Send the client request
     *
     * @param  ?string $uri
     * @param  string  $method
     * @throws Exception
     * @return Response
     */
    public function send(?string $uri = null, string $method = 'GET'): Response
    {
        if ((!$this->hasRequest()) && ($uri === null)) {
            throw new Exception('Error: There is no request URI to send.');
        }
        if ((!$this->hasRequest()) && ($uri !== null)) {
            $request = new Request(new Uri($uri), $method);
            $this->setRequest($request);
        }

        if (($this->hasOption('headers')) && is_array($this->options['headers'])) {
            $this->request->addHeaders($this->options['headers']);
        }
        if (($this->hasOption('query')) && is_array($this->options['query'])) {
            $this->request->setData($this->options['query']);
        }
        if ($this->hasOption('type')) {
            $this->request->setType($this->options['type']);
        }

        if (!$this->hasHandler()) {
            $this->setHandler(new Client\Handler\Curl());
        }

        return $this->handler->prepare($this->request, $this->auth)->send();
    }

    /**
     * Magic method to send requests by the method name, i.e. $client->get('http://localhost/');
     *
     * @param  string $methodName
     * @param  array  $arguments
     * @throws Exception
     * @return Response
     */
    public function __call(string $methodName, array $arguments): Response
    {
        if (!isset($arguments[0])) {
            throw new Exception('Error: You must pass a URI.');
        }

        return $this->send($arguments[0], strtoupper($methodName));
    }

    /**
     * Magic method to send requests by the static method name, i.e. Client::get('http://localhost/');
     *
     * @param  string $methodName
     * @param  array  $arguments
     * @throws Exception
     * @return Response
     */
    public static function __callStatic(string $methodName, array $arguments): Response
    {
        if (!isset($arguments[0])) {
            throw new Exception('Error: You must pass a URI.');
        }

        $client = new static();

        if (count($arguments) > 1) {
            foreach ($arguments as $arg) {
                if ($arg instanceof Client\Request) {
                    $client->setRequest($arg);
                } else if ($arg instanceof Client\Response) {
                    $client->setResponse($arg);
                } else if ($arg instanceof Client\Handler\HandlerInterface) {
                    $client->setHandler($arg);
                } else if ($arg instanceof Auth) {
                    $client->setAuth($arg);
                } else if (is_string($arg)) {
                    $client->setBaseUri($arg);
                } else if (is_array($arg)) {
                    $client->setOptions($arg);
                }
            }
        }

        return $client->send($arguments[0], strtoupper($methodName));
    }

}
