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
namespace Pop\Http\Server;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Pop\Http\Body;

/**
 * HTTP server uploaded file class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class UploadedFile implements UploadedFileInterface
{

    /**
     * Path to the underlying (typically temporary) uploaded file
     * @var string
     */
    protected string $file;

    /**
     * File size in bytes
     * @var int
     */
    protected int $size;

    /**
     * One of PHP's UPLOAD_ERR_* constants
     * @var int
     */
    protected int $error;

    /**
     * Client-reported original filename
     * @var ?string
     */
    protected ?string $clientFilename;

    /**
     * Client-reported media type
     * @var ?string
     */
    protected ?string $clientMediaType;

    /**
     * Lazily-built stream wrapping $file
     * @var ?Body
     */
    protected ?Body $stream = null;

    /**
     * Whether moveTo() has already been called
     * @var bool
     */
    protected bool $moved = false;

    /**
     * Constructor
     *
     * @param string  $file
     * @param int     $size
     * @param int     $error
     * @param ?string $clientFilename
     * @param ?string $clientMediaType
     */
    public function __construct(string $file, int $size, int $error, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        $this->file            = $file;
        $this->size            = $size;
        $this->error           = $error;
        $this->clientFilename  = $clientFilename;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * Get a stream wrapping the uploaded file's contents
     *
     * @throws Exception
     * @return StreamInterface
     */
    public function getStream(): StreamInterface
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new Exception('Error: Cannot retrieve the stream - the upload failed with error code ' . $this->error . '.');
        }
        if ($this->moved) {
            throw new Exception('Error: Cannot retrieve the stream - the file has already been moved.');
        }

        if ($this->stream === null) {
            $this->stream = new Body();
            $this->stream->setContentFromFile($this->file);
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location
     *
     * @param  string $targetPath
     * @param  bool   $secure
     * @throws Exception
     * @return void
     */
    public function moveTo(string $targetPath, bool $secure = false): void
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            throw new Exception('Error: Cannot move the file - the upload failed with error code ' . $this->error . '.');
        }
        if ($this->moved) {
            throw new Exception('Error: Cannot move the file - it has already been moved.');
        }

        if (is_uploaded_file($this->file)) {
            if (!move_uploaded_file($this->file, $targetPath)) {
                throw new Exception("Error: Unable to move the uploaded file to '" . $targetPath . "'.");
            }
        } else if ($secure) {
            throw new Exception("Error: Unable to move the file to '" . $targetPath . "' - not a genuine upload.");
        } else if (!rename($this->file, $targetPath)) {
            throw new Exception("Error: Unable to move the file to '" . $targetPath . "'.");
        }

        $this->moved = true;
    }

    /**
     * Get the file size in bytes
     *
     * @return ?int
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Get the upload error code (one of PHP's UPLOAD_ERR_* constants)
     *
     * @return int
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Get the client-reported original filename
     *
     * @return ?string
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * Get the client-reported media type
     *
     * @return ?string
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }

    /**
     * Build a single UploadedFile instance from one $_FILES-shaped entry
     *
     * @param  array $file
     * @return UploadedFile
     */
    public static function fromFile(array $file): self
    {
        return new self(
            $file['tmp_name'],
            (int)($file['size'] ?? 0),
            (int)($file['error'] ?? UPLOAD_ERR_OK),
            $file['name'] ?? null,
            $file['type'] ?? null
        );
    }

    /**
     * Normalize a native $_FILES-shaped array (flat or per-field multi-file) into
     * an array of UploadedFile instances, preserving the original field-name keys
     *
     * @param  array $files
     * @return array
     */
    public static function createFromFilesArray(array $files): array
    {
        $uploadedFiles = [];

        foreach ($files as $name => $file) {
            if (!isset($file['tmp_name'])) {
                continue;
            }

            if (is_array($file['tmp_name'])) {
                foreach (array_keys($file['tmp_name']) as $key) {
                    $uploadedFiles[$name][$key] = new self(
                        $file['tmp_name'][$key],
                        (int)($file['size'][$key] ?? 0),
                        (int)($file['error'][$key] ?? UPLOAD_ERR_OK),
                        $file['name'][$key] ?? null,
                        $file['type'][$key] ?? null
                    );
                }
            } else {
                $uploadedFiles[$name] = self::fromFile($file);
            }
        }

        return $uploadedFiles;
    }

}
