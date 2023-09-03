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

use Pop\Http;
use Pop\Http\Client\Stream;
use Pop\Mime\Message;
use Pop\Mime\Part\Header;

/**
 * HTTP response parser class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    4.2.0
 */
class Parser
{

    /**
     * Encoding constants
     * @var string
     */
    const BASE64  = 'BASE64';
    const QUOTED  = 'QUOTED';
    const URL     = 'URL';
    const RAW_URL = 'RAW_URL';
    const GZIP    = 'GZIP';
    const DEFLATE = 'DEFLATE';

    /**
     * Parse headers
     *
     * @param  mixed $headers
     * @return array
     */
    public static function parseHeaders($headers)
    {
        $httpVersion   = null;
        $httpCode      = null;
        $httpMessage   = null;
        $headerObjects = [];

        $headers = (is_string($headers)) ?
            array_map('trim', explode("\n", $headers)) : (array)$headers;

        foreach ($headers as $header) {
            if (strpos($header, 'HTTP/') !== false) {
                $httpVersion = substr($header, 0, strpos($header, ' '));
                $httpVersion = substr($httpVersion, (strpos($httpVersion, '/') + 1));

                $match = [];
                preg_match('/\d\d\d/', trim($header), $match);

                if (isset($match[0])) {
                    $httpCode    = $match[0];
                    $httpMessage = trim(substr($header, strpos($header, ' ' . $httpCode . ' ') + 5));
                }
            } else if (strpos($header, ':') !== false) {
                $headerObject = Header::parse($header);
                $headerObjects[$headerObject->getName()] = $headerObject;
            }
        }

        return [
            'version' => $httpVersion,
            'code'    => $httpCode,
            'message' => $httpMessage,
            'headers' => $headerObjects
        ];
    }

    /**
     * Parse request or response data based on content type
     *
     * @param  string  $rawData
     * @param  string  $contentType
     * @param  string  $encoding
     * @param  boolean $chunked
     * @return mixed
     */
    public static function parseDataByContentType($rawData, $contentType = null, $encoding = null, $chunked = false)
    {
        $parsedResult = false;

        if (null !== $contentType) {
            $contentType = strtolower($contentType);
        }
        if (null !== $encoding) {
            $encoding = strtoupper($encoding);
        }

        // JSON data
        if ((null !== $contentType) && (strpos($contentType, 'json') !== false)) {
            $parsedResult = json_decode(self::decodeData($rawData, $encoding, $chunked), true);
        // XML data
        } else if ((null !== $contentType) && (strpos($contentType, 'xml') !== false)) {
            $rawData = self::decodeData($rawData, $encoding, $chunked);
            $matches = [];
            preg_match_all('/<!\[cdata\[(.*?)\]\]>/is', $rawData, $matches);

            foreach ($matches[0] as $match) {
                $strip = str_replace(
                    ['<![CDATA[', ']]>', '<', '>'],
                    ['', '', '&lt;', '&gt;'],
                    $match
                );
                $rawData = str_replace($match, $strip, $rawData);
            }

            $parsedResult = json_decode(json_encode((array)simplexml_load_string($rawData)), true);
        // URL-encoded form data
        } else if ((null !== $contentType) && (strpos($contentType, 'application/x-www-form-urlencoded') !== false)) {
            parse_str(self::decodeData($rawData, $encoding, $chunked), $parsedResult);
        // Multipart form data
        } else if ((null !== $contentType) && (strpos($contentType, 'multipart/form-data') !== false)) {
            $formContent  = (strpos($rawData, 'Content-Type:') === false) ?
                'Content-Type: ' . $contentType . "\r\n\r\n" . $rawData : $rawData;
            $parsedResult = Message::parseForm($formContent);
        // Fallback to just the encoding
        } else if (null !== $encoding) {
            $parsedResult = self::decodeData($rawData, $encoding, $chunked);
        }

        return $parsedResult;
    }

