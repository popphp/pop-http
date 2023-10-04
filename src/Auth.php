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
namespace Pop\Http;

use Pop\Mime\Part\Header;

/**
 * HTTP auth class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.2.0
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
     * Create key auth
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
     * Parse header
     *
     * @param  mixed  $header
     * @param  string $scheme
     * @return Auth
     */
    public static function parse($header, $scheme = null)
    {
        $auth = new static();

        if (!($header instanceof Header)) {
            $header = Header::parse($header);
        }

        $auth->setHeader($header->getName());

        if (count($header->getValues()) == 1) {
            $value = $header->getValue(0);
        } else {
            $value = $header->getValuesAsStrings('; ');
        }

        if (substr($value, 0, 5) == 'Basic') {
            $auth->setScheme('Basic');
            $creds = base64_decode(trim(substr($value, 5)));
            if (($creds !== false) && (strpos($creds, ':') !== false)) {
                [$username, $password] = explode(':', $creds);
                $auth->setUsername($username)
                    ->setPassword($password);
            }
        } else if (substr($value, 0, 6) == 'Bearer') {
            $auth->setScheme('Bearer');
            $auth->setToken(trim(substr($value, 6)));
        } else {
            if ((null !== $scheme) && (substr($value, 0, strlen($scheme)) == $scheme)) {
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
     * @param  string $username
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
     * Has auth header
     *
     * @return boolean
     */
    public function hasAuthHeader()
    {
        return (null !== $this->authHeader);
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
     * Get auth header value object
     *
     * @return Header
     */
    public function getAuthHeader()
    {
        return $this->authHeader;
    }

    /**
     * Get auth header value as an array
     *
     * @param  boolean $assoc
     * @return array
     */
    public function getAuthHeaderAsArray($assoc = true)
    {
        $this->createAuthHeader();

        return ($assoc) ?
            [$this->authHeader->getName() => $this->authHeader->getValue(0)]:
            [$this->authHeader->getName(), $this->authHeader->getValue(0)];
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
     * @return Header
     */
    public function createAuthHeader()
    {
        if (($this->isBasic()) && ((null === $this->username) || (null ===  $this->password))) {
            throw new Exception('Error: The username and password values must be set for basic authorization');
        } else if (!($this->isBasic()) && (null === $this->token)) {
            throw new Exception('Error: The token is not set');
        }

        $value = new Header\Value();

        if ($this->isBasic()) {
            $value->setScheme('Basic ');
            $value->setValue(base64_encode($this->username . ':' . $this->password));
            $value = 'Basic ' . base64_encode($this->username . ':' . $this->password);
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
    public function __toString()
    {
        return $this->getAuthHeaderAsString();
    }

}