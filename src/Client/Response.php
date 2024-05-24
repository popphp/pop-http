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
use Pop\Utils\Collection;

/**
 * HTTP client response class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
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
        $parsedResponse     = null;
        $contentTypeHeaders = ['Content-Type', 'Content-type', 'content-type'];

        if ($this->hasBody()) {
            $rawResponse     = $this->getBody()->getContent();
            $contentEncoding = ($this->hasHeader('Content-Encoding') && (count($this->getHeader('Content-Encoding')->getValues()) == 1)) ?
                $this->getHeader('Content-Encoding')->getValue(0) : null;

            foreach ($contentTypeHeaders as $contentTypeHeader) {
                if ($this->hasHeader($contentTypeHeader) && (count($this->getHeader($contentTypeHeader)->getValues()) == 1)) {
                    $contentType    = $this->getHeader($contentTypeHeader)->getValue(0);
                    $parsedResponse = Parser::parseDataByContentType($rawResponse, $contentType, $contentEncoding);
                    if ($parsedResponse != $rawResponse) {
                        break;
                    }
                }
            }
        }

        return $parsedResponse;
    }

    /**
     * Attempt to create a collection object from the response.
     * Attempts a JSON decode on any content string that returns unparsed.
     *
     * @param  bool $forceJson
     * @return Collection
     */
    public function collect(bool $forceJson = true): Collection
    {
        $data = ($forceJson) ? $this->json() : $this->getParsedResponse();

        // Fall back to empty array on fail
        if (!is_array($data)) {
            $data = [];
        }

        return new Collection($data);
    }

    /**
     * Attempts to JSON-decode any content string that returns unparsed.
     *
     * @return array
     */
    public function json(): array
    {
        $content = $this->getParsedResponse();

        if (is_string($content)) {
            $json = @json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $content = $json;
            }
        }

        return (is_array($content)) ? $content : [];
    }

}
