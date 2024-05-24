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
namespace Pop\Http\Server;

use Pop\Http\Auth;
use Pop\Http\Uri;
use Pop\Http\AbstractRequest;
use Pop\Mime\Part\Body;

/**
 * HTTP server request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
class Request extends AbstractRequest
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
     * Constructor
     *
     * Instantiate the request object
     *
     * @param  Uri|string|null $uri
     * @param  mixed           $filters
     * @param  mixed           $streamToFile
     * @throws Exception|\Pop\Http\Exception
     */
    public function __construct(Uri|string|null $uri = null, mixed $filters = null, mixed $streamToFile = null)
    {
        parent::__construct($uri);

        $this->cookie = (isset($_COOKIE)) ? $_COOKIE : [];
        $this->server = (isset($_SERVER)) ? $_SERVER : [];
        $this->env    = (isset($_ENV))    ? $_ENV    : [];

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
            $this->setAuth(Auth::parse($this->getHeader('Authorization')));
        }

        $this->data = new Data(
            $this->getHeaderValue('Content-Type'), $this->getHeaderValue('Content-Encoding'), $filters, $streamToFile
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
     * Return whether or not the method is GET
     *
     * @return bool
     */
    public function isGet(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'GET'));
    }

    /**
     * Return whether or not the method is HEAD
     *
     * @return bool
     */
    public function isHead(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'HEAD'));
    }

    /**
     * Return whether or not the method is POST
     *
     * @return bool
     */
    public function isPost(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'POST'));
    }

    /**
     * Return whether or not the method is PUT
     *
     * @return bool
     */
    public function isPut(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'PUT'));
    }

    /**
     * Return whether or not the method is DELETE
     *
     * @return bool
     */
    public function isDelete(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'DELETE'));
    }

    /**
     * Return whether or not the method is TRACE
     *
     * @return bool
     */
    public function isTrace(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'TRACE'));
    }

    /**
     * Return whether or not the method is OPTIONS
     *
     * @return bool
     */
    public function isOptions(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'OPTIONS'));
    }

    /**
     * Return whether or not the method is CONNECT
     *
     * @return bool
     */
    public function isConnect(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'CONNECT'));
    }

    /**
     * Return whether or not the method is PATCH
     *
     * @return bool
     */
    public function isPatch(): bool
    {
        return (isset($this->server['REQUEST_METHOD']) && ($this->server['REQUEST_METHOD'] == 'PATCH'));
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
     * @return string|null
     */
    public function getMethod(): string|null
    {
        return $this->server['REQUEST_METHOD'] ?? null;
    }

    /**
     * Get the server port
     *
     * @return string|null
     */
    public function getPort(): string|null
    {
        return $this->server['SERVER_PORT'] ?? null;
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
     */
    public function getQueryData(?string $key = null): string|array|null
    {
        return $this->data->getQueryData($key);
    }

    /**
     * Has query data
     *
     * @return bool
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
     * @return Uri
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
