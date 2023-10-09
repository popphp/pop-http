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
namespace Pop\Http\Client;

use Pop\Http\AbstractResponse;
use Pop\Http\Parser;

/**
 * HTTP client response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Response extends AbstractResponse
{

    /**
     * Get the parsed response
     *
     * @return mixed
     */
    public function getParsedResponse(): mixed
    {
        $parsedResponse = null;

        if (($this->hasBody()) && ($this->hasHeader('Content-Type')) && (count($this->getHeader('Content-Type')->getValues()) == 1)) {
            $rawResponse     = $this->getBody()->getContent();
            $contentType     = $this->getHeader('Content-Type')->getValue(0);
            $contentEncoding = ($this->hasHeader('Content-Encoding') && (count($this->getHeader('Content-Encoding')->getValues()) == 1)) ?
                $this->getHeader('Content-Encoding')->getValue(0) : null;
            $parsedResponse  = Parser::parseDataByContentType($rawResponse, $contentType, $contentEncoding);
        }

        return $parsedResponse;
    }

}