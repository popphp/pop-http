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
namespace Pop\Http\Client\Handler;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;

/**
 * HTTP client handler exception class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Exception extends \Pop\Http\Client\Exception implements NetworkExceptionInterface
{

    /**
     * The underlying curl error number, if this exception came from a curl failure
     * @var int
     */
    protected int $curlErrno = 0;

    /**
     * The request in flight when the failure occurred, if the handler had been prepared
     * @var ?RequestInterface
     */
    protected ?RequestInterface $request = null;

    /**
     * Constructor
     *
     * @param string             $message
     * @param int                $code
     * @param ?\Throwable        $previous
     * @param int                $curlErrno
     * @param ?RequestInterface  $request
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, int $curlErrno = 0, ?RequestInterface $request = null)
    {
        parent::__construct($message, $code, $previous);
        $this->curlErrno = $curlErrno;
        $this->request   = $request;
    }

    /**
     * Get the underlying curl error number
     *
     * @return int
     */
    public function getCurlErrno(): int
    {
        return $this->curlErrno;
    }

    /**
     * Get the request in flight when the failure occurred
     *
     * @throws \LogicException
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        if ($this->request === null) {
            throw new \LogicException('Error: No request is available for this exception - the handler was never prepared.');
        }

        return $this->request;
    }

}
