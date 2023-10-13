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

use Pop\Mime\Part\Header;

/**
 * HTTP auth header class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Auth
{

    /**
     * Auth header name
     * @var string
     */
    protected string $header = 'Authorization';

    /**
     * Auth scheme
     * @var ?string
     */
    protected ?string $scheme = null;

    /**
     * Auth token
     * @var ?string
     */
    protected ?string $token = null;

    /**
     * Auth username
     * @var ?string
     */
    protected ?string $username = null;

    /**
     * Auth password
     * @var ?string
     */
    protected ?string $password = null;

    /**
     * Digest
     * @var ?Auth\Digest
     */
    protected ?Auth\Digest $digest = null;

    /**
     * Auth header object
     * @var ?Header
     */
    protected ?Header $authHeader = null;

    /**
     * Constructor
     *
     * Instantiate the auth object
     *
     * @param string       $header
     * @param ?string      $scheme
     * @param ?string      $token
     * @param ?string      $username
     * @param ?string      $password
     * @param ?Auth\Digest $digest
     */
    public function __construct(
        string $header = 'Authorization', ?string $scheme = null, ?string $token = null,
        ?string $username = null, ?string $password = null, ?Auth\Digest $digest = null
    )
    {
        $this->setHeader($header);
        if ($scheme !== null) {
            $this->setScheme($scheme);
        }
        if ($token !== null) {
            $this->setToken($token);
        }
        if ($username !== null) {
            $this->setUsername($username);
        }
        if ($password !== null) {
            $this->setPassword($password);
        }
        if ($digest !== null) {
            $this->setDigest($digest);
        }
    }

    /**
     * Create basic auth
     *
     * @param  string $username
     * @param  string $password
     * @return Auth
     */
    public static function createBasic(string $username, string $password): Auth
    {
        return new static('Authorization', 'Basic', null, $username, $password);
    }

    /**
     * Create bearer auth
     *
     * @param  string $token
     * @return Auth
     */
    public static function createBearer(string $token): Auth
    {
        return new static('Authorization', 'Bearer', $token);
    }

    /**
     * Create key auth
     *
     * @param  string  $token
     * @param  string  $header
     * @param  ?string $scheme
     * @return Auth
     */
    public static function createKey(string $token, string $header = 'Authorization', ?string $scheme = null): Auth
    {
        return new static($header, $scheme, $token);
    }

    /**
     * Create digest auth
     *
     * @param  string $username
     * @param  string $password
     * @param  array  $digest
     * @return Auth
     */
    public static function createDigest(Auth\Digest $digest): Auth
    {
        $auth = new static('Authorization', null, null, $digest->getUsername(), $digest->getPassword());
        $auth->setDigest($digest);
        return $auth;
    }

    /**
     * Parse header
     *
     * @param  mixed   $header
     * @param  ?string $scheme
     * @return Auth
     */
    public static function parse(mixed $header, ?string $scheme = null): Auth
    {
        $auth = new static();

        if (!($header instanceof Header)) {
            $header = Header::parse($header);
        }

        $auth->setHeader($header->getName());

        if (count($header->getValues()) == 1) {
            $value = $header->getValue();
        } else {
            $value = $header->getValuesAsStrings('; ');
        }

        if (str_starts_with($value, 'Basic')) {
            $auth->setScheme('Basic');
            $creds = base64_decode(trim(substr($value, 5)));
            if (($creds !== false) && (str_contains($creds, ':'))) {
                [$username, $password] = explode(':', $creds);
                $auth->setUsername($username)
                    ->setPassword($password);
            }
        } else if (str_starts_with($value, 'Bearer')) {
            $auth->setScheme('Bearer');
            $auth->setToken(trim(substr($value, 6)));
        } else {
            if (($scheme !== null) && (str_starts_with($value, $scheme))) {
                $value = substr($value, strlen($scheme));
                $auth->setScheme($scheme);
            }
            $auth->setToken($value);
        }

        return $auth;
    }

    /**
     * Set the header
     *
     * @param  string $header
     * @return Auth
     */
    public function setHeader(string $header): Auth
    {
        $this->header = $header;
        return $this;
    }

    /**
     * Set the scheme
     *
     * @param  string $scheme
     * @return Auth
     */
    public function setScheme(string $scheme): Auth
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Set the token
     *
     * @param  string $token
     * @return Auth
     */
    public function setToken(string $token): Auth
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Set the $username
     *
     * @param  string $username
     * @return Auth
     */
    public function setUsername(string $username): Auth
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the password
     *
     * @param  string $password
     * @return Auth
     */
    public function setPassword(string $password): Auth
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set digest
     *
     * @param  Auth\Digest $digest
     * @return Auth
     */
    public function setDigest(Auth\Digest $digest): Auth
    {
        $this->digest = $digest;
        return $this;
    }

    /**
     * Get the header
     *
     * @return string
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * Get the scheme
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Get the token
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get the $username
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
     * Get digest
     *
     * @return Auth\Digest
     */
    public function getDigest(): Auth\Digest
    {
        return $this->digest;
    }

    /**
     * Has scheme
     *
     * @return bool
     */
    public function hasScheme(): bool
    {
        return ($this->scheme !== null);
    }

    /**
     * Has token
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return ($this->token !== null);
    }

    /**
     * Has $username
     *
     * @return bool
     */
    public function hasUsername(): bool
    {
        return ($this->username !== null);
    }

    /**
     * Has password
     *
     * @return bool
     */
    public function hasPassword(): bool
    {
        return ($this->password !== null);
    }

    /**
     * Has digest
     *
     * @return bool
     */
    public function hasDigest(): bool
    {
        return ($this->digest !== null);
    }

    /**
     * Has auth header
     *
     * @return bool
     */
    public function hasAuthHeader(): bool
    {
        return ($this->authHeader !== null);
    }

    /**
     * Determine if the auth is basic
     *
     * @return bool
     */
    public function isBasic(): bool
    {
        return (strtolower($this->scheme) == 'basic');
    }

    /**
     * Determine if the auth is bearer
     *
     * @return bool
     */
    public function isBearer(): bool
    {
        return (strtolower($this->scheme) == 'bearer');
    }

    /**
     * Determine if the auth is digest
     *
     * @return bool
     */
    public function isDigest(): bool
    {
        return $this->hasDigest();
    }

    /**
     * Get auth header value object
     *
     * @return Header
     */
    public function getAuthHeader(): Header
    {
        return $this->authHeader;
    }

    /**
     * Get auth header value as an array
     *
     * @param  bool $assoc
     * @throws Exception
     * @return array
     */
    public function getAuthHeaderAsArray(bool $assoc = true): array
    {
        $this->createAuthHeader();

        return ($assoc) ?
            [$this->authHeader->getName() => $this->authHeader->getValue()]:
            [$this->authHeader->getName(), $this->authHeader->getValue()];
    }

    /**
     * Get auth header value as a string
     *
     * @param  bool $crlf
     * @throws Exception
     * @return string
     */
    public function getAuthHeaderAsString(bool $crlf = false): string
    {
        $this->createAuthHeader();

        if ($this->authHeader === null) {
            throw new Exception('Error: The auth header object is not set.');
        }

        $headerValue = $this->authHeader->render();
        if ($crlf) {
            $headerValue .= "\r\n";
        }

        return $headerValue;
    }

    /**
     * Create auth header
     *
     * @throws Exception
     * @return Header
     */
    public function createAuthHeader(): Header
    {
        if (($this->isBasic()) && (($this->username === null) || ($this->password === null))) {
            throw new Exception('Error: The username and password values must be set for basic authorization');
        } else if (($this->isDigest()) && ((!$this->hasDigest()) || (!$this->digest->isValid()))) {
            if ($this->digest->hasErrors()) {
                throw new Exception(implode('\n', $this->digest->getErrors()));
            } else {
                throw new Exception('Error: The digest is either not set or is not valid.');
            }
        } else if (!($this->isBasic()) && !($this->isDigest()) && ($this->token === null)) {
            throw new Exception('Error: The token is not set');
        }

        $value = new Header\Value();

        if ($this->isBasic()) {
            $value->setScheme('Basic ');
            $value->setValue(base64_encode($this->username . ':' . $this->password));
            $value = 'Basic ' . base64_encode($this->username . ':' . $this->password);
        } else if ($this->isDigest()) {
            $this->digest->createDigestString($value);
        } else if ($this->isBearer()) {
            $value->setScheme('Bearer ');
            $value->setValue($this->token);
        } else {
            $value->setScheme($this->scheme);
            $value->setValue($this->token);
        }

        $this->authHeader = new Header($this->header, $value);
        return $this->authHeader;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAuthHeaderAsString();
    }

}