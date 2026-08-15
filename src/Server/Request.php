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
namespace Pop\Http\Server;

use Pop\Http\Auth;
use Pop\Http\Uri;
use Pop\Http\AbstractRequest;
use Pop\Http\Body;
use Psr\Http\Message\ServerRequestInterface;

/**
 * HTTP server request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Request extends AbstractRequest implements ServerRequestInterface
{

    /**
     * Server request data object
     * @var ?Data
     */
    protected ?Data $data = null;

    /**
     * COOKIE array
     * @var array
     */
    protected array $cookie = [];

    /**
     * SERVER array
     * @var array
     */
    protected array $server = [];

    /**
     * ENV array
     * @var array
     */
    protected array $env = [];

    /**
     * HTTP auth object
     * @var ?Auth
     */
    protected ?Auth $auth = null;

    /**
     * Method override (from withMethod)
     * @var ?string
     */
    protected ?string $methodOverride = null;

    /**
     * Cookie params override, per PSR-7 withCookieParams() (null = derive from $this->cookie)
     * @var ?array
     */
    protected ?array $cookieParamsOverride = null;

    /**
     * Query params override, per PSR-7 withQueryParams() (null = derive from $this->data)
     * @var ?array
     */
    protected ?array $queryParamsOverride = null;

    /**
     * Uploaded files override, per PSR-7 withUploadedFiles() (null = derive from $this->data)
     * @var ?array
     */
    protected ?array $uploadedFilesOverride = null;

    /**
     * Parsed body override, per PSR-7 withParsedBody()
     * @var mixed
     */
    protected mixed $parsedBodyOverride = null;

    /**
     * Whether withParsedBody() has been called (distinguishes "not set" from "set to null")
     * @var bool
     */
    protected bool $parsedBodyOverridden = false;

    /**
     * Request attributes, per PSR-7 (middleware-style per-request key-value bag)
     * @var array
     */
    protected array $attributes = [];

    /**
     * Constructor
     *
     * Instantiate the request object
     *
     * @param  Uri|string|null $uri
     * @param  mixed           $filters
     * @param  mixed           $streamToFile
     * @param  bool            $populateFromGlobals
     * @param  array           $serverParams
     * @throws Exception|\Pop\Http\Exception
     */
    public function __construct(
        Uri|string|null $uri = null, mixed $filters = null, mixed $streamToFile = null,
        bool $populateFromGlobals = true, array $serverParams = []
    )
    {
        parent::__construct($uri);

        if ($populateFromGlobals) {
            $this->cookie = array_key_exists('_COOKIE', $GLOBALS) ? $_COOKIE : [];
            $this->server = array_key_exists('_SERVER', $GLOBALS) ? $_SERVER : [];
            $this->env    = array_key_exists('_ENV', $GLOBALS)    ? $_ENV    : [];

            // Get any possible request headers
            if (function_exists('getallheaders')) {
                $this->addHeaders(getallheaders());
            } else {
                foreach ($_SERVER as $key => $value) {
                    if (str_starts_with($key, 'HTTP_')) {
                        $key = ucfirst(strtolower(str_replace('HTTP_', '', $key)));
                        if (str_contains($key, '_')) {
                            $ary = explode('_', $key);
                            foreach ($ary as $k => $v){
                                $ary[$k] = ucfirst(strtolower($v));
                            }
                            $key = implode('-', $ary);
                        }
                        $this->addHeader($key, $value);
                    }
                }
            }

            if ($this->hasHeader('Authorization')) {
                $this->setAuth(Auth::parse($this->getHeaderObject('Authorization')));
            }
        } else {
            $this->server = $serverParams;
        }

        $contentType     = $this->getHeaderValue('Content-Type');
        $contentEncoding = $this->getHeaderValue('Content-Encoding');

        $this->data = new Data(
            ($contentType !== null) ? (string)$contentType : null,
            ($contentEncoding !== null) ? (string)$contentEncoding : null,
            $filters, $streamToFile, $populateFromGlobals
        );

        if ($this->data->hasRawData()) {
            $this->body = new Body($this->data->getRawData());
        }
    }

    /**
     * Factory to create a new request object
     *
     * @param  ?Uri  $uri
     * @param  mixed $filters
     * @param  mixed $streamToFile
     * @throws Exception|\Pop\Http\Exception
     * @return Request
     */
    public static function create(?Uri $uri = null, mixed $filters = null, mixed $streamToFile = null): Request
    {
        return new self($uri, $filters, $streamToFile);
    }

    /**
     * Factory to create a new request object with a base path reference for the request URI
     *
     * @param  string $basePath
     * @param  mixed  $filters
     * @param  mixed  $streamToFile
     * @return Request
     */
    public static function createWithBasePath(string $basePath, mixed $filters = null, mixed $streamToFile = null): Request
    {
        return new self(new Uri(null, $basePath), $filters, $streamToFile);
    }

    /**
     * Set the auth object
     *
     * @param  Auth $auth
     * @return Request
     */
    public function setAuth(Auth $auth): Request
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * Get the auth object
     *
     * @return Auth
     */
    public function getAuth(): Auth
    {
        return $this->auth;
    }

    /**
     * Has auth object
     *
     * @return bool
     */
    public function hasAuth(): bool
    {
        return ($this->auth !== null);
    }

    /**
     * Return a new request with the specified request method
     *
     * @param  string $method
     * @return static
     */
    public function withMethod(string $method): static
    {
        $clone = clone $this;
        $clone->methodOverride = $method;
        return $clone;
    }

    /**
     * Return whether or not the method is GET
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return ($this->getMethod() == 'GET');
    }

    /**
     * Return whether or not the method is HEAD
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return ($this->getMethod() == 'HEAD');
    }

    /**
     * Return whether or not the method is POST
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return ($this->getMethod() == 'POST');
    }

    /**
     * Return whether or not the method is PUT
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return ($this->getMethod() == 'PUT');
    }

    /**
     * Return whether or not the method is DELETE
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return ($this->getMethod() == 'DELETE');
    }

    /**
     * Return whether or not the method is TRACE
     *
     * @return bool
     */
    public function isTrace(): bool
    {
        return ($this->getMethod() == 'TRACE');
    }

    /**
     * Return whether or not the method is OPTIONS
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return ($this->getMethod() == 'OPTIONS');
    }

    /**
     * Return whether or not the method is CONNECT
     *
     * @return bool
     */
    public function isConnect(): bool
    {
        return ($this->getMethod() == 'CONNECT');
    }

    /**
     * Return whether or not the method is PATCH
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return ($this->getMethod() == 'PATCH');
    }

    /**
     * Return whether or not the request is secure
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return (isset($this->server['HTTPS']) || (isset($_SERVER['SERVER_PORT']) && ($_SERVER['SERVER_PORT'] == '443')));
    }

    /**
     * Get the document root
     *
     * @return string|null
     */
    public function getDocumentRoot(): string|null
    {
        return $this->server['DOCUMENT_ROOT'] ?? null;
    }

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->methodOverride ?? ($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get the server port
     *
     * @return string|null
     */
    public function getPort(): string|null
    {
        return isset($this->server['SERVER_PORT']) ? (string)$this->server['SERVER_PORT'] : null;
    }

    /**
     * Get scheme
     *
     * @return string
     */
    public function getScheme(): string
    {
        return ($this->isSecure()) ? 'https' : 'http';
    }

    /**
     * Get host (without port)
     *
     * @return string
     */
    public function getHost(): string
    {
        $hostname = null;

        if (!empty($this->server['HTTP_HOST'])) {
            $hostname = $this->server['HTTP_HOST'];
        } else if (!empty($this->server['SERVER_NAME'])) {
            $hostname = $this->server['SERVER_NAME'];
        }

        if (str_contains($hostname, ':')) {
            $hostname = substr($hostname, 0, strpos($hostname, ':'));
        }

        return $hostname;
    }

    /**
     * Get host with port
     *
     * @return string
     */
    public function getFullHost(): string
    {
        $port     = $this->getPort();
        $hostname = null;

        if (!empty($this->server['HTTP_HOST'])) {
            $hostname = $this->server['HTTP_HOST'];
        } else if (!empty($this->server['SERVER_NAME'])) {
            $hostname = $this->server['SERVER_NAME'];
        }

        if ((!str_contains($hostname, ':')) && ($port !== null)) {
            $hostname .= ':' . $port;
        }

        return $hostname;
    }

    /**
     * Get client's IP
     *
     * @param  bool $proxy
     * @return string
     */
    public function getIp(bool $proxy = true): string
    {
        $ip = null;

        if ($proxy && isset($this->server['HTTP_CLIENT_IP'])) {
            $ip = $this->server['HTTP_CLIENT_IP'];
        } else if ($proxy && isset($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ip = $this->server['HTTP_X_FORWARDED_FOR'];
        } else if (isset($this->server['REMOTE_ADDR'])) {
            $ip = $this->server['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Get a value from $_COOKIE, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getCookie(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->cookie;
        } else {
            return $this->cookie[$key] ?? null;
        }
    }

    /**
     * Get a value from $_SERVER, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getServer(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->server;
        } else {
            return $this->server[$key] ?? null;
        }
    }

    /**
     * Get a value from $_ENV, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getEnv(?string $key = null): string|array|null
    {
        if ($key === null) {
            return $this->env;
        } else {
            return $this->env[$key] ?? null;
        }
    }

    /**
     * Get the base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->uri->getBasePath();
    }

    /**
     * Get the request URI
     *
     * @return string
     */
    public function getUriString(): string
    {
        return $this->uri->getUri();
    }

    /**
     * Get the full request URI, including base path
     *
     * @return string
     */
    public function getFullUriString(): string
    {
        return $this->uri->getFullUri();
    }

    /**
     * Get a path segment, divided by the forward slash,
     * where $i refers to the array key index, i.e.,
     *    0     1     2
     * /hello/world/page
     *
     * @param  int $i
     * @return string|null
     */
    public function getSegment(int $i): string|null
    {
        return $this->uri->getSegment($i);
    }

    /**
     * Get all path segments
     *
     * @return array
     */
    public function getSegments(): array
    {
        return $this->uri->getSegments();
    }

    /**
     * Set the base path
     *
     * @param  ?string $path
     * @return Request
     */
    public function setBasePath(?string $path = null): Request
    {
        if ($this->uri !== null) {
            $this->uri->setBasePath($path);
        }
        return $this;
    }

    /**
     * Return whether or not the request has FILES
     *
     * @return bool
     */
    public function hasFiles(): bool
    {
        return $this->data->hasFiles();
    }

    /**
     * Get a value from $_GET, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getQuery(?string $key = null): string|array|null
    {
        return $this->data->getQuery($key);
    }

    /**
     * Get a value from $_POST, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getPost(?string $key = null): string|array|null
    {
        return $this->data->getPost($key);
    }

    /**
     * Get a value from $_FILES, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getFiles(?string $key = null): string|array|null
    {
        return $this->data->getFiles($key);
    }

    /**
     * Get a value from PUT query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getPut(?string $key = null): string|array|null
    {
        return $this->data->getPut($key);
    }

    /**
     * Get a value from PATCH query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getPatch(?string $key = null): string|array|null
    {
        return $this->data->getPatch($key);
    }

    /**
     * Get a value from DELETE query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getDelete(?string $key = null): string|array|null
    {
        return $this->data->getDelete($key);
    }


    /**
     * Get a value from query data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     * @deprecated This always returns null now: the QUERY_STRING re-parse that used to populate
     *             $queryData was removed. Use getQuery() instead, which reads directly from PHP's
     *             native $_GET.
     */
    public function getQueryData(?string $key = null): string|array|null
    {
        return $this->data->getQueryData($key);
    }

    /**
     * Has query data
     *
     * @return bool
     * @deprecated This always returns false now: the QUERY_STRING re-parse that used to populate
     *             $queryData was removed. Check getQuery() instead (e.g. !empty($this->getQuery())),
     *             which reads directly from PHP's native $_GET.
     */
    public function hasQueryData(): bool
    {
        return $this->data->hasQueryData();
    }

    /**
     * Get a value from parsed data, or the whole array
     *
     * @param  ?string $key
     * @return string|array|null
     */
    public function getParsedData(?string $key = null): string|array|null
    {
        return $this->data->getParsedData($key);
    }

    /**
     * Has parsed data
     *
     * @return bool
     */
    public function hasParsedData(): bool
    {
        return $this->data->hasParsedData();
    }

    /**
     * Get the raw data
     *
     * @return string|null
     */
    public function getRawData(): string|null
    {
        return $this->data->getRawData();
    }

    /**
     * Has raw data
     *
     * @return bool
     */
    public function hasRawData(): bool
    {
        return $this->data->hasRawData();
    }

    /**
     * Get data
     *
     * @return Data
     */
    public function getData(): Data
    {
        return $this->data;
    }

    /**
     * Has data
     *
     * @return bool
     */
    public function hasData(): bool
    {
        return ($this->data !== null);
    }

    /**
     * Get the server params, per PSR-7 (alias of getServer())
     *
     * @return array
     */
    public function getServerParams(): array
    {
        return $this->server;
    }

    /**
     * Get the cookie params, per PSR-7
     *
     * @return array
     */
    public function getCookieParams(): array
    {
        return $this->cookieParamsOverride ?? $this->cookie;
    }

    /**
     * Return an instance with the specified cookie params, per PSR-7
     *
     * @param  array $cookies
     * @return static
     */
    public function withCookieParams(array $cookies): static
    {
        $clone                       = clone $this;
        $clone->cookieParamsOverride = $cookies;
        return $clone;
    }

    /**
     * Get the query params, per PSR-7
     *
     * @return array
     */
    public function getQueryParams(): array
    {
        if ($this->queryParamsOverride !== null) {
            return $this->queryParamsOverride;
        }

        $query = $this->data->getQuery();
        return is_array($query) ? $query : [];
    }

    /**
     * Return an instance with the specified query params, per PSR-7
     *
     * @param  array $query
     * @return static
     */
    public function withQueryParams(array $query): static
    {
        $clone                      = clone $this;
        $clone->queryParamsOverride = $query;
        return $clone;
    }

    /**
     * Get the uploaded files, per PSR-7 - an array of UploadedFileInterface instances
     * built from the native $_FILES-shaped data, unless overridden
     *
     * @return array
     */
    public function getUploadedFiles(): array
    {
        if ($this->uploadedFilesOverride !== null) {
            return $this->uploadedFilesOverride;
        }

        return UploadedFile::createFromFilesArray($this->data->getFiles());
    }

    /**
     * Return an instance with the specified uploaded files, per PSR-7
     *
     * @param  array $uploadedFiles
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles): static
    {
        $clone                        = clone $this;
        $clone->uploadedFilesOverride = $uploadedFiles;
        return $clone;
    }

    /**
     * Get the parsed body, per PSR-7
     *
     * @return array|object|null
     */
    public function getParsedBody(): array|object|null
    {
        if ($this->parsedBodyOverridden) {
            return $this->parsedBodyOverride;
        }

        $parsed = $this->data->getParsedData();
        return (!empty($parsed)) ? $parsed : null;
    }

    /**
     * Return an instance with the specified parsed body, per PSR-7
     *
     * @param  array|object|null $data
     * @return static
     */
    public function withParsedBody($data): static
    {
        $clone                        = clone $this;
        $clone->parsedBodyOverride    = $data;
        $clone->parsedBodyOverridden  = true;
        return $clone;
    }

    /**
     * Get all request attributes, per PSR-7
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Get a request attribute, per PSR-7
     *
     * @param  string $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * Return an instance with the specified attribute set, per PSR-7
     *
     * @param  string $name
     * @param  mixed  $value
     * @return static
     */
    public function withAttribute(string $name, mixed $value): static
    {
        $clone                    = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    /**
     * Return an instance without the specified attribute, per PSR-7
     *
     * @param  string $name
     * @return static
     */
    public function withoutAttribute(string $name): static
    {
        $clone = clone $this;
        unset($clone->attributes[$name]);
        return $clone;
    }

    /**
     * Magic method to get a value from one of the server/environment variables
     *
     * @param  string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'get'     => $this->data->get,
            'post'    => $this->data->post,
            'files'   => $this->data->files,
            'put'     => $this->data->put,
            'patch'   => $this->data->patch,
            'delete'  => $this->data->delete,
            'parsed'  => $this->data->parsed,
            'raw'     => $this->data->raw,
            'cookie'  => $this->cookie,
            'server'  => $this->server,
            'env'     => $this->env,
            'headers' => $this->headers,
            default   => null,
        };
    }

}
