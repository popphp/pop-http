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

/**
 * Abstract HTTP class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
abstract class AbstractHttp implements HttpInterface
{

    /**
     * Request
     * @var ?RequestResponseInterface
     */
    protected ?RequestResponseInterface $request = null;

    /**
     * Response
     * @var ?RequestResponseInterface
     */
    protected ?RequestResponseInterface $response = null;

    /**
     * Instantiate the HTTP object
     *
     * @param  ?RequestResponseInterface $request
     * @param  ?RequestResponseInterface $response
     */
    public function __construct(?RequestResponseInterface $request = null, ?RequestResponseInterface $response = null)
    {
        if ($request !== null) {
            $this->setRequest($request);
        }
        if ($response !== null) {
            $this->setResponse($response);
        }
    }

    /**
     * Set the request
     *
     * @param  RequestResponseInterface $request
     * @return AbstractHttp
     */
    public function setRequest(RequestResponseInterface $request): AbstractHttp
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the response
     *
     * @param  RequestResponseInterface $response
     * @return AbstractHttp
     */
    public function setResponse(RequestResponseInterface $response): AbstractHttp
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get the request
     *
     * @return RequestResponseInterface
     */
    public function getRequest(): RequestResponseInterface
    {
        return $this->request;
    }

    /**
     * Get the response
     *
     * @return RequestResponseInterface
     */
    public function getResponse(): RequestResponseInterface
    {
        return $this->response;
    }

    /**
     * Has request
     *
     * @return bool
     */
    public function hasRequest(): bool
    {
        return ($this->request !== null);
    }

    /**
     * Get the response
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return ($this->response !== null);
    }

    /**
     * Determine if the response is complete
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return (($this->response !== null) && ($this->response->hasCode()));
    }

    /**
     * Determine if the response is a success
     *
     * @return bool|null
     */
    public function isSuccess(): bool|null
    {
        $code = $this->response?->getCode();
        if (!empty($code)) {
            $type = floor($code / 100);
            return (($type == 1) || ($type == 2) || ($type == 3));
        } else {
            return null;
        }
    }

    /**
     * Determine if the response is an error
     *
     * @return bool|null
     */
    public function isError(): bool|null
    {
        $code = $this->response?->getCode();
        if (!empty($code)) {
            $type = floor($code / 100);
            return (($type == 4) || ($type == 5));
        } else {
            return null;
        }
    }

    /**
     * Determine if the response is continue
     *
     * @return bool|null
     */
    public function isContinue(): bool|null
    {
        $code = $this->response?->getCode();
        if (!empty($code)) {
            $type = floor($code / 100);
            return ($type == 1);
        } else {
            return null;
        }
    }

    /**
     * Determine if the response is OK
     *
     * @return bool|null
     */
    public function isOk(): bool|null
    {
        $code = $this->response?->getCode();
        if (!empty($code)) {
            $type = floor($code / 100);
            return ($type == 2);
        } else {
            return null;
        }
    }

    /**
     * Determine if the response is a redirect
     *
     * @return bool|null
     */
    public function isRedirect(): bool|null
    {
        $code = $this->response?->getCode();
        if (!empty($code)) {
            $type = floor($code / 100);
            return ($type == 3);
        } else {
            return null;
        }
    }

    /**
     * Determine if the response is a client error
     *
     * @return bool|null
     */
    public function isClientError(): bool|null
    {
        $code = $this->response?->getCode();
        if (!empty($code)) {
            $type = floor($code / 100);
            return ($type == 4);
        } else {
            return null;
        }
    }

    /**
     * Determine if the response is a server error
     *
     * @return bool|null
     */
    public function isServerError(): bool|null
    {
        $code = $this->response?->getCode();
        if (!empty($code)) {
            $type = floor($code / 100);
            return ($type == 5);
        } else {
            return null;
        }
    }

    /**
     * Send the request/response
     */
    abstract public function send();

}
