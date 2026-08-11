<?php
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

use Pop\Http\Client\Handler\Stream;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\Curl;
use Pop\Http\Client\Handler\CurlMulti;
use Pop\Http\Client\Handler\HandlerInterface;
use Pop\Http\Client\Middleware\CallableMiddleware;
use Pop\Http\Client\Middleware\MiddlewareInterface;
use Pop\Http\Client\Middleware\Pipeline;
use Pop\Http\Body;
use Pop\Mime\Part\Header;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Client extends AbstractHttp implements ClientInterface
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
     * Registered middleware, in registration order (first-registered = outermost)
     * @var array
     */
    protected array $middleware = [];

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
        $options  = null;
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
                $options = $arg;
            }
        }

        parent::__construct($request, $response);

        if ($options !== null) {
            $this->setOptions($options);
        }

        if ($handler !== null) {
            if ($handler instanceof CurlMulti) {
                $this->setMultiHandler($handler);
            } else {
                $this->setHandler($handler);
            }
        }

        if ((!$this->hasRequest()) && (isset($this->options['base_uri']) || $this->hasOption('method') ||
            $this->hasOption('data') || $this->hasOption('query') || $this->hasOption('headers') ||
            $this->hasOption('type') || $this->hasOption('files'))) {
            $this->request = new Request(
                isset($this->options['base_uri']) ? new Uri($this->options['base_uri']) : null,
                $this->options['method'] ?? 'GET'
            );
        }

        $this->syncRequestFromOptions();
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
     * Set the request object
     *
     * Overrides AbstractHttp::setRequest() so that any options already set on this client
     * (via the constructor or setOptions()/addOption()) get synced onto a request that arrives
     * via a direct setRequest() call - not just one materialized internally by prepare(). Without
     * this, options set before an explicit setRequest() call were silently dropped, since
     * syncRequestFromOptions() is a no-op until a request exists.
     *
     * @param  AbstractRequest $request
     * @return Client
     */
    public function setRequest(AbstractRequest $request): Client
    {
        parent::setRequest($request);
        $this->syncRequestFromOptions();
        return $this;
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
     *  - 'no_content_length'
     *  - 'raw_data'
     *  - 'force_custom_method' (Curl only - forces CURLOPT_CUSTOMREQUEST)
     *
     * @param  array $options
     * @return Client
     */
    public function setOptions(array $options): Client
    {
        $this->options = $options;
        $this->syncRequestFromOptions();

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
        $this->syncRequestFromOptions();

        return $this;
    }

    /**
     * Push request-shaped options ('type', 'headers', 'query', 'data', 'files') onto the
     * client's request, if one exists. Options are configuration that seeds the request the
     * moment both are available - they are not a second, parallel data store - so this is the
     * one place that seeding happens, called any time the request or the options change.
     *
     * 'query' and 'data' are merged onto the request key-by-key (addQuery()/addData()), not
     * replaced wholesale (setQuery()/setData()) - this method can run again later (e.g. from a
     * later addOption() call for an unrelated key), and a wholesale replace would silently wipe
     * out any data/query values added directly in between via addData()/addQuery() or a prior
     * sync. 'headers' and 'files' are naturally merge-safe already (addHeaders()/addData() key
     * on name), and 'type' is guarded to apply only once (first request type wins).
     *
     * @return void
     */
    protected function syncRequestFromOptions(): void
    {
        if (!$this->hasRequest()) {
            return;
        }

        if ($this->hasOption('method')) {
            $this->request->setMethod($this->options['method']);
        }

        if ($this->hasOption('type') && !$this->request->hasRequestType()) {
            $this->setType($this->options['type']);
        }

        if ($this->hasOption('headers') && is_array($this->options['headers'])) {
            $this->addHeaders($this->options['headers']);
        }

        if ($this->hasOption('query') && is_array($this->options['query'])) {
            foreach ($this->options['query'] as $name => $value) {
                $this->addQuery($name, $value);
            }
        }

        if ($this->hasOption('data') && is_array($this->options['data'])) {
            foreach ($this->options['data'] as $name => $value) {
                $this->addData($name, $value);
            }
        }

        if ($this->hasOption('files')) {
            foreach ($this->options['files'] as $i => $file) {
                $name = is_numeric($i) ? 'file' . ($i + 1) : $i;
                $this->addData($name, [
                    'filename'    => $file,
                    'contentType' => Client\Data::getMimeTypeFromFilename($file)
                ]);
            }
        }
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
     * Add a middleware to the pipeline. A plain callable is wrapped in
     * CallableMiddleware automatically. Registration order is wrap order: the
     * first-registered middleware is outermost.
     *
     * @param  MiddlewareInterface|callable $middleware
     * @return Client
     */
    public function addMiddleware(MiddlewareInterface|callable $middleware): Client
    {
        $this->middleware[] = ($middleware instanceof MiddlewareInterface)
            ? $middleware : new CallableMiddleware($middleware);

        return $this;
    }

    /**
     * Get the registered middleware, in registration order
     *
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Has any middleware registered
     *
     * @return bool
     */
    public function hasMiddleware(): bool
    {
        return !empty($this->middleware);
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
        $this->request()->setHeaders($headers);
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
        $this->request()->addHeaders($headers);
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
        $this->request()->addHeader($header, $value);
        return $this;
    }

    /**
     * Get headers
     *
     * @return mixed
     */
    public function getHeaders(): mixed
    {
        return ($this->hasRequest() && $this->request->hasHeaders()) ? $this->request->getHeaderObjects() : null;
    }

    /**
     * Get header
     *
     * @param  string $name
     * @return mixed
     */
    public function getHeader(string $name): mixed
    {
        return ($this->hasRequest() && $this->request->hasHeader($name)) ? $this->request->getHeaderObject($name) : null;
    }

    /**
     * Has headers
     *
     * @return bool
     */
    public function hasHeaders(): bool
    {
        return $this->hasRequest() && $this->request->hasHeaders();
    }

    /**
     * Has header
     *
     * @param  string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return $this->hasRequest() && $this->request->hasHeader($name);
    }

    /**
     * Remove header
     *
     * @param  string $name
     * @return Client
     */
    public function removeHeader(string $name): Client
    {
        if ($this->hasHeader($name)) {
            $this->request->removeHeader($name);
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
        if ($this->hasHeaders()) {
            $this->request->removeHeaders();
        }
        return $this;
    }

    /**
     * Get the client's request, materializing an empty one if none exists yet -
     * every data-touching setter goes through this, so there is exactly one
     * place a Client\Request gets created on demand instead of the old
     * options-array-vs-request dual bookkeeping.
     *
     * @return Client\Request
     */
    protected function request(): Client\Request
    {
        if (!$this->hasRequest()) {
            $this->request = new Request();
        }
        return $this->request;
    }

    /**
     * Set data
     *
     * @param  array $data
     * @return Client
     */
    public function setData(array $data): Client
    {
        $this->request()->setData($data);
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
        $this->request()->addData($name, $value);
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
        return $this->hasRequest() ? $this->request->getData()?->getData($key) : null;
    }

    /**
     * Has data
     *
     * @param  ?string $key
     * @return bool
     */
    public function hasData(?string $key = null): bool
    {
        return $this->hasRequest() && $this->request->hasData() && $this->request->getData()->hasData($key);
    }

    /**
     * Remove data
     *
     * @param  string $key
     * @return Client
     */
    public function removeData(string $key): Client
    {
        if ($this->hasData($key)) {
            $this->request->removeData($key);
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
        if ($this->hasRequest() && $this->request->hasData()) {
            $this->request->removeAllData();
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
        $this->request()->setQuery($query);
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
        $this->request()->addQuery($name, $value);
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
        return $this->hasRequest() ? $this->request->getQuery()?->getData($key) : null;
    }

    /**
     * Has query
     *
     * @param  ?string $key
     * @return bool
     */
    public function hasQuery(?string $key = null): bool
    {
        return $this->hasRequest() && $this->request->hasQuery() && $this->request->getQuery()->hasData($key);
    }

    /**
     * Remove query
     *
     * @param  string $key
     * @return Client
     */
    public function removeQuery(string $key): Client
    {
        if ($this->hasQuery($key)) {
            $this->request->removeQuery($key);
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
        if ($this->hasRequest() && $this->request->hasQuery()) {
            $this->request->removeAllQuery();
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
        $this->request()->setRequestType($type, $addHeader);
        return $this;
    }

    /**
     * Get type
     *
     * @return mixed
     */
    public function getType(): mixed
    {
        return $this->hasRequest() ? $this->request->getRequestType() : null;
    }

    /**
     * Has type
     *
     * @return bool
     */
    public function hasType(): bool
    {
        return $this->hasRequest() && $this->request->hasRequestType();
    }

    /**
     * Remove type
     *
     * @return Client
     */
    public function removeType(): Client
    {
        if ($this->hasType()) {
            $this->request->removeRequestType();
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

        // Replace semantics, consistent with setData()/setHeaders()/setQuery() - a set*() call
        // supersedes what was there before; add*() is the accumulating counterpart.
        $this->removeFiles();

        foreach ($files as $i => $file) {
            $this->addFile($file, is_numeric($i) ? null : $i);
        }

        if ($multipart) {
            $this->setType(Client\Request::MULTIPART);
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

        if ($name === null) {
            $i = 1;
            do {
                $name = 'file' . $i;
                $i++;
            } while ($this->hasFile($name));
        }

        $this->addData($name, ['filename' => $file, 'contentType' => Client\Data::getMimeTypeFromFilename($file)]);

        return $this;
    }

    /**
     * Get files
     *
     * @return array|null
     */
    public function getFiles(): array|null
    {
        if (!$this->hasRequest() || !$this->request->hasData()) {
            return null;
        }

        $files = [];
        foreach ($this->request->getData()->getData() as $name => $value) {
            if (is_array($value) && isset($value['filename'])) {
                $files[$name] = $value['filename'];
            }
        }

        return !empty($files) ? $files : null;
    }

    /**
     * Get file
     *
     * @param  string $key
     * @return ?string
     */
    public function getFile(string $key): ?string
    {
        return $this->getFiles()[$key] ?? null;
    }

    /**
     * Has files
     *
     * @return bool
     */
    public function hasFiles(): bool
    {
        return !empty($this->getFiles());
    }

    /**
     * Has file
     *
     * @param  string $key
     * @return bool
     */
    public function hasFile(string $key): bool
    {
        return array_key_exists($key, $this->getFiles() ?? []);
    }

    /**
     * Remove file
     *
     * @param  string $key
     * @return Client
     */
    public function removeFile(string $key): Client
    {
        if ($this->hasFile($key)) {
            $this->removeData($key);
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
        foreach (array_keys($this->getFiles() ?? []) as $key) {
            $this->removeData($key);
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
    public function prepare(?string $uri = null, ?string $method = null): Client
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

            // A request has just been materialized for the first time, so seed it from any
            // request-shaped options. syncRequestFromOptions() is a no-op without a request, so
            // options set on a client that had no request yet (e.g. `new Client(new Curl())` then
            // `addOption('data', [...])`) would otherwise be silently dropped. This deliberately
            // does NOT run when a request already existed: addOption()/setOptions() sync
            // themselves at call time in that case, and re-running the sync here would merge
            // stale option data back over any explicit setData()/removeData() made since.
            $this->syncRequestFromOptions();
        }

        // An explicit method argument to prepare() outranks a 'method' option the sync just applied
        if ($method !== null) {
            $this->request->setMethod($method);
        }

        // Set request type
        if ($this->hasOption('type')) {
            $this->request->setRequestType($this->options['type']);
        }

        // Set no Content-Length header flag
        if ($this->hasOption('no_content_length')) {
            $this->request->setNoContentLength($this->options['no_content_length']);
        }

        // Set raw data flag
        if ($this->hasOption('raw_data')) {
            $this->request->setRawData($this->options['raw_data']);
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
    public function send(?string $uri = null, ?string $method = null): Response|Promise|array|string
    {
        if (isset($this->options['async']) && ($this->options['async'] === true)) {
            return $this->sendAsync();
        } else {
            return $this->dispatch($uri, $method);
        }
    }

    /**
     * Perform the actual synchronous dispatch, always - unlike send(), this does
     * NOT redirect to sendAsync() when the 'async' option is set. Promise::wait()/
     * resolve() call this (not send()) to perform the real request: since a Promise
     * wraps a Client whose 'async' option is still true for the lifetime of that
     * Client, calling send() from inside wait()/resolve() would re-enter the async
     * branch above and return a brand-new Promise instead of ever dispatching.
     *
     * @param  ?string $uri
     * @param  ?string $method
     * @throws Exception|Client\Exception|Client\Handler\Exception
     * @return Response|array|string
     */
    public function dispatch(?string $uri = null, ?string $method = null): Response|array|string
    {
        $this->prepare($uri, $method);
        $this->response = $this->processMiddleware($this->request);

        return (($this->hasOption('auto')) && ($this->options['auto']) && ($this->response instanceof Response)) ?
            $this->response->getParsedResponse() : $this->response;
    }

    /**
     * Perform the actual handler dispatch for the given request - the terminal
     * handler at the center of the middleware pipeline. If $request is a
     * Client\Request, it's re-pointed as $this->request first, so any
     * middleware-applied modifications (e.g. an injected header) are reflected
     * in what's actually sent. A foreign (non-Client\Request) PSR-7 request is
     * converted first, reusing the same conversion sendRequest() uses.
     *
     * Note: this is public (so a terminal callable passed into Pipeline::process()
     * can reach it), but it assumes prepare() has already run - it does not create
     * a handler itself, and depends on $this->handler already being set.
     *
     * @param  RequestInterface $request
     * @throws Exception|Client\Exception|Client\Handler\Exception
     * @return Response
     */
    public function dispatchRequest(RequestInterface $request): Response
    {
        if (!($request instanceof Request)) {
            $request = $this->convertToClientRequest($request);
        }

        if ($request !== $this->request) {
            parent::setRequest($request);
        }

        $this->response = (isset($this->options['force_custom_method']) && ($this->handler instanceof Curl)) ?
            $this->handler->prepare($this->request, $this->auth, (bool)$this->options['force_custom_method'])->send() :
            $this->handler->prepare($this->request, $this->auth)->send();

        return $this->response;
    }

    /**
     * Run the request through the middleware pipeline (if any), terminating in
     * dispatchRequest(). Shared by dispatch() and sendRequest() so middleware
     * behaves identically regardless of entry point.
     *
     * MiddlewareInterface::process() is typed to the broader PSR-7 ResponseInterface
     * (see that interface's docblock), but this method's return type is narrowed to
     * Client\Response, since that's the concrete type Client's internals require. If
     * a middleware short-circuits with some other ResponseInterface implementation,
     * that mismatch is caught below and re-thrown as a clear Client\Exception rather
     * than surfacing as a raw TypeError.
     *
     * @param  RequestInterface $request
     * @throws Exception|Client\Exception|Client\Handler\Exception
     * @return Response
     */
    protected function processMiddleware(RequestInterface $request): Response
    {
        if (!$this->hasMiddleware()) {
            return $this->dispatchRequest($request);
        }

        $pipeline = new Pipeline($this->middleware);
        $response = $pipeline->process($request, fn(RequestInterface $req) => $this->dispatchRequest($req));

        if (!($response instanceof Response)) {
            throw new Client\Exception(
                'Error: A middleware returned a ' . get_class($response) .
                ', but Pop\Http\Client requires middleware to return a Pop\Http\Client\Response ' .
                '(see Pop\Http\Client\Middleware\MiddlewareInterface::process()).'
            );
        }

        return $response;
    }

    /**
     * Send a PSR-7 request and return a PSR-7 response, per PSR-18
     *
     * @param  RequestInterface $request
     * @throws Exception|Client\Exception|Client\RequestException|Client\Handler\Exception
     * @return ResponseInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $clientRequest = $this->convertToClientRequest($request);

        if (!$clientRequest->hasUri()) {
            throw new Client\RequestException('Error: There is no request URI to send.', $clientRequest);
        }

        $this->setRequest($clientRequest);
        $this->prepare();

        $this->response = $this->processMiddleware($this->request);

        return $this->response;
    }

    /**
     * Convert a foreign (non-Client\Request) PSR-7 RequestInterface into a
     * Client\Request. A Client\Request passed in is returned as-is. Shared by
     * sendRequest() (the PSR-18 entry point) and dispatchRequest() (so a
     * middleware that substitutes a foreign PSR-7 request is honored rather
     * than silently dropped).
     *
     * @param  RequestInterface $request
     * @throws Client\Exception
     * @return Request
     */
    protected function convertToClientRequest(RequestInterface $request): Request
    {
        if ($request instanceof Request) {
            return $request;
        }

        try {
            $clientRequest = new Request((string)$request->getUri(), $request->getMethod());
            foreach ($request->getHeaders() as $name => $values) {
                $clientRequest->addHeader($name, array_shift($values));
                foreach ($values as $value) {
                    $clientRequest->getHeaderObject($name)->addValue($value);
                }
            }
            $body = (string)$request->getBody();
            if ($body !== '') {
                $clientRequest->setBody($body);
            }
        } catch (\Throwable $e) {
            throw new Client\Exception(
                'Error: Unable to convert the given ' . get_class($request) .
                ' PSR-7 request into a Pop\Http\Client\Request: ' . $e->getMessage(), 0, $e
            );
        }

        return $clientRequest;
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
        // Multipart data is prepared lazily - prepareData() only mints the boundary and declares
        // the Content-Type header, leaving the body to the handler's own (streaming) path - so
        // there is no buffered data content to display here. render() is a one-shot debug/
        // inspection method rather than part of the send path, so building the rendered body on
        // demand here is fine, and reusing the already-declared boundary keeps the header and
        // the displayed body in agreement.
        } else if (($this->request->hasData()) && ($this->request->isMultipart())) {
            $boundary    = null;
            $contentType = $this->request->getHeaderValueAsString('Content-Type');
            if ($contentType !== null) {
                $boundary = Parser::parseMediaType($contentType)['params']['boundary'] ?? null;
            }

            $request .= Body\Multipart::build($this->request->getData()->getData(), $boundary)->getContent();
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
            $this->request      = null;
            $this->response     = null;
            $this->handler      = null;
            $this->multiHandler = null;
            $this->auth         = null;
            $this->options      = [];
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
