<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client;

use Pop\Mime\Part\Header;

/**
 * HTTP client auth class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.1.0
 */
class Auth
{

    /**
     * Auth header name
     * @var string
     */
    protected $header = 'Authorization';

    /**
     * Auth scheme
     * @var string
     */
    protected $scheme = null;

    /**
     * Auth token
     * @var string
     */
    protected $token = null;

    /**
     * Auth username
     * @var string
     */
    protected $username = null;

    /**
     * Auth password
     * @var string
     */
    protected $password = null;

    /**
     * Auth header object
     * @var Header
     */
    protected $authHeader = null;

    /**
     * Constructor
     *
     * Instantiate the auth object
     *
     * @param  string $header
     * @param  string $scheme
     * @param  string $token
     * @param  string $username
     * @param  string $password
     */
    public function __construct($header = 'Authorization', $scheme = null, $token = null, $username = null, $password = null)
    {
        $this->setHeader($header);
        if (null !== $scheme) {
            $this->setScheme($scheme);
        }
        if (null !== $token) {
            $this->setToken($token);
        }
        if (null !== $username) {
            $this->setUsername($username);
        }
        if (null !== $password) {
            $this->setPassword($password);
        }
    }

    /**
     * Create basic auth
     *
     * @param  string $username
     * @param  string $password
     * @return Auth
     */
    public static function createBasic($username, $password)
    {
        return new static('Authorization', 'Basic', null, $username, $password);
    }

    /**
     * Create bearer auth
     *
     * @param  string $token
     * @return Auth
     */
    public static function createBearer($token)
    {
        return new static('Authorization', 'Bearer', $token);
    }

    /**
     * Create jey auth
     *
     * @param  string $token
     * @param  string $header
     * @param  string $scheme
     * @return Auth
     */
    public static function createKey($token, $header = 'Authorization', $scheme = null)
    {
        return new static($header, $scheme, $token);
    }

    /**
     * Set the header
     *
     * @param  string $header
     * @return Auth
     */
    public function setHeader($header)
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
    public function setScheme($scheme)
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
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Set the $username
     *
     * @param  string username
     * @return Auth
     */
    public function setUsername($username)
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
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get the header
     *
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Get the scheme
     *
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Get the token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get the $username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get the password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Has scheme
     *
     * @return boolean
     */
    public function hasScheme()
    {
        return (null !== $this->scheme);
    }

    /**
     * Has token
     *
     * @return boolean
     */
    public function hasToken()
    {
        return (null !== $this->token);
    }

    /**
     * Has $username
     *
     * @return boolean
     */
    public function hasUsername()
    {
        return (null !== $this->username);
    }

    /**
     * Has password
     *
     * @return boolean
     */
    public function hasPassword()
    {
        return (null !== $this->password);
    }

    /**
     * Determine if the auth is basic
     *
     * @return boolean
     */
    public function isBasic()
    {
        return (strtolower($this->scheme) == 'basic');
    }

    /**
     * Determine if the auth is bearer
     *
     * @return boolean
     */
    public function isBearer()
    {
        return (strtolower($this->scheme) == 'bearer');
    }

    /**
     * Get auth header value as an array
     *
     * @param  boolean $assoc
     * @return array
     */
    public function getAuthHeader($assoc = true)
    {
        $this->createAuthHeader();

        return ($assoc) ?
            [$this->authHeader->getName() => $this->authHeader->getValue()]:
            [$this->authHeader->getName(), $this->authHeader->getValue()];
    }

    /**
     * Get auth header value as a string
     *
     * @param  boolean $crlf
     * @return string
     */
    public function getAuthHeaderAsString($crlf = false)
    {
        $this->createAuthHeader();

        if (null === $this->authHeader) {
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
     * @return Auth
     */
    public function createAuthHeader()
    {
        if (($this->isBasic()) && (empty($this->username) || empty($this->password))) {
            throw new Exception('Error: The username and password values must be set for basic authorization');
        } else if (!($this->isBasic()) && empty($this->token)) {
            throw new Exception('Error: The token is not set');
        }

        if ($this->isBasic()) {
            $value = 'Basic ' . base64_encode($this->username . ':' . $this->password);
        } else if ($this->isBearer()) {
            $value = 'Bearer ' . $this->token;
        } else {
            $value = $this->scheme . $this->token;
        }

        $this->authHeader = new Header($this->header, $value);

        return $this;
    }

    /**
     * To string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getAuthHeaderAsString();
    }

}