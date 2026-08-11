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
namespace Pop\Http\Factory;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Pop\Http\Body;

/**
 * PSR-17 stream factory class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class StreamFactory implements StreamFactoryInterface
{

    /**
     * Create a new stream from a string
     *
     * @param  string $content
     * @return StreamInterface
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Body($content);
    }

    /**
     * Create a new stream from a file path
     *
     * Opens the file directly with the given mode (bypassing Body::setContentFromFile(),
     * which always opens read-only) so the stream honors the mode the caller requested,
     * and throws \RuntimeException on failure per the PSR-17 contract.
     *
     * @param  string $filename
     * @param  string $mode
     * @throws \RuntimeException
     * @return StreamInterface
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = @fopen($filename, $mode);
        if ($resource === false) {
            throw new \RuntimeException("Error: Unable to open the file '" . $filename . "' with mode '" . $mode . "'.");
        }

        $body = new Body();
        $body->setContentFromStream($resource);
        $body->setAsFile(true);

        return $body;
    }

    /**
     * Create a new stream from an existing resource
     *
     * @param  mixed $resource
     * @return StreamInterface
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        $body = new Body();
        $body->setContentFromStream($resource);
        return $body;
    }

}
