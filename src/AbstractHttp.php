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
namespace Pop\Http;

/**
 * Abstract HTTP class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2025 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    5.3.2
 */
abstract class AbstractHttp implements HttpInterface
{

    /**
     * Request
     * @var ?AbstractRequest
     */
    protected ?AbstractRequest $request = null;

    /**
     * Response
     * @var ?AbstractResponse
     */
    protected ?AbstractResponse $response = null;

    /**
     * Instantiate the HTTP object
     *
     * @param  ?AbstractRequest $request
     * @param  ?AbstractResponse $response
     */
    public function __construct(?AbstractRequest $request = null, ?AbstractResponse $response = null)
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
     * @param  AbstractRequest $request
     * @return AbstractHttp
     */
    public function setRequest(AbstractRequest $request): AbstractHttp
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Set the response
     *
     * @param  AbstractResponse $response
     * @return AbstractHttp
     */
    public function setResponse(AbstractResponse $response): AbstractHttp
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Get the request
     *
     * @return AbstractRequest
     */
    public function getRequest(): AbstractRequest
    {
        return $this->request;
    }

    /**
     * Get the response
     *
     * @return AbstractResponse
     */
    public function getResponse(): AbstractResponse
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
        return $this->response?->isSuccess();
    }

    /**
     * Determine if the response is an error
     *
     * @return bool|null
     */
    public function isError(): bool|null
    {
        return $this->response?->isError();
    }

    /**
     * Determine if the response is a continue
     *
     * @return bool|null
     */
    public function isContinue(): bool|null
    {
        return $this->response?->isContinue();
    }

    /**
     * Determine if the response is 200 OK
     *
     * @return bool|null
     */
    public function isOk(): bool|null
    {
        return $this->response?->isOk();
    }

    /**
     * Determine if the response is 201 created
     *
     * @return bool|null
     */
    public function isCreated(): bool|null
    {
        return $this->response?->isCreated();
    }

    /**
     * Determine if the response is 202 accepted
     *
     * @return bool|null
     */
    public function isAccepted():bool|null
    {
        return $this->response?->isAccepted();
    }

    /**
     * Determine if the response is 204 No Content
     *
     * @return bool|null
     */
    public function isNoContent():bool|null
    {
        return $this->response?->isNoContent();
    }

    /**
     * Determine if the response is a redirect
     *
     * @return bool|null
     */
    public function isRedirect(): bool|null
    {
        return $this->response?->isRedirect();
    }

    /**
     * Determine if the response is a 301 Moved Permanently
     *
     * @return bool|null
     */
    public function isMovedPermanently(): bool|null
    {
        return $this->response?->isMovedPermanently();
    }

    /**
     * Determine if the response is a 302 Found
     *
     * @return bool|null
     */
    public function isFound(): bool|null
    {
        return $this->response?->isFound();
    }

    /**
     * Determine if the response is a client error
     *
     * @return bool|null
     */
    public function isClientError(): bool|null
    {
        return $this->response?->isClientError();
    }

    /**
     * Determine if the response is a 400 Bad Request
     *
     * @return bool|null
     */
    public function isBadRequest(): bool|null
    {
        return $this->response?->isBadRequest();
    }

    /**
     * Determine if the response is a 401 Unauthorized
     *
     * @return bool|null
     */
    public function isUnauthorized(): bool|null
    {
        return $this->response?->isUnauthorized();
    }

    /**
     * Determine if the response is a 403 Forbidden
     *
     * @return bool|null
     */
    public function isForbidden(): bool|null
    {
        return $this->response?->isForbidden();
    }

    /**
     * Determine if the response is a 404 Not Found
     *
     * @return bool|null
     */
    public function isNotFound(): bool|null
    {
        return $this->response?->isNotFound();
    }

    /**
     * Determine if the response is a 405 Method Not Allowed
     *
     * @return bool|null
     */
    public function isMethodNotAllowed(): bool|null
    {
        return $this->response?->isMethodNotAllowed();
    }

    /**
     * Determine if the response is a 406 Not Acceptable
     *
     * @return bool|null
     */
    public function isNotAcceptable(): bool|null
    {
        return $this->response?->isNotAcceptable();
    }

    /**
     * Determine if the response is a 408 Request Timeout
     *
     * @return bool|null
     */
    public function isRequestTimeout(): bool|null
    {
        return $this->response?->isRequestTimeout();
    }

    /**
     * Determine if the response is a 409 Conflict
     *
     * @return bool|null
     */
    public function isConflict(): bool|null
    {
        return $this->response?->isConflict();
    }

    /**
     * Determine if the response is a 411 Length Required
     *
     * @return bool|null
     */
    public function isLengthRequired(): bool|null
    {
        return $this->response?->isLengthRequired();
    }

    /**
     * Determine if the response is a 415 Unsupported Media Type
     *
     * @return bool|null
     */
    public function isUnsupportedMediaType(): bool|null
    {
        return $this->response?->isUnsupportedMediaType();
    }

    /**
     * Determine if the response is a 422 Unprocessable Entity
     *
     * @return bool|null
     */
    public function isUnprocessableEntity(): bool|null
    {
        return $this->response?->isUnprocessableEntity();
    }

    /**
     * Determine if the response is a 429 Too Many Requests
     *
     * @return bool|null
     */
    public function isTooManyRequests(): bool|null
    {
        return $this->response?->isTooManyRequests();
    }

    /**
     * Determine if the response is a server error
     *
     * @return bool|null
     */
    public function isServerError(): bool|null
    {
        return $this->response?->isServerError();
    }

    /**
     * Determine if the response is a 500 Internal Server Error
     *
     * @return bool|null
     */
    public function isInternalServerError(): bool|null
    {
        return $this->response?->isInternalServerError();
    }

    /**
     * Determine if the response is a 502 Bad Gateway
     *
     * @return bool|null
     */
    public function isBadGateway(): bool|null
    {
        return $this->response?->isBadGateway();
    }

    /**
     * Determine if the response is a 503 Service Unavailable
     *
     * @return bool|null
     */
    public function isServiceUnavailable(): bool|null
    {
        return $this->response?->isServiceUnavailable();
    }

    /**
     * Send the request/response
     */
    abstract public function send();

    /**
     * Render the request/response to string
     */
    abstract public function render(): string;

    /**
     * Render the request/response to string
     */
    abstract public function __toString(): string;

}