    /**
     * Parse a response from a URI
     *
     * @param  string $uri
     * @param  string $method
     * @param  string $mode
     * @param  array  $options
     * @param  array  $params
     * @return Http\Server\Response
     */
    public static function parseResponseFromUri($uri, $method = 'GET', $mode = 'r', array $options = [], array $params = [])
    {
        $client = new Stream($uri, $method, $mode, $options, $params);
        $client->send(false);

        return new Http\Server\Response([
            'code'    => $client->response()->getCode(),
            'headers' => $client->response()->getHeaders(),
            'body'    => $client->response()->getBody(),
            'message' => $client->response()->getMessage(),
            'version' => $client->response()->getVersion()
        ]);
    }

    /**
     * Parse a response from a full response string
     *
     * @param  string $responseString
     * @return Http\Server\Response
     */
    public static function parseResponseFromString($responseString)
    {
        $headerString  = substr($responseString, 0, strpos($responseString, "\r\n\r\n"));
        $bodyString    = substr($responseString, (strpos($responseString, "\r\n\r\n") + 4));
        $parsedHeaders = self::parseHeaders($headerString);

        // If the body content is encoded, decode the body content
        if (array_key_exists('Content-Encoding', $parsedHeaders['headers'])) {
            $encoding = strtoupper($parsedHeaders['headers']['Content-Encoding']);
            $chunked  = ($parsedHeaders['headers']['Transfer-Encoding'] == 'chunked');
            $body     = self::decodeData($bodyString, $encoding, $chunked);
        } else {
            $body     = $bodyString;
        }

        return new Http\Server\Response([
            'code'    => $parsedHeaders['code'],
            'headers' => $parsedHeaders['headers'],
            'body'    => $body,
            'message' => $parsedHeaders['message'],
            'version' => $parsedHeaders['version']
        ]);
    }

    /**
     * Encode data
     *
     * @param  string $data
     * @param  string $encoding
     * @return string
     */
    public static function encodeData($data, $encoding = null)
    {
        switch ($encoding) {
            case self::BASE64:
                $data = base64_encode($data);
                break;
            case self::QUOTED:
                $data = quoted_printable_encode($data);
                break;
            case self::URL:
                $data = urlencode($data);
                break;
            case self::RAW_URL:
                $data = rawurlencode($data);
                break;
            case self::GZIP:
                $data = gzencode($data);
                break;
            case self::DEFLATE:
                $data = gzdeflate($data);
                break;
        }

        return $data;
    }

    /**
     * Decode data
     *
     * @param  string  $data
     * @param  string  $encoding
     * @param  boolean $chunked
     * @return string
     */
    public static function decodeData($data, $encoding = null, $chunked = false)
    {
        if ($chunked) {
            $data = self::decodeChunkedData($data);
        }

        switch ($encoding) {
            case self::BASE64:
                $data = base64_decode($data);
                break;
            case self::QUOTED:
                $data = quoted_printable_decode($data);
                break;
            case self::URL:
                $data = urldecode($data);
                break;
            case self::RAW_URL:
                $data = rawurldecode($data);
                break;
            case self::GZIP:
                $data = gzinflate(substr($data, 10));
                break;
            case self::DEFLATE:
                $zLib = unpack('n', substr($data, 0, 2));
                $data = ($zLib[1] % 31 == 0) ? gzuncompress($data) : gzinflate($data);
                break;
        }

        return $data;
    }

    /**
     * Decode a chunked transfer-encoded data and return the decoded data
     *
     * @param string $data
     * @return string
     */
    public static function decodeChunkedData($data)
    {
        $decoded = '';

        while($data != '') {
            $lfPos = strpos($data, "\012");

            if ($lfPos === false) {
                $decoded .= $data;
                break;
            }

            $chunkHex = trim(substr($data, 0, $lfPos));
            $scPos    = strpos($chunkHex, ';');

            if ($scPos !== false) {
                $chunkHex = substr($chunkHex, 0, $scPos);
            }

            if ($chunkHex == '') {
                $decoded .= substr($data, 0, $lfPos);
                $data     = substr($data, $lfPos + 1);
                continue;
            }

            $chunkLength = hexdec($chunkHex);

            if ($chunkLength) {
                $decoded .= substr($data, $lfPos + 1, $chunkLength);
                $data     = substr($data, $lfPos + 2 + $chunkLength);
            } else {
                $data = '';
            }
        }

        return $decoded;
    }

}
