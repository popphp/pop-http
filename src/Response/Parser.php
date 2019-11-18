<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Response;

use Pop\Http;
use Pop\Http\Client\Stream;

/**
 * HTTP response parser class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2020 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    3.5.0
 */
class Parser
{

    /**
     * Parse a response from a URI
     *
     * @param  string $uri
     * @param  string $method
     * @param  string $mode
     * @param  array  $options
     * @return Http\Response
     */
    public static function parseFromUri($uri, $method = 'GET', $mode = 'r', array $options = [])
    {
        $client  = new Stream($uri, $method, $mode, $options);
        $client->send();

        return new Http\Response([
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
     * @throws Exception
     * @return Http\Response
     */
    public static function parseFromString($responseString)
    {
        if (substr($responseString, 0, 5) != 'HTTP/') {
            throw new Exception('The response was not properly formatted.');
        }

        if (strpos($responseString, "\r") !== false) {
            $headerString = substr($responseString, 0, strpos($responseString, "\r\n\r\n"));
            $bodyString   = substr($responseString, (strpos($responseString, "\r\n\r\n") + 4));
        } else {
            $headerString = substr($responseString, 0, strpos($responseString, "\n\n"));
            $bodyString   = substr($responseString, (strpos($responseString, "\n\n") + 2));
        }

        $firstLine     = trim(substr($headerString, 0, strpos($headerString, "\n")));
        $allHeaders    = trim(substr($headerString, strpos($headerString, "\n")));
        $allHeadersAry = explode("\n", $allHeaders);
        $headers       = [];

        // Get the version, code and message
        $version = substr($firstLine, 0, strpos($firstLine, ' '));
        $version = substr($version, (strpos($version, '/') + 1));
        preg_match('/\d\d\d/', trim($firstLine), $match);
        $code    = $match[0];
        $message = str_replace('HTTP/' . $version . ' ' . $code . ' ', '', $firstLine);

        // Get the headers
        foreach ($allHeadersAry as $hdr) {
            $name  = substr($hdr, 0, strpos($hdr, ':'));
            $value = substr($hdr, (strpos($hdr, ' ') + 1));
            $headers[trim($name)] = trim($value);
        }

        // If the body content is encoded, decode the body content
        if (array_key_exists('Content-Encoding', $headers)) {
            if (isset($headers['Transfer-Encoding']) && ($headers['Transfer-Encoding'] == 'chunked')) {
                $bodyString = self::decodeChunkedBody($bodyString);
            }
            $body = self::decodeBody($bodyString, $headers['Content-Encoding']);
        } else {
            $body = $bodyString;
        }

        return new Http\Response([
            'code'    => $code,
            'headers' => $headers,
            'body'    => $body,
            'message' => $message,
            'version' => $version
        ]);
    }

    /**
     * Encode the body data
     *
     * @param  string $body
     * @param  string $encode
     * @throws Exception
     * @return string
     */
    public static function encodeBody($body, $encode = 'gzip')
    {
        switch ($encode) {
            // GZIP compression
            case 'gzip':
                if (!function_exists('gzencode')) {
                    throw new Exception('Gzip compression is not available.');
                }
                $encodedBody = gzencode($body);
                break;

            // Deflate compression
            case 'deflate':
                if (!function_exists('gzdeflate')) {
                    throw new Exception('Deflate compression is not available.');
                }
                $encodedBody = gzdeflate($body);
                break;

            // Unknown compression
            default:
                $encodedBody = $body;

        }

        return $encodedBody;
    }

    /**
     * Decode the body data
     *
     * @param  string $body
     * @param  string $decode
     * @throws Exception
     * @return string
     */
    public static function decodeBody($body, $decode = 'gzip')
    {
        switch ($decode) {
            // GZIP compression
            case 'gzip':
                if (!function_exists('gzinflate')) {
                    throw new Exception('Gzip compression is not available.');
                }
                $decodedBody = gzinflate(substr($body, 10));
                break;

            // Deflate compression
            case 'deflate':
                if (!function_exists('gzinflate')) {
                    throw new Exception('Deflate compression is not available.');
                }
                $zLibHeader = unpack('n', substr($body, 0, 2));
                $decodedBody = ($zLibHeader[1] % 31 == 0) ? gzuncompress($body) : gzinflate($body);
                break;

            // Unknown compression
            default:
                $decodedBody = $body;

        }

        return $decodedBody;
    }

    /**
     * Decode a chunked transfer-encoded body and return the decoded text
     *
     * @param string $body
     * @return string
     */
    public static function decodeChunkedBody($body)
    {
        $decoded = '';

        while($body != '') {
            $lfPos = strpos($body, "\012");

            if ($lfPos === false) {
                $decoded .= $body;
                break;
            }

            $chunkHex = trim(substr($body, 0, $lfPos));
            $scPos    = strpos($chunkHex, ';');

            if ($scPos !== false) {
                $chunkHex = substr($chunkHex, 0, $scPos);
            }

            if ($chunkHex == '') {
                $decoded .= substr($body, 0, $lfPos);
                $body = substr($body, $lfPos + 1);
                continue;
            }

            $chunkLength = hexdec($chunkHex);

            if ($chunkLength) {
                $decoded .= substr($body, $lfPos + 1, $chunkLength);
                $body = substr($body, $lfPos + 2 + $chunkLength);
            } else {
                $body = '';
            }
        }

        return $decoded;
    }

}
