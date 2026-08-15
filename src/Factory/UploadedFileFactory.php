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
namespace Pop\Http\Factory;

use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;
use Pop\Http\Server\UploadedFile;

/**
 * PSR-17 uploaded file factory class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class UploadedFileFactory implements UploadedFileFactoryInterface
{

    /**
     * Create a new uploaded file, materializing the given stream's contents
     * into a temporary file so UploadedFile has a real path to work from
     *
     * @param  StreamInterface $stream
     * @param  ?int            $size
     * @param  int             $error
     * @param  ?string         $clientFilename
     * @param  ?string         $clientMediaType
     * @return UploadedFileInterface
     */
    public function createUploadedFile(
        StreamInterface $stream,
        ?int $size = null,
        int $error = UPLOAD_ERR_OK,
        ?string $clientFilename = null,
        ?string $clientMediaType = null
    ): UploadedFileInterface
    {
        $content = (string)$stream;
        $tmpFile = tempnam(sys_get_temp_dir(), 'pop-http-uploaded-');
        file_put_contents($tmpFile, $content);

        return new UploadedFile($tmpFile, $size ?? strlen($content), $error, $clientFilename, $clientMediaType);
    }

}
