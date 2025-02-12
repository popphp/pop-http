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
namespace Pop\Http\Auth;

use Pop\Mime\Part\Header;
use Pop\Mime\Part\Header\Value;

/**
 * HTTP auth digest class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.3.2
 */
class Digest
{

    /**
     * Digest constants
     * @var string
     */
    const ALGO_MD5      = 'MD5';
    const ALGO_MD5_SESS = 'MD5-sess';
    const QOP_AUTH      = 'auth';
    const QOP_AUTH_INT  = 'auth-int';

    /**
     * WWW-Auth Header
     * @var ?Header
     */
    protected ?Header $wwwAuth = null;

    /**
     * Realm
     * @var ?string
     */
    protected ?string $realm = null;

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
     * Uri string
     * @var ?string
     */
    protected ?string $uri = null;

    /**
     * Nonce
     * @var ?string
     */
    protected ?string $nonce = null;

    /**
     * Nonce count
     * @var ?string
     */
    protected ?string $nonceCount = null;

    /**
     * Client nonce
     * @var ?string
     */
    protected ?string $clientNonce = null;

    /**
     * Method
     * @var string
     */
    protected string $method = 'GET';

    /**
     * Algorithm
     * @var string
     */
    protected string $algorithm = self::ALGO_MD5;

    /**
     * QOP
     * @var ?string
     */
    protected ?string $qop = null;

    /**
     * Opaque
     * @var ?string
     */
    protected ?string $opaque = null;

    /**
     * Body
     * @var ?string
     */
    protected ?string $body = null;

    /**
     * Stale flag
     * @var bool
     */
    protected bool $stale = false;

    /**
     * Validation errors
     * @var array
     */
    protected array $errors = [];

    /**
     * Constructor
     *
     * Instantiate the auth digest object
     *
     * @param  string $realm
     * @param  string $username
     * @param  string $password
     * @param  string $uri
     * @param  string $nonce
     */
    public function __construct(string $realm, string $username, string $password, string $uri, string $nonce)
    {
        $this->setRealm($realm);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setUri($uri);
        $this->setNonce($nonce);
    }

    /**
     * Create digest
     *
     * @param  string $realm
     * @param  string $username
     * @param  string $password
     * @param  string $uri
     * @param  string $nonce
     * @return Digest
     */
    public static function create(
        string $realm, string $username, string $password, string $uri, string $nonce
    ): Digest
    {
        return new static($realm, $username, $password, $uri, $nonce);
    }

    /**
     * Create digest from client header
     *
     * @param  string|Header $header
     * @return Digest
     */
    public static function createFromHeader(string|Header $header, $password)
    {
        if (is_string($header)) {
            $header = Header::parse($header);
            if (($header->getValue()->getScheme() === null) || (trim($header->getValue()->getScheme()) != 'Digest')) {
                throw new Exception('Error: The auth header is not digest.');
            }
        }

        $params = $header->getValue()->getParameters();

        $realm    = $params['realm'] ?? null;
        $nonce    = $params['nonce'] ?? null;
        $uri      = $params['uri'] ?? null;
        $username = $params['username'] ?? null;

        if ($realm === null) {
            throw new Exception('Error: The realm is not set.');
        }
        if ($username === null) {
            throw new Exception('Error: The username is not set.');
        }
        if ($nonce === null) {
            throw new Exception('Error: The nonce is not set.');
        }
        if ($uri === null) {
            throw new Exception('Error: The URI is not set.');
        }

        return new static($realm, $username, $password, $uri, $nonce);
    }

