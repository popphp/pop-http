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

use Psr\Http\Message\UriInterface;

/**
 * HTTP URI class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Uri implements UriInterface
{

    /**
     * Scheme
     * @var ?string
     */
    protected ?string $scheme = null;

    /**
     * Host
     * @var ?string
     */
    protected ?string $host = null;

    /**
     * Username
     * @var ?string
     */
    protected ?string $username = null;

    /**
     * Password
     * @var ?string
     */
    protected ?string $password = null;

    /**
     * URI
     * @var ?string
     */
    protected ?string $uri = null;

    /**
     * Port
     * @var string|int|null
     */
    protected string|int|null $port = null;

    /**
     * Query
     * @var ?string
     */
    protected ?string $query = null;

    /**
     * Fragment
     * @var ?string
     */
    protected ?string $fragment = null;

    /**
     * Base path
     * @var ?string
     */
    protected ?string $basePath = null;

    /**
     * Path segments
     * @var array
     */
    protected array $segments = [];

    /**
     * Constructor
     *
     * Instantiate the URI object
     *
     * @param  ?string $uri
     * @param  ?string $basePath
     * @throws Exception
     */
    public function __construct(?string $uri = null, ?string $basePath = null)
    {
        $path = null;
        if ($uri !== null) {
            $uriInfo = parse_url($uri);

            if ($uriInfo === false) {
                throw new Exception('Error: Unable to parse the URI value.');
            }

            if (!empty($uriInfo['scheme'])) {
                $this->setScheme($uriInfo['scheme']);
            }
            if (!empty($uriInfo['host'])) {
                $this->setHost($uriInfo['host']);
            }
            if (!empty($uriInfo['user'])) {
                $this->setUsername($uriInfo['user']);
            }
            if (!empty($uriInfo['pass'])) {
                $this->setPassword($uriInfo['pass']);
            }
            if (!empty($uriInfo['port'])) {
                $this->setPort($uriInfo['port']);
            }
            if (!empty($uriInfo['query'])) {
                $this->setQuery($uriInfo['query']);
            }
            if (!empty($uriInfo['fragment'])) {
                $this->setFragment($uriInfo['fragment']);
            }
            if (!empty($uriInfo['path'])) {
                $path = $uriInfo['path'];
            }
        }

        $this->setUri($path, $basePath);
    }

    /**
     * Create URI object
     *
     * @param  ?string $uri
     * @param  ?string $basePath
     * @throws Exception
     * @return Uri
     */
    public static function create(?string $uri = null, ?string $basePath = null): Uri
    {
        return new self($uri, $basePath);
    }

    /**
     * Get the base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the scheme
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme ?? '';
    }

    /**
     * Get the host
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host ?? '';
    }

    /**
     * Get the host with the port
     *
     * @return string
     */
    public function getFullHost(): string
    {
        $host = $this->host;
        if ($this->hasPort()) {
            $host .= ':' . $this->port;
        }

        return $host;
    }

    /**
     * Get the username
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Get the URI authority (userinfo@host:port)
     *
     * @return string
     */
    public function getAuthority(): string
    {
        if (!$this->hasHost()) {
            return '';
        }

        $authority = $this->getHost();
        $userInfo  = $this->getUserInfo();

        if ($userInfo !== '') {
            $authority = $userInfo . '@' . $authority;
        }

        $port = $this->getPort();
        if ($port !== null) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    /**
     * Get the URI user info (user[:password])
     *
     * @return string
     */
    public function getUserInfo(): string
    {
        if (!$this->hasUsername()) {
            return '';
        }

        $userInfo = $this->username;

        if ($this->hasPassword()) {
            $userInfo .= ':' . $this->password;
        }

        return $userInfo;
    }

    /**
     * Get the URI path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->uri ?? '';
    }

    /**
     * Get the port
     *
     * @return ?int
     */
    public function getPort(): ?int
    {
        if ($this->port === null) {
            return null;
        }

        $port          = (int)$this->port;
        $standardPorts = ['http' => 80, 'https' => 443];

        if (isset($standardPorts[$this->scheme]) && ($port === $standardPorts[$this->scheme])) {
            return null;
        }

        return $port;
    }

    /**
     * Get the query
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query ?? '';
    }

    /**
     * Get the query
     *
     * @return array
     */
    public function getQueryAsArray(): array
    {
        $result = [];

        if ($this->query !== null) {
            parse_str($this->query, $result);
        }

        return $result;
    }

    /**
     * Get the fragment
     *
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment ?? '';
    }

    /**
     * Get the URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the full URI, including base path
     *
     * @return string
     */
    public function getFullUri(): string
    {
        return $this->basePath . $this->uri;
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
        return $this->segments[(int)$i] ?? null;
    }

    /**
     * Get all path segments
     *
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    /**
     * Has a base path
     *
     * @return bool
     */
    public function hasBasePath(): bool
    {
        return ($this->basePath !== null);
    }

    /**
     * Has a scheme
     *
     * @return bool
     */
    public function hasScheme(): bool
    {
        return ($this->scheme !== null);
    }

    /**
     * Has a host
     *
     * @return bool
     */
    public function hasHost(): bool
    {
        return ($this->host !== null);
    }

    /**
     * Has a username
     *
     * @return bool
     */
    public function hasUsername(): bool
    {
        return ($this->username !== null);
    }

    /**
     * Has a password
     *
     * @return bool
     */
    public function hasPassword(): bool
    {
        return ($this->password !== null);
    }

    /**
     * Has a uri
     *
     * @return bool
     */
    public function hasUri(): bool
    {
        return ($this->uri !== null);
    }

    /**
     * Has a port
     *
     * @return bool
     */
    public function hasPort(): bool
    {
        return ($this->port !== null);
    }

    /**
     * Has a query
     *
     * @return bool
     */
    public function hasQuery(): bool
    {
        return ($this->query !== null);
    }

    /**
     * Has a fragment
     *
     * @return bool
     */
    public function hasFragment(): bool
    {
        return ($this->fragment !== null);
    }

    /**
     * Has segments
     *
     * @return bool
     */
    public function hasSegments(): bool
    {
        return !empty($this->segments);
    }

    /**
     * Has segment
     *
     * @return bool
     */
    public function hasSegment($i): bool
    {
        return isset($this->segments[$i]);
    }

    /**
     * Set the base path
     *
     * @param  ?string $path
     * @return Uri
     */
    public function setBasePath(?string $path = null): Uri
    {
        $this->basePath = $path;
        return $this;
    }

    /**
     * Set the scheme
     *
     * @param  string $scheme
     * @return Uri
     */
    public function setScheme(string $scheme): Uri
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Set the host
     *
     * @param  string $host
     * @return Uri
     */
    public function setHost(string $host): Uri
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Set the username
     *
     * @param  string $username
     * @return Uri
     */
    public function setUsername(string $username): Uri
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the password
     *
     * @param  string $password
     * @return Uri
     */
    public function setPassword(string $password): Uri
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set the port
     *
     * @param  string|int $port
     * @return Uri
     */
    public function setPort(string|int $port): Uri
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Set the query
     *
     * @param  string|array $query
     * @return Uri
     */
    public function setQuery(string|array $query): Uri
    {
        if (is_array($query)) {
            $query = http_build_query($query);
        }
        $this->query = $query;
        return $this;
    }

    /**
     * Set the fragment
     *
     * @param  string $fragment
     * @return Uri
     */
    public function setFragment(string $fragment): Uri
    {
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * Return an instance with the specified scheme
     *
     * @param  string $scheme
     * @return static
     */
    public function withScheme(string $scheme): static
    {
        $clone         = clone $this;
        $clone->scheme = ($scheme !== '') ? $scheme : null;
        return $clone;
    }

    /**
     * Return an instance with the specified user info
     *
     * @param  string  $user
     * @param  ?string $password
     * @return static
     */
    public function withUserInfo(string $user, ?string $password = null): static
    {
        $clone           = clone $this;
        $clone->username = ($user !== '') ? $user : null;
        $clone->password = $password;
        return $clone;
    }

    /**
     * Return an instance with the specified host
     *
     * @param  string $host
     * @return static
     */
    public function withHost(string $host): static
    {
        $clone       = clone $this;
        $clone->host = ($host !== '') ? $host : null;
        return $clone;
    }

    /**
     * Return an instance with the specified port
     *
     * @param  ?int $port
     * @return static
     */
    public function withPort(?int $port): static
    {
        $clone       = clone $this;
        $clone->port = $port;
        return $clone;
    }

    /**
     * Return an instance with the specified path
     *
     * @param  string $path
     * @return static
     */
    public function withPath(string $path): static
    {
        $clone      = clone $this;
        $clone->uri = $path;
        return $clone;
    }

    /**
     * Return an instance with the specified query string
     *
     * @param  string $query
     * @return static
     */
    public function withQuery(string $query): static
    {
        $clone        = clone $this;
        $clone->query = ($query !== '') ? $query : null;
        return $clone;
    }

    /**
     * Return an instance with the specified fragment
     *
     * @param  string $fragment
     * @return static
     */
    public function withFragment(string $fragment): static
    {
        $clone           = clone $this;
        $clone->fragment = ($fragment !== '') ? $fragment : null;
        return $clone;
    }

    /**
     * Set the URI
     *
     * @param  ?string $uri
     * @param  ?string $basePath
     * @return Uri
     */
    public function setUri(?string $uri = null, ?string $basePath = null): Uri
    {
        $isServerRequest = false;
        if (($uri === null) && isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            $isServerRequest = true;
        }

        if (!empty($basePath)) {
            if (substr($uri, 0, (strlen($basePath) + 1)) == $basePath . '/') {
                $uri = substr($uri, (strpos($uri, $basePath) + strlen($basePath)));
            } else if (substr($uri, 0, (strlen($basePath) + 1)) == $basePath . '?') {
                $uri = '/' . substr($uri, (strpos($uri, $basePath) + strlen($basePath)));
            }
        }

        if (($uri == '') || ($uri == $basePath)) {
            $uri = '/';
        }

        // Some slash clean up
        $this->uri = $uri;

        if ($isServerRequest) {
            $docRoot = (isset($_SERVER['DOCUMENT_ROOT'])) ? str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']) : null;
            $dir     = str_replace('\\', '/', getcwd());

            if (($dir != $docRoot) && (strlen($dir) > strlen($docRoot))) {
                $realBasePath = str_replace($docRoot, '', $dir);
                if (str_starts_with($uri, $realBasePath)) {
                    $this->uri = substr($uri, strlen($realBasePath));
                }
            }

            $this->setBasePath((($basePath === null) ? str_replace($docRoot, '', $dir) : $basePath));
        } else {
            $this->setBasePath($basePath);
        }

        // Get fragment
        if (str_contains($this->uri, '#')) {
            $this->fragment = substr($this->uri, (strpos($this->uri, '#') + 1));
            $this->uri      = substr($this->uri, 0, strpos($this->uri, '#'));
        }

        // Get query
        if (str_contains($this->uri, '?')) {
            $this->query = substr($this->uri, (strpos($this->uri, '?') + 1));
            $this->uri   = substr($this->uri, 0, strpos($this->uri, '?'));
        }

        // Get segments
        if (($this->uri != '/') && (str_contains($this->uri, '/'))) {
            $uri = (str_starts_with($this->uri, '/')) ? substr($this->uri, 1) : $this->uri;
            $this->segments = explode('/', $uri);
        }

        return $this;
    }

    /**
     * Render the URI
     *
     * @return string
     */
    public function render(): string
    {
        $uri = '';

        if ($this->hasScheme()) {
            $uri .= $this->getScheme() . '://';
        }
        if (($this->hasUsername()) && ($this->hasPassword())) {
            $uri .= $this->getUsername() . ':' . $this->getPassword() . '@';
        }
        if ($this->hasHost()) {
            $uri .= $this->getHost();
        }
        if ($this->hasPort()) {
            $uri .= ':' . $this->getPort();
        }

        $uri .= $this->getFullUri();

        if ($this->hasQuery()) {
            $uri .= '?' . $this->getQuery();
        }
        if ($this->hasFragment()) {
            $uri .= '#' . $this->getFragment();
        }

        return $uri;
    }

    /**
     * Render the URI
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
