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

use Pop\Http\Client\Data;
use Pop\Http\Client\Handler\Stream;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\Curl;
use Pop\Http\Client\Handler\CurlMulti;
use Pop\Http\Client\Handler\HandlerInterface;
use Pop\Mime\Part\Body;
use Pop\Mime\Part\Header;

/**
 * HTTP client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
class Client extends AbstractHttp
{

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
     * Optional parameters are
     *  - Request URI string
     *  - Client request instance
     *  - Client response instance
     *  - Client handler instance
     *  - Auth instance
     *  - Options array
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
     * Factory to create a multi-handler object
     *
     * @param  array                    $requests
     * @param  Client\Handler\CurlMulti $multiHandler
     * @return CurlMulti
     */
    public static function createMulti(
        array $requests, Client\Handler\CurlMulti $multiHandler = new Client\Handler\CurlMulti()
    ): CurlMulti
    {
        foreach ($requests as $request) {
            $client = new Client($request);
            $client->setMultiHandler($multiHandler);
        }

        return $multiHandler;
    }

    /**
     * Method to convert Curl CLI command to a client object
     *
     * @param  string $command
     * @throws Curl\Exception
     * @return Client
     */
    public static function fromCurlCommand(string $command): Client
    {
        return Curl\Command::commandToClient($command);
    }

    /**
     * Set method
     *
     * @param  string $method
     * @return Client
     */
    public function setMethod(string $method): Client
    {
        if ($this->hasRequest()) {
            $this->request->setMethod($method);
        } else {
            $this->options['method'] = $method;
        }

        return $this;
    }

    /**
     * Get method
     *
     * @return ?string
     */
    public function getMethod(): ?string
    {
        if ($this->hasRequest()) {
            return $this->request->getMethod();
        } else {
            return $this->options['method'] ?? null;
        }
    }

    /**
     * Has method
     *
     * @return bool
     */
    public function hasMethod(): bool
    {
        return ((($this->hasRequest()) && !empty($this->request->getMethod())) || isset($this->options['method']));
    }

    /**
     * Set options
     *
     * Supported options
     *  - 'base_uri'
     *  - 'method'
     *  - 'headers'
     *  - 'user_agent'
     *  - 'query' (can only be encoded query string on the URI)
     *  - 'data' (can be any request data)
     *  - 'files'
     *  - 'type'
     *  - 'auto'
     *  - 'async'
     *  - 'verify_peer'
     *  - 'allow_self_signed'
     *  - 'force_custom_method' (Curl only - forces CURLOPT_CUSTOMREQUEST)
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
     * Add options
     *
     * @param  array $options
     * @return Client
     */
    public function addOptions(array $options): Client
    {
        foreach ($options as $name => $value) {
            $this->addOption($name, $value);
        }
        return $this;
    }

    /**
     * Add an option
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Client
     */
    public function addOption(string $name, mixed $value): Client
    {
        $this->options[$name] = $value;
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
        return array_key_exists($name, $this->options);
    }

    /**
     * Remove option
     *
     * @param  string $name
     * @return Client
     */
    public function removeOption(string $name): Client
    {
        if (isset($this->options[$name])) {
            unset($this->options[$name]);
        }
        return $this;
    }

    /**
     * Remove options
     *
     * @return Client
     */
    public function removeOptions(): Client
    {
        $this->options = [];
        return $this;
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
     * @return ?Auth
     */
    public function getAuth(): ?Auth
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
     * Set headers (clear out any existing headers)
     *
     * @param  array $headers
     * @return Client
     */
    public function setHeaders(array $headers): Client
    {
        if ($this->hasRequest()) {
            $this->request->setHeaders($headers);
        } else {
            $this->options['headers'] = $headers;
        }
        return $this;
    }

    /**
     * Add headers
     *
     * @param  array $headers
     * @return Client
     */
    public function addHeaders(array $headers): Client
    {
        if ($this->hasRequest()) {
            $this->request->addHeaders($headers);
        } else {
            $this->options['headers'] = $headers;
        }
        return $this;
    }

    /**
     * Add header
     *
     * @param  Header|string|int $header
     * @param  mixed             $value
     * @return Client
     */
    public function addHeader(Header|string|int $header, mixed $value = null): Client
    {
        if ($this->hasRequest()) {
            $this->request->addHeader($header, $value);
        } else {
            if (!isset($this->options['headers'])) {
                $this->options['headers'] = [];
            }
            if ($header instanceof Header) {
                $this->options['headers'][$header->getName()] = $header;
            } else {
                $this->options['headers'][$header] = $value;
            }
        }

        return $this;
    }

    /**
     * Get headers
     *
     * @return mixed
     */
    public function getHeaders(): mixed
    {
        if (($this->hasRequest()) && ($this->request->hasHeaders())) {
            return $this->request->getHeaders();
        } else {
            return $this->options['headers'] ?? null;
        }
    }

    /**
     * Get header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader(string $name): mixed
    {
        if (($this->hasRequest()) && ($this->request->hasHeader($name))) {
            return $this->request->getHeader($name);
        } else {
            return (isset($this->options['headers'][$name])) ? $this->options['headers'][$name] : null;
        }
    }

    /**
     * Has headers
     *
     * @return bool
     */
    public function hasHeaders(): bool
    {
        if (($this->hasRequest()) && ($this->request->hasHeaders())) {
            return true;
        } else {
            return !empty($this->options['headers']);
        }
    }

    /**
     * Has header
     *
     * @param  string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        if (($this->hasRequest()) && ($this->request->hasHeader($name))) {
            return true;
        } else {
            return isset($this->options['headers'][$name]);
        }
    }

    /**
     * Remove header
     *
     * @param  string $name
     * @return Client
     */
    public function removeHeader(string $name): Client
    {
        if (($this->hasRequest()) && ($this->request->hasHeader($name))) {
            $this->request->removeHeader($name);
        }
        if (isset($this->options['headers'][$name])) {
            unset($this->options['headers'][$name]);
        }

        return $this;
    }

    /**
     * Remove all headers
     *
     * @return Client
     */
    public function removeAllHeaders(): Client
    {
        if (($this->hasRequest()) && ($this->request->hasHeaders())) {
            $this->request->removeHeaders();
        }
        if (isset($this->options['headers'])) {
            unset($this->options['headers']);
        }

        return $this;
    }

    /**
     * Set data
     *
     * @param  array $data
     * @return Client
     */
    public function setData(array $data): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $this->request->setData($data);
        } else {
            $this->options['data'] = $data;
        }

        return $this;
    }

    /**
     * Add data
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Client
     */
    public function addData(string $name, mixed $value): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $this->request->addData($name, $value);
        } else {
            if (!isset($this->options['data'])) {
                $this->options['data'] = [];
            }
            $this->options['data'][$name] = $value;
        }

        return $this;
    }

    /**
     * Get data
     *
     * @param  ?string $key
     * @return mixed
     */
    public function getData(?string $key = null): mixed
    {
        $data = null;

        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $data = $this->request->getData()->getData($key);
        }

        if (($data === null) && isset($this->options['data'])) {
            if ($key !== null) {
                $data = (isset($this->options['data'][$key])) ?
                    $this->options['data'][$key] : null;
            } else {
                $data = $this->options['data'] ?? null;
            }
        }

        return $data;
    }

    /**
     * Has data
     *
     * @param  ?string $key
     * @return bool
     */
    public function hasData(?string $key = null): bool
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request) &&
            ($this->request->hasData()) && ($this->request->getData()->hasData($key))) {
            return true;
        } else {
            return ($key !== null) ? isset($this->options['data'][$key]) : isset($this->options['data']);
        }
    }

    /**
     * Remove data
     *
     * @param  string $key
     * @return Client
     */
    public function removeData(string $key): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request) &&
            ($this->request->hasData()) && ($this->request->getData()->hasData($key))) {
            $this->request->removeData($key);
        }
        if (isset($this->options['data'][$key])) {
            unset($this->options['data'][$key]);
        }

        return $this;
    }

    /**
     * Remove all data
     *
     * @return Client
     */
    public function removeAllData(): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request) && ($this->request->hasData())) {
            $this->request->removeAllData();
        }
        if (isset($this->options['data'])) {
            unset($this->options['data']);
        }

        return $this;
    }

    /**
     * Set query
     *
     * @param  array $query
     * @return Client
     */
    public function setQuery(array $query): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $this->request->setQuery(new Data($query));
        } else {
            $this->options['query'] = $query;
        }

        return $this;
    }

    /**
     * Add query
     *
     * @param  string $name
     * @param  mixed  $value
     * @return Client
     */
    public function addQuery(string $name, mixed $value): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $this->request->addQuery($name, $value);
        } else {
            if (!isset($this->options['query'])) {
                $this->options['query'] = [];
            }
            $this->options['query'][$name] = $value;
        }

        return $this;
    }

    /**
     * Get query
     *
     * @param  ?string $key
     * @return mixed
     */
    public function getQuery(?string $key = null): mixed
    {
        $query = null;

        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $query = $this->request->getQuery()->getData($key);
        }

        if (($query === null) && isset($this->options['query'])) {
            if ($key !== null) {
                $query = (isset($this->options['query'][$key])) ?
                    $this->options['query'][$key] : null;
            } else {
                $query = $this->options['query'] ?? null;
            }
        }

        return $query;
    }

    /**
     * Has query
     *
     * @param  ?string $key
     * @return bool
     */
    public function hasQuery(?string $key = null): bool
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request) &&
            ($this->request->hasQuery()) && ($this->request->getQuery()->hasData($key))) {
            return true;
        } else {
            return ($key !== null) ? isset($this->options['query'][$key]) : isset($this->options['query']);
        }
    }

    /**
     * Remove query
     *
     * @param  string $key
     * @return Client
     */
    public function removeQuery(string $key): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request) &&
            ($this->request->hasQuery()) && ($this->request->getQuery()->hasData($key))) {
            $this->request->removeQuery($key);
        }
        if (isset($this->options['query'][$key])) {
            unset($this->options['query'][$key]);
        }

        return $this;
    }

    /**
     * Remove all query data
     *
     * @return Client
     */
    public function removeAllQuery(): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request) && ($this->request->hasQuery())) {
            $this->request->removeAllQuery();
        }
        if (isset($this->options['query'])) {
            unset($this->options['query']);
        }

        return $this;
    }

    /**
     * Set type
     *
     * @param  string $type
     * @param  bool   $addHeader
     * @return Client
     */
    public function setType(string $type, bool $addHeader = true): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $this->request->setRequestType($type, $addHeader);
        } else {
            $this->options['type'] = $type;
        }

        return $this;
    }

    /**
     * Get type
     *
     * @return mixed
     */
    public function getType(): mixed
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            return $this->request->getRequestType();
        } else {
            return $this->options['type'] ?? null;
        }
    }

    /**
     * Has type
     *
     * @return bool
     */
    public function hasType(): bool
    {
        return ((($this->hasRequest()) && ($this->request instanceof Client\Request))) ?
            $this->request->hasRequestType() : isset($this->options['type']);
    }

    /**
     * Remove type
     *
     * @return Client
     */
    public function removeType(): Client
    {
        if (($this->hasRequest()) && ($this->request instanceof Client\Request)) {
            $this->request->removeRequestType();
        }
        if (isset($this->options['type'])) {
            unset($this->options['type']);
        }

        return $this;
    }

    /**
     * Set files
     *
     * @param  array|string $files
     * @param  bool         $multipart
     * @throws Exception
     * @return Client
     */
    public function setFiles(array|string $files, bool $multipart = true): Client
    {
        if (is_string($files)) {
            $files = [$files];
        }

        $filenames = [];

        foreach ($files as $i => $file) {
            if (!file_exists($file)) {
                throw new Exception("Error: The file '" . $file . "' does not exist.");
            }

            $name = (is_numeric($i)) ? 'file' . ($i + 1) : $i;
            $filenames[$name] = $file;
        }

        $this->options['files'] = $filenames;

        if ($multipart) {
            $this->options['type'] = Request::MULTIPART;
        }
        return $this;
    }

    /**
     * Add file
     *
     * @param  string  $file
     * @param  ?string $name
     * @throws Exception
     * @return Client
     */
    public function addFile(string $file, ?string $name = null): Client
    {
        if (!file_exists($file)) {
            throw new Exception("Error: The file '" . $file . "' does not exist.");
        }

        if (!isset($this->options['files'])) {
            $this->options['files'] = [];
        }

        if ($name === null) {
            $i = 1;
            $name = 'file' . $i;
            while (isset($this->options['files'][$name])) {
                $i++;
                $name = 'file' . $i;
            }
        }

        $this->options['files'][$name] = $file;
        return $this;
    }

    /**
     * Get files
     *
     * @return array|null
     */
    public function getFiles(): array|null
    {
        return $this->options['files'] ?? null;
    }

    /**
     * Get file
     *
     * @param  string $key
     * @return ?string
     */
    public function getFile(string $key): ?string
    {
        return (isset($this->options['files'][$key])) ?
                $this->options['files'][$key] : null;
    }

    /**
     * Has files
     *
     * @return bool
     */
    public function hasFiles(): bool
    {
        return isset($this->options['files']);
    }

    /**
     * Has file
     *
     * @param  string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return (isset($this->options['files'][$key]));
    }

    /**
     * Remove file
     *
     * @param  string $key
     * @return Client
     */
    public function removeFile(string $key): Client
    {
        if (isset($this->options['files'][$key])) {
            unset($this->options['files'][$key]);
        }

        return $this;
    }

    /**
     * Remove all files
     *
     * @return Client
     */
    public function removeFiles(): Client
    {
        if (isset($this->options['files'])) {
            unset($this->options['files']);
        }

        return $this;
    }

    /**
     * Set request body
     *
     * @param  string $body
     * @throws Exception
     * @return Client
     */
    public function setBody(string $body): Client
    {
        if ($this->request === null) {
            throw new Exception('Error: The request object has not been created.');
        }

        $this->request->setBody($body);

        return $this;
    }

    /**
     * Set request body from file
     *
     * @param  string $file
     * @throws Exception
     * @return Client
     */
    public function setBodyFromFile(string $file): Client
    {
        if ($this->request === null) {
            throw new Exception('Error: The request object has not been created.');
        }
        if (!file_exists($file)) {
            throw new Exception("Error: The file '" . $file . "' does not exist.");
        }

        $this->request->setBody(file_get_contents($file));

        return $this;
    }

    /**
     * Has request body
     *
     * @return bool
     */
    public function hasBody(): bool
    {
        return (($this->request !== null) && ($this->request->hasBody()));
    }

    /**
     * Get request body
     *
     * @return ?Body
     */
    public function getBody(): ?Body
    {
        return (($this->request !== null) && ($this->request->hasBody())) ? $this->request->getBody() : null;
    }

    /**
     * Get request body content
     *
     * @return ?string
     */
    public function getBodyContent(): ?string
    {
        return (($this->request !== null) && ($this->request->hasBody())) ? $this->request->getBodyContent() : null;
    }

    /**
     * Get request body content length
     *
     * @param  bool $mb
     * @return int
     */
    public function getBodyContentLength(bool $mb = false): int
    {
        return (($this->request !== null) && ($this->request->hasBody())) ? $this->request->getBodyContentLength($mb) : 0;
    }

    /**
     * Remove the body
     *
     * @return Client
     */
    public function removeBody(): Client
    {
        if (($this->request !== null) && ($this->request->hasBody())) {
            $this->request->removeBody();
        }
        return $this;
    }

    /**
     * Prepare the client request
     *
     * @param  ?string $uri
     * @param  ?string $method
     * @throws Exception|Client\Exception
     * @return Client
     */
    public function prepare(?string $uri = null, string $method = null): Client
    {
        // Check that there is a request object or a URI
        if ((!$this->hasRequest()) && ($uri === null) && !isset($this->options['base_uri'])) {
            throw new Exception('Error: There is no request URI to send.');
        }

        if (($method === null) && isset($this->options['method'])) {
            $method = $this->options['method'];
        }

        if ($this->hasRequest()) {
            // Set request URI
            if ($uri !== null) {
                if (isset($this->options['base_uri']) && !str_starts_with($uri, $this->options['base_uri'])) {
                    $uri = $this->options['base_uri'] . $uri;
                }
                $this->request->setUri(new Uri($uri));
            // Else, check and adjust for base_uri
            } else if (isset($this->options['base_uri']) && !str_starts_with($this->request->getUriAsString(), $this->options['base_uri'])) {
                $this->request->setUri($this->options['base_uri'] . $this->request->getUriAsString());
            }

            // Set method
            if ($method !== null) {
                $this->request->setMethod($method);
            }
        // Create new request object
        } else {
            if (($uri === null) && isset($this->options['base_uri'])) {
                $uri = $this->options['base_uri'];
            } else if (isset($this->options['base_uri']) && !str_starts_with($uri, $this->options['base_uri'])) {
                $uri = $this->options['base_uri'] . $uri;
            }
            $this->setRequest(new Request(new Uri($uri), ($method ?? 'GET')));
        }

        // Set request type
        if ($this->hasOption('type')) {
            $this->request->setRequestType($this->options['type']);
        }

        // Add headers
        if (($this->hasOption('headers')) && is_array($this->options['headers'])) {
            $this->request->addHeaders($this->options['headers']);
        }

        // Add query
        if ($this->hasOption('query')) {
            $this->request->setQuery($this->options['query']);
        }

        // Add data
        $data = [];
        if ($this->hasOption('data')) {
            $data = $this->options['data'];
        }

        // Add files
        if ($this->hasOption('files')) {
            $files = $this->options['files'];
            foreach ($files as $file => $value) {
                $file = (is_numeric($file)) ? 'file' . ($file + 1) : $file;
                $data[$file] = [
                    'filename'    => $value,
                    'contentType' => Client\Data::getMimeTypeFromFilename($value)
                ];
            }
        }

        if (!empty($data)) {
            $this->request->setData($data);
        }

        // Set (or reset) handler
        if (!$this->hasHandler()) {
            $this->setHandler(new Curl());
        } else {
            $this->getHandler()->reset();
        }

        // Set user-agent
        if ($this->hasOption('user_agent')) {
            if ($this->handler instanceof Curl) {
                $this->handler->setOption(CURLOPT_USERAGENT, $this->options['user_agent']);
            } else if ($this->handler instanceof Stream) {
                $this->handler->addContextOption('http', ['user_agent' => $this->options['user_agent']]);
            }
        }

        // Handle SSL options
        if (!($this->handler instanceof CurlMulti)) {
            if ($this->hasOption('verify_peer')) {
                $this->handler->setVerifyPeer((bool)$this->options['verify_peer']);
            }
            if ($this->hasOption('allow_self_signed')) {
                $this->handler->allowSelfSigned((bool)$this->options['allow_self_signed']);
            }
        }

        return $this;
    }

    /**
     * Send the client request
     *
     * @param  ?string $uri
     * @param  ?string $method
     * @throws Exception|Client\Exception|Client\Handler\Exception
     * @return Response|Promise|array|string
     */
    public function send(?string $uri = null, string $method = null): Response|Promise|array|string
    {
        if (isset($this->options['async']) && ($this->options['async'] === true)) {
            return $this->sendAsync();
        } else {
            $this->prepare($uri, $method);
            $this->response = (isset($this->options['force_custom_method']) && ($this->handler instanceof Curl)) ?
                $this->handler->prepare($this->request, $this->auth, (bool)$this->options['force_custom_method'])->send() :
                $this->handler->prepare($this->request, $this->auth)->send();

            return (($this->hasOption('auto')) && ($this->options['auto']) && ($this->response instanceof Response)) ?
                $this->response->getParsedResponse() : $this->response;
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
     * Method to render the request as a string
     *
     * @return string
     */
    public function render(): string
    {
        $this->prepare();

        if (isset($this->options['force_custom_method']) && ($this->handler instanceof Curl)) {
            $this->handler->prepare($this->request, $this->auth, (bool)$this->options['force_custom_method']);
        } else {
            $this->handler->prepare($this->request, $this->auth);
        }

        $uri       = $this->handler->getUriObject();
        $uriString = $uri->getUri();
        if ($uri->hasQuery()) {
            $uriString .= '?' . $uri->getQuery();
        }

        $request = $this->request->getMethod() . ' ' . $uriString . ' HTTP/' . $this->handler->getHttpVersion() . "\r\n" .
            'Host: ' . $uri->getFullHost() . "\r\n" . $this->request->getHeadersAsString() . "\r\n";

        if ($this->request->hasDataContent()) {
            $request .= $this->request->getDataContent();
        }
        return $request;
    }

    /**
     * Method to reset the client
     *
     * @param  bool $data
     * @param  bool $headers
     * @param  bool $clear
     * @return static
     */
    public function reset(?bool $data = true, ?bool $headers = false, bool $clear = false): static
    {
        // Fully clear out and reset of client object
        if ($clear) {
            $this->request  = null;
            $this->response = null;
            $this->handler  = null;
            $this->auth     = null;
            $this->options  = [];
        // Clear out basic client info & data
        } else {
            if ($data) {
                if (isset($this->options['query'])) {
                    unset($this->options['query']);
                }
                if (isset($this->options['data'])) {
                    unset($this->options['data']);
                }
                if (isset($this->options['files'])) {
                    unset($this->options['files']);
                }
                if ($this->request !== null) {
                    if ($this->request->hasQuery()) {
                        $this->request->removeAllQuery();
                    }
                    if ($this->request->hasData()) {
                        $this->request->removeAllData();
                    }
                }
            }
            if ($headers) {
                if (isset($this->options['headers'])) {
                    unset($this->options['headers']);
                }
                if (isset($this->options['user_agent'])) {
                    unset($this->options['user_agent']);
                }
                if ($this->request !== null) {
                    if ($this->request->hasHeaders()) {
                        $this->request->removeHeaders();
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Method to convert client object to a Curl command for the CLI
     *
     * @throws Exception|Curl\Exception
     * @return string
     */
    public function toCurlCommand(): string
    {
        if ($this->handler instanceof Stream) {
            throw new Exception('Error: The client handler must be an instance of Curl');
        }

        if (!$this->hasHandler()) {
            $this->setHandler(new Curl());
        }

        return Curl\Command::clientToCommand($this);
    }

    /**
     * To string magic method to render the client request to a raw string
     *
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Magic method to send requests by the method name, i.e. $client->get('http://localhost/');
     *
     * @param  string $methodName
     * @param  array  $arguments
     * @throws Exception|Client\Exception|Client\Handler\Exception
     * @return Response|Promise|array|string
     */
    public function __call(string $methodName, array $arguments): Response|Promise|array|string
    {
        if (str_contains($methodName, 'Async')) {
            if (isset($arguments[0])) {
                $methodName = strtoupper(substr($methodName, 0, strpos($methodName, 'Async')));
                $this->prepare($arguments[0], $methodName);
            }
            return $this->sendAsync();
        } else {
            return $this->send(($arguments[0] ?? null), strtoupper($methodName));
        }
    }

    /**
     * Magic method to send requests by the static method name, i.e. Client::get('http://localhost/');
     *
     * @param  string $methodName
     * @param  array  $arguments
     * @throws Exception|Client\Exception|Client\Handler\Exception
     * @return Response|Promise|array|string
     */
    public static function __callStatic(string $methodName, array $arguments): Response|Promise|array|string
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
