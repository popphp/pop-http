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

}