    /**
     * Create digest from WWW-auth server header
     *
     * @param  string|Header $wwwAuth
     * @param  string $username
     * @param  string $password
     * @param  string $uri
     * @throws Exception
     * @return Digest
     */
    public static function createFromWwwAuth(
        string|Header $wwwAuth, string $username, string $password, string $uri
    ): Digest
    {
        if (is_string($wwwAuth)) {
            $wwwAuth = Header::parse($wwwAuth);
            if (($wwwAuth->getValue()->getScheme() === null) || (trim($wwwAuth->getValue()->getScheme()) != 'Digest')) {
                throw new Exception('Error: The auth header is not digest.');
            }
        }

        $params = $wwwAuth->getValue()->getParameters();

        $realm  = $params['realm'] ?? null;
        $qop    = $params['qop'] ?? null;
        $nonce  = $params['nonce'] ?? null;
        $opaque = $params['opaque'] ?? null;
        $stale  = $params['stale'] ?? false;

        if ($realm === null) {
            throw new Exception('Error: The realm is not set.');
        }
        if ($nonce === null) {
            throw new Exception('Error: The nonce is not set.');
        }
        if ($opaque === null) {
            throw new Exception('Error: The opaque is not set.');
        }

        $digest = new static($realm, $username, $password, $uri, $nonce);
        $digest->setWwwAuth($wwwAuth)
            ->setOpaque($opaque);

        if ($qop == 'auth-int') {
            $digest->setQop(static::QOP_AUTH_INT);
        } else if (!str_contains($qop, 'auth-int') && str_contains($qop, 'auth')) {
            $digest->setQop(static::QOP_AUTH);
        }
        if ($stale) {
            $digest->setStale($stale);
        }

        return $digest;
    }

    /**
     * Set the WWW auth header
     *
     * @param  Header $wwwAuth
     * @return Digest
     */
    public function setWwwAuth(Header $wwwAuth): Digest
    {
        $this->wwwAuth = $wwwAuth;
        return $this;
    }

    /**
     * Set the realm
     *
     * @param  string $realm
     * @return Digest
     */
    public function setRealm(string $realm): Digest
    {
        $this->realm = $realm;
        return $this;
    }

    /**
     * Set the username
     *
     * @param  string $username
     * @return Digest
     */
    public function setUsername(string $username): Digest
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set the password
     *
     * @param  string $password
     * @return Digest
     */
    public function setPassword(string $password): Digest
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Set the URI
     *
     * @param  string $uri
     * @return Digest
     */
    public function setUri(string $uri): Digest
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Set the nonce
     *
     * @param  string $nonce
     * @return Digest
     */
    public function setNonce(string $nonce): Digest
    {
        $this->nonce = $nonce;
        return $this;
    }

    /**
     * Set the nonce count
     *
     * @param  string $nonceCount
     * @return Digest
     */
    public function setNonceCount(string $nonceCount): Digest
    {
        $this->nonceCount = $nonceCount;
        return $this;
    }

    /**
     * Set the client nonce
     *
     * @param  string $clientNonce
     * @return Digest
     */
    public function setClientNonce(string $clientNonce): Digest
    {
        $this->clientNonce = $clientNonce;
        return $this;
    }

    /**
     * Set the method
     *
     * @param  string $method
     * @return Digest
     */
    public function setMethod(string $method): Digest
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Set the algorithm
     *
     * @param  string $algorithm
     * @return Digest
     */
    public function setAlgorithm(string $algorithm): Digest
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    /**
     * Set the QOP
     *
     * @param  string $qop
     * @return Digest
     */
    public function setQop(string $qop): Digest
    {
        $this->qop = $qop;
        return $this;
    }

    /**
     * Set the opaque
     *
     * @param  string $opaque
     * @return Digest
     */
    public function setOpaque(string $opaque): Digest
    {
        $this->opaque = $opaque;
        return $this;
    }

    /**
     * Set the body
     *
     * @param  string $body
     * @return Digest
     */
    public function setBody(string $body): Digest
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set stale flag
     *
     * @param  bool $stale
     * @return Digest
     */
    public function setStale(bool $stale = false): Digest
    {
        $this->stale = $stale;
        return $this;
    }

    /**
     * Get the WWW auth header
     *
     * @return Header
     */
    public function getWwwAuth(): string
    {
        return $this->wwwAuth;
    }

