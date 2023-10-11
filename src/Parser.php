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

use Pop\Mime\Message;
use Pop\Mime\Part\Header;

/**
 * HTTP response parser class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
    public static function parseHeaders(mixed $headers): array
    {
        $httpVersion   = null;
        $httpCode      = null;
        $httpMessage   = null;
        $headerObjects = [];

        $headers = (is_string($headers)) ?
            array_map('trim', explode("\n", $headers)) : (array)$headers;

        foreach ($headers as $header) {
            if (str_contains($header, 'HTTP/')) {
                $httpVersion = substr($header, 0, strpos($header, ' '));
                $httpVersion = substr($httpVersion, (strpos($httpVersion, '/') + 1));

                $match = [];
                preg_match('/\d\d\d/', trim($header), $match);

                if (isset($match[0])) {
                    $httpCode    = $match[0];
                    $httpMessage = trim(substr($header, strpos($header, ' ' . $httpCode . ' ') + 5));
                }
            } else if (str_contains($header, ':')) {
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
     * @param  ?string $contentType
     * @param  ?string $encoding
     * @param  bool    $chunked
     * @return mixed
     */
    public static function parseDataByContentType(
        string $rawData, ?string $contentType = null, ?string $encoding = null, bool $chunked = false
    ): mixed
    {
        $parsedResult = false;

        if ($contentType !== null) {
            $contentType = strtolower($contentType);
        }
        if ($encoding !== null) {
            $encoding = strtoupper($encoding);
        }

        // JSON data
        if (($contentType !== null) && (str_contains($contentType, 'json'))) {
            $parsedResult = json_decode(self::decodeData($rawData, $encoding, $chunked), true);
            // XML data
        } else if (($contentType !== null) && (str_contains($contentType, 'xml'))) {
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
        } else if (($contentType !== null) && (str_contains($contentType, 'application/x-www-form-urlencoded'))) {
            $parsedResult = [];
            parse_str(self::decodeData($rawData, $encoding, $chunked), $parsedResult);
            // Multipart form data
        } else if (($contentType !== null) && (str_contains($contentType, 'multipart/form-data'))) {
            $formContent  = (!str_contains($rawData, 'Content-Type:')) ?
                'Content-Type: ' . $contentType . "\r\n\r\n" . $rawData : $rawData;
            $parsedResult = Message::parseForm($formContent);
            // Fallback to just the encoding
        } else if (($contentType !== null) && (str_contains($contentType, 'text/html') || str_contains($contentType, 'text/plain'))) {
            $parsedResult = $rawData;
        } else if ($encoding !== null) {
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
     * @throws Client\Exception|Exception
     * @return Server\Response
     */
    public static function parseResponseFromUri(
        string $uri, string $method = 'GET', string $mode = 'r', array $options = [], array $params = []
    ): Server\Response
    {
        $request  = new Client\Request($uri, $method);
        $handler  = new Client\Handler\Stream($mode, $options, $params);
        $response = $handler->prepare($request, null, false)->send();

        return new Server\Response([
            'code'    => $response->getCode(),
            'headers' => $response->getHeaders(),
            'body'    => $response->getBody(),
            'message' => $response->getMessage(),
            'version' => $response->getVersion()
        ]);
    }

    /**
     * Parse a response from a full response string
     *
     * @param  string $responseString
     * @return Server\Response
     */
    public static function parseResponseFromString(string $responseString): Server\Response
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

        return new Server\Response([
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
     * @param  string  $data
     * @param  ?string $encoding
     * @return string
     */
    public static function encodeData(string $data, ?string $encoding = null): string
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
     * @param  ?string $encoding
     * @param  bool    $chunked
     * @return string
     */
    public static function decodeData(string $data, ?string $encoding = null, bool $chunked = false): string
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
     * @param  string $data
     * @return string
     */
    public static function decodeChunkedData(string $data): string
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
