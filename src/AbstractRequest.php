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
 * Abstract HTTP request class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
 */
abstract class AbstractRequest extends AbstractRequestResponse
{

    /**
     * Request URI object
     * @var ?Uri
     */
    protected ?Uri $uri = null;

    /**
     * Constructor
     *
     * Instantiate the request object
     *
     * @param  Uri|string|null $uri
     * @throws Exception
     */
    public function __construct(Uri|string|null $uri = null)
    {
        if ($uri !== null) {
            $this->setUri($uri);
        }
    }

    /**
     * Set URI
     *
     * @param  Uri|string $uri
     * @throws Exception
     * @return AbstractRequest
     */
    public function setUri(Uri|string $uri): AbstractRequest
    {
        $this->uri = (is_string($uri)) ? new Uri($uri) : $uri;
        return $this;
    }

    /**
     * Get URI
     *
     * @return Uri
     */
    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * Get URI as string
     *
     * @return string
     */
    public function getUriAsString(): string
    {
        return (string)$this->uri?->render();
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

}
