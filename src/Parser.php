<?php
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
namespace Pop\Http;

use Pop\Mime\Part\Header;

/**
 * HTTP response parser class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
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
     * Parse a Content-Type header value into its structured parts
     *
     * @param  string $contentType
     * @return array
     */
    public static function parseMediaType(string $contentType): array
    {
        $parts    = explode(';', $contentType);
        $fullType = strtolower(trim(array_shift($parts)));
        $params   = [];

        foreach ($parts as $part) {
            if (str_contains($part, '=')) {
                [$key, $value] = array_map('trim', explode('=', $part, 2));
                $params[strtolower($key)] = trim($value, '"');
            }
        }

        [$type, $subtypeAndSuffix] = array_pad(explode('/', $fullType, 2), 2, '');
        $suffix   = null;
        $subtype  = $subtypeAndSuffix;

        if (str_contains($subtypeAndSuffix, '+')) {
            [$subtype, $suffix] = explode('+', $subtypeAndSuffix, 2);
        }

        return [
            'type'    => $type,
            'subtype' => $subtype,
            'suffix'  => $suffix,
            'params'  => $params,
        ];
    }

    /**
     * Parse request or response data based on content type
     *
     * @param  string  $rawData
     * @param  ?string $contentType
     * @param  ?string $encoding
     * @param  bool    $chunked
     * @throws Exception
     * @return mixed
     */
    public static function parseDataByContentType(
        string $rawData, ?string $contentType = null, ?string $encoding = null, bool $chunked = false
    ): mixed
    {
        if ($encoding !== null) {
            $encoding = strtoupper($encoding);
        }

        if ($contentType === null) {
            return self::decodeData($rawData, $encoding, $chunked);
        }

        $media   = self::parseMediaType($contentType);
        $rawData = self::decodeData($rawData, $encoding, $chunked);

        if (isset($media['params']['charset']) && (strtoupper($media['params']['charset']) !== 'UTF-8')) {
            $converted = @mb_convert_encoding($rawData, 'UTF-8', $media['params']['charset']);
            if ($converted !== false) {
                $rawData = $converted;
            }
        }

        // JSON: application/json, or a structured +json suffix (e.g. application/vnd.api+json)
        if (($media['subtype'] === 'json') || ($media['suffix'] === 'json')) {
            return json_decode($rawData, true);
        }

        // Generic XML only: application/xml or text/xml specifically - NOT a
        // +xml structured suffix (e.g. application/xhtml+xml, application/rdf+xml),
        // and not an office document that merely contains "xml" in its name.
        if ((($media['subtype'] === 'xml') && ($media['suffix'] === null)) &&
            !self::isOfficeDocument($contentType)) {
            return self::parseXml($rawData);
        }

        if (($media['type'] === 'application') && ($media['subtype'] === 'x-www-form-urlencoded')) {
            $parsedResult = [];
            parse_str($rawData, $parsedResult);
            return $parsedResult;
        }

        if (($media['type'] === 'multipart') && ($media['subtype'] === 'form-data')) {
            $boundary = $media['params']['boundary'] ?? null;
            if ($boundary === null) {
                throw new Exception('Error: The multipart/form-data content type is missing its boundary parameter.');
            }
            return \Pop\Http\Body\Multipart::parse($rawData, $boundary);
        }

        return $rawData;
    }

    /**
     * Parse an XML string into an array, throwing on malformed input instead
     * of silently returning a nonsense value.
     *
     * @param  string $rawData
     * @throws Exception
     * @return array
     */
    protected static function parseXml(string $rawData): array
    {
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

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $xml = simplexml_load_string($rawData);
        $errors = libxml_get_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            $message = !empty($errors) ? trim($errors[0]->message) : 'unknown XML parse error';
            throw new Exception('Error: Unable to parse XML content - ' . $message);
        }

        return json_decode(json_encode((array)$xml), true);
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
            'headers' => $response->getHeaderObjects(),
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
                $data = gzdecode($data);
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

    /**
     * Determine if the content-type is that of an office document
     *
     * @param  string $contentType
     * @return bool
     */
    public static function isOfficeDocument(string $contentType): bool
    {
        $keywords = [
            'open', 'office', 'ms-', 'word', 'excel', 'powerpoint', 'document',
            'presentation', 'sheet', 'template', 'slideshow', 'addin'
        ];
        $contentType = strtolower($contentType);

        foreach ($keywords as $keyword) {
            if (str_contains($contentType, $keyword)) {
                return true;
            }
        }

        return false;
    }

}
