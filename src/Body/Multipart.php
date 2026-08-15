<?php
declare(strict_types=1);
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
namespace Pop\Http\Body;

use Pop\Http\Client\Data;

/**
 * HTTP multipart/form-data builder and parser (RFC 7578)
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Multipart
{

    /**
     * Convert a request data array into a curl-native array (scalars + CURLFile),
     * suitable for CURLOPT_POSTFIELDS. Curl streams CURLFile entries directly
     * from disk and builds the multipart framing itself - no buffering here.
     *
     * @param  array $data
     * @return array
     */
    public static function toArray(array $data): array
    {
        $result = [];

        foreach ($data as $name => $value) {
            if (is_array($value) && isset($value['filename'])) {
                $result[$name] = self::toCurlFile($value);
            } else if (is_array($value)) {
                $index = 0;
                foreach ($value as $item) {
                    $result[$name . '[' . $index . ']'] = (string)$item;
                    $index++;
                }
            } else {
                $result[$name] = (string)$value;
            }
        }

        return $result;
    }

    /**
     * Convert a single file-field value (by path, or by raw contents) into a CURLFile
     *
     * @param  array $value
     * @return \CURLFile
     */
    protected static function toCurlFile(array $value): \CURLFile
    {
        if (isset($value['contents'])) {
            $path = tempnam(sys_get_temp_dir(), 'pop-http-multipart-');
            file_put_contents($path, $value['contents']);
            $postFilename = $value['filename'];
            $mimeType     = $value['mimeType'] ?? Data::getMimeTypeFromFilename($value['filename']);

            // Register shutdown cleanup for temp file
            register_shutdown_function(function () use ($path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            });
        } else {
            $path         = $value['filename'];
            $postFilename = basename($value['filename']);
            $mimeType     = $value['contentType'] ?? $value['mimeType'] ?? $value['mime'] ??
                Data::getMimeTypeFromFilename($value['filename']);
        }

        return new \CURLFile($path, $mimeType, $postFilename);
    }

    /**
     * Generate a random multipart boundary
     *
     * @return string
     */
    public static function generateBoundary(): string
    {
        return '----PopHttpBoundary' . bin2hex(random_bytes(16));
    }

    /**
     * Build a rendered multipart/form-data body (RFC 7578) from a request data array.
     * File fields are streamed from disk into the output, not buffered as strings.
     *
     * @param  array   $data
     * @param  ?string $boundary
     * @return \Pop\Http\Body
     */
    public static function build(array $data, ?string $boundary = null): \Pop\Http\Body
    {
        // A caller-supplied boundary is never trusted verbatim - CR/LF in it would break out of
        // the boundary line and inject arbitrary headers/parts into the rendered body.
        $boundary = ($boundary !== null) ? str_replace(["\r", "\n"], '', $boundary) : self::generateBoundary();
        $output   = fopen('php://temp', 'r+');

        foreach ($data as $name => $value) {
            if (is_array($value) && isset($value['filename'])) {
                self::writeFilePart($output, $boundary, $name, $value);
            } else if (is_array($value)) {
                $index = 0;
                foreach ($value as $item) {
                    self::writeScalarPart($output, $boundary, $name . '[' . $index . ']', (string)$item);
                    $index++;
                }
            } else {
                self::writeScalarPart($output, $boundary, $name, (string)$value);
            }
        }

        fwrite($output, '--' . $boundary . "--\r\n");
        rewind($output);

        $body = new \Pop\Http\Body();
        $body->setContentFromStream($output);

        return $body;
    }

    /**
     * Escape a value for safe use inside a quoted Content-Disposition/Content-Type parameter
     *
     * @param  string $value
     * @return string
     */
    protected static function escapeHeaderValue(string $value): string
    {
        $value = str_replace(["\r", "\n"], '', $value);
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    /**
     * Write one non-file part to the output stream
     *
     * @param  resource $output
     * @param  string   $boundary
     * @param  string   $name
     * @param  string   $value
     * @return void
     */
    protected static function writeScalarPart(mixed $output, string $boundary, string $name, string $value): void
    {
        $escapedName = self::escapeHeaderValue($name);
        fwrite($output, '--' . $boundary . "\r\n");
        fwrite($output, 'Content-Disposition: form-data; name="' . $escapedName . '"' . "\r\n\r\n");
        fwrite($output, $value . "\r\n");
    }

    /**
     * Write one file part to the output stream, streaming the file's content
     * directly rather than reading it into a string first
     *
     * @param  resource $output
     * @param  string   $boundary
     * @param  string   $name
     * @param  array    $value
     * @throws Exception
     * @return void
     */
    protected static function writeFilePart(mixed $output, string $boundary, string $name, array $value): void
    {
        $postFilename = isset($value['contents']) ? $value['filename'] : basename($value['filename']);
        $mimeType     = $value['contentType'] ?? $value['mimeType'] ?? $value['mime'] ??
            Data::getMimeTypeFromFilename($value['filename']);

        // $name and $postFilename land inside quoted parameters, so they need the full quote-escaping.
        // $mimeType is an unquoted header value which may legitimately contain quoted parameters of
        // its own (e.g. charset="utf-8"), so only the actual injection vector (CR/LF) is stripped.
        $escapedName     = self::escapeHeaderValue($name);
        $escapedFilename = self::escapeHeaderValue($postFilename);
        $escapedMimeType = str_replace(["\r", "\n"], '', $mimeType);

        fwrite($output, '--' . $boundary . "\r\n");
        fwrite($output, 'Content-Disposition: form-data; name="' . $escapedName . '"; filename="' . $escapedFilename . '"' . "\r\n");
        fwrite($output, 'Content-Type: ' . $escapedMimeType . "\r\n\r\n");

        if (isset($value['contents'])) {
            fwrite($output, $value['contents']);
        } else {
            if (!file_exists($value['filename'])) {
                throw new Exception("Error: The file '" . $value['filename'] . "' does not exist.");
            }
            $file = @fopen($value['filename'], 'rb');
            if ($file === false) {
                throw new Exception("Error: Unable to open the file '" . $value['filename'] . "' for reading.");
            }
            stream_copy_to_stream($file, $output);
            fclose($file);
        }

        fwrite($output, "\r\n");
    }

    /**
     * Parse a raw multipart/form-data body into a flat data array
     *
     * @param  string $rawBody
     * @param  string $boundary
     * @return array
     */
    public static function parse(string $rawBody, string $boundary): array
    {
        $result       = [];
        $indexedKeys  = [];
        $parts        = explode('--' . $boundary, $rawBody);

        foreach ($parts as $part) {
            // Remove framing: one leading \r\n (after boundary line) and one trailing \r\n (before next boundary)
            // but preserve any \r\n that's part of the actual content
            if (strpos($part, "\r\n") === 0) {
                $part = substr($part, 2);
            }
            if (substr($part, -2) === "\r\n") {
                $part = substr($part, 0, -2);
            }

            if (($part === '') || ($part === '--')) {
                continue;
            }

            [$headerString, $content] = array_pad(explode("\r\n\r\n", $part, 2), 2, '');

            $name         = null;
            $filename     = null;
            $contentType  = null;

            foreach (explode("\r\n", $headerString) as $headerLine) {
                if (stripos($headerLine, 'Content-Disposition:') === 0) {
                    // Use regex that matches the attribute as a distinct entity (preceded by ; or start of line)
                    // to avoid matching 'name' inside 'filename'. \\\\. means backslash followed by any character
                    if (preg_match('/(?:^|;)\s*name="((?:\\\\.|[^"])*)"/i', $headerLine, $match)) {
                        $name = $match[1];
                        // Unescape: \\ becomes \, \" becomes "
                        $name = str_replace(['\\\\', '\\"'], ['\\', '"'], $name);
                    }
                    if (preg_match('/(?:^|;)\s*filename="((?:\\\\.|[^"])*)"/i', $headerLine, $match)) {
                        $filename = $match[1];
                        // Unescape: \\ becomes \, \" becomes "
                        $filename = str_replace(['\\\\', '\\"'], ['\\', '"'], $filename);
                    }
                } else if (stripos($headerLine, 'Content-Type:') === 0) {
                    $contentType = trim(substr($headerLine, strlen('Content-Type:')));
                }
            }

            if ($name === null) {
                continue;
            }

            if ($filename !== null) {
                $result[$name] = [
                    'filename'    => $filename,
                    'contentType' => $contentType,
                    'contents'    => $content,
                ];
            } else if (preg_match('/^(.+)\[(\d*)\]$/', $name, $arrayMatch)) {
                $key = $arrayMatch[1];
                if ($arrayMatch[2] === '') {
                    // Bare 'name[]' convention (e.g. plain HTML form submissions) - append in encounter order
                    $result[$key][] = $content;
                } else {
                    // Indexed 'name[N]' convention (e.g. this class's own build()/toArray() output) -
                    // place at the explicit index so the array is correct regardless of arrival order
                    $result[$key][(int)$arrayMatch[2]] = $content;
                    $indexedKeys[$key] = true;
                }
            } else {
                $result[$name] = $content;
            }
        }

        // Indexed entries are placed by key as they're encountered, which may not match the raw
        // body's arrival order - restore ascending index order so the array reads correctly.
        foreach (array_keys($indexedKeys) as $key) {
            ksort($result[$key]);
        }

        return $result;
    }

}