    /**
     * Get the realm
     *
     * @return string
     */
    public function getRealm(): string
    {
        return $this->realm;
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
     * Get the URI
     *
     * @return string
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get the nonce
     *
     * @return string
     */
    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * Get the nonce count
     *
     * @return string
     */
    public function getNonceCount(): string
    {
        return $this->nonceCount;
    }

    /**
     * Get the client nonce
     *
     * @return string
     */
    public function getClientNonce(): string
    {
        return $this->clientNonce;
    }

    /**
     * Get the method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the algorithm
     *
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * Get the QOP
     *
     * @return string
     */
    public function getQop(): string
    {
        return $this->qop;
    }

    /**
     * Get the opaque
     *
     * @return string
     */
    public function getOpaque(): string
    {
        return $this->opaque;
    }

    /**
     * Get the body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Has WWW auth header
     *
     * @return bool
     */
    public function hasWwwAuth(): bool
    {
        return ($this->wwwAuth !== null);
    }

    /**
     * Has realm
     *
     * @return bool
     */
    public function hasRealm(): bool
    {
        return ($this->realm !== null);
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
     * Has URI
     *
     * @return bool
     */
    public function hasUri(): bool
    {
        return ($this->uri !== null);
    }

    /**
     * Has nonce
     *
     * @return bool
     */
    public function hasNonce(): bool
    {
        return ($this->nonce !== null);
    }

    /**
     * Has nonce count
     *
     * @return bool
     */
    public function hasNonceCount(): bool
    {
        return ($this->nonceCount !== null);
    }

    /**
     * Has client nonce
     *
     * @return bool
     */
    public function hasClientNonce(): bool
    {
        return ($this->clientNonce !== null);
    }

    /**
     * Has method
     *
     * @return bool
     */
    public function hasMethod(): bool
    {
        return ($this->method !== null);
    }

    /**
     * Has algorithm
     *
     * @return bool
     */
    public function hasAlgorithm(): bool
    {
        return ($this->algorithm !== null);
    }

    /**
     * Has qop
     *
     * @return bool
     */
    public function hasQop(): bool
    {
        return ($this->qop !== null);
    }

    /**
     * Has opaque
     *
     * @return bool
     */
    public function hasOpaque(): bool
    {
        return ($this->opaque !== null);
    }

    /**
     * Has body
     *
     * @return bool
     */
    public function hasBody(): bool
    {
        return ($this->body !== null);
    }

    /**
     * Is stale
     *
     * @return bool
     */
    public function isStale(): bool
    {
        return $this->stale;
    }

    /**
     * Is valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $result = true;

        // Check basic required parameters
        if (($this->realm === null) || ($this->username === null) ||
            empty($this->password) || ($this->nonce === null)) {
            $this->errors[] =
                'Error: One or more of the basic parameters were not set (realm, username, password or nonce).';
            $result = false;
        }
        // Check client nonce for MD5-sess algorithm
        if (($this->algorithm == self::ALGO_MD5_SESS) && ($this->clientNonce === null)) {
            $this->errors[] = 'Error: The client nonce was not set for the MD5-sess algorithm.';
            $result = false;
        }
        // Check QOP auth-int and the entity body
        if (($this->qop == self::QOP_AUTH_INT) && ($this->body === null)) {
            $this->errors[] = 'Error: The entity body was not set for the auth-int QOP.';
            $result = false;
        }
        // Check QOP auth/auth-int nonce count and client nonce for the response
        if (!empty($this->qop) && (str_contains($this->qop, 'auth') && (($this->nonceCount === null) || ($this->clientNonce === null)))) {
            $this->errors[] = 'Error: Either the nonce count or client nonce was not set for the auth QOP.';
            $result = false;
        }

        return $result;
    }

    /**
     * Get errors
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Has errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return (!empty($this->errors));
    }

    /**
     * Create digest value
     *
     * @param  Value $value
     * @return string
     */
    public function createDigestString(Value $value = new Value()): string
    {
        $a1 = ($this->algorithm == self::ALGO_MD5_SESS) ?
            md5(
                md5($this->username . ':' . $this->realm . ':' . $this->password) .
                ':' . $this->nonce . ':' . $this->clientNonce
            ) :
            md5($this->username . ':' . $this->realm . ':' . $this->password);

        $a2 = ($this->qop == self::QOP_AUTH_INT) ?
            md5($this->method . ':' . $this->uri . ':' . md5($this->body)) :
            md5($this->method . ':' . $this->uri);

        $response = ($this->qop !== null) ?
            md5($a1 . ':' . $this->nonce . ':' .  $this->nonceCount . ':' .  $this->clientNonce . ':' . $a2) :
            md5($a1 . ':' . $this->nonce . ':' . $a2);

        $value->setDelimiter(',')
            ->setScheme('Digest ')
            ->setForceQuote(true)
            ->addParameter('username', $this->username)
            ->addParameter('realm', $this->realm)
            ->addParameter('nonce', $this->nonce)
            ->addParameter('uri', $this->uri)
            ->addParameter('response', $response);

        return $value->render();
    }

    /**
     * Render the header value string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->createDigestString();
    }

}
