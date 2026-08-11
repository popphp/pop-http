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

use Psr\Http\Message\StreamInterface;

/**
 * HTTP body class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Body implements StreamInterface
{

    /**
     * Encoding constants
     * @var string
     */
    const BASE64  = 'BASE64';
    const QUOTED  = 'QUOTED';
    const URL     = 'URL';
    const RAW_URL = 'RAW_URL';

    /**
     * Underlying content stream. All content - string-set or file-backed - lives here.
     * @var resource|null
     */
    protected mixed $stream = null;

    /**
     * Encoding
     * @var ?string
     */
    protected ?string $encoding = null;

    /**
     * Chunk split
     * @var int|bool|null
     */
    protected int|bool|null $split = null;

    /**
     * Is file flag
     * @var bool
     */
    protected bool $isFile = false;

    /**
     * Is encoded flag
     * @var bool
     */
    protected bool $isEncoded = false;

    /**
     * Constructor
     *
     * Instantiate the body object
     *
     * @param ?string       $content
     * @param ?string       $encoding
     * @param int|bool|null $split
     */
    public function __construct(?string $content = null, ?string $encoding = null, int|bool|null $split = null)
    {
        if ($content !== null) {
            $this->setContent($content);
        }
        if ($encoding !== null) {
            $this->setEncoding($encoding);
        }
        if ($split !== null) {
            $this->setSplit($split);
        }
    }

    /**
     * Set the body content from a string
     *
     * @param  string $content
     * @return Body
     */
    public function setContent(string $content): Body
    {
        $this->stream = fopen('php://temp', 'r+');
        fwrite($this->stream, $content);
        rewind($this->stream);
        $this->isFile = false;

        return $this;
    }

    /**
     * Get the body content as a string
     *
     * Reads the entire underlying stream and restores its read position
     * afterward, so repeated calls return the same content.
     *
     * @return string
     */
    public function getContent(): string
    {
        if ($this->stream === null) {
            return '';
        }

        $position = ftell($this->stream);
        rewind($this->stream);
        $content = stream_get_contents($this->stream);
        fseek($this->stream, $position);

        return $content;
    }

    /**
     * Has body content
     *
     * @return bool
     */
    public function hasContent(): bool
    {
        return ($this->stream !== null);
    }

    /**
     * Set the body content from a file, without buffering it into memory
     *
     * @param  string        $file
     * @param  ?string       $encoding
     * @param  int|bool|null $split
     * @throws Exception
     * @return Body
     */
    public function setContentFromFile(string $file, ?string $encoding = null, int|bool|null $split = null): Body
    {
        if (!file_exists($file)) {
            throw new Exception("Error: The file '" . $file . "' does not exist.");
        }

        $stream = @fopen($file, 'rb');
        if ($stream === false) {
            throw new Exception("Error: Unable to open the file '" . $file . "' for reading.");
        }

        $this->stream = $stream;
        $this->isFile = true;

        if ($encoding !== null) {
            $this->setEncoding($encoding);
        }
        if ($split !== null) {
            $this->setSplit($split);
        }

        return $this;
    }

    /**
     * Set the body content directly from an existing stream resource
     *
     * If the given stream is known to be backed by a real file (e.g. a handle returned
     * from fopen() on a local file), chain ->setAsFile(true) afterward to flag it as such,
     * since this method has no reliable way to detect that on its own.
     *
     * @param  mixed $stream
     * @return Body
     */
    public function setContentFromStream(mixed $stream): Body
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * Get the raw underlying stream resource, for a consumer that wants to
     * copy bytes directly (e.g. to the network) without materializing the
     * whole content as a PHP string.
     *
     * @return mixed
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    /**
     * Close the underlying stream
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->stream !== null) {
            fclose($this->stream);
            $this->stream = null;
        }
    }

    /**
     * Detach the underlying stream resource, leaving the Body empty
     *
     * @return mixed
     */
    public function detach(): mixed
    {
        $stream       = $this->stream;
        $this->stream = null;
        return $stream;
    }

    /**
     * Get the content size in bytes, without reading file-backed content
     * into memory to compute it. Per PSR-7, returns null if the stream size
     * is not statable (e.g. php://output).
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        if ($this->stream === null) {
            return 0;
        }

        // Not every stream is statable (php://output, some sockets/pipes); fstat() returns false
        // for those, and per StreamInterface that means "size unknown", not zero.
        $stat = @fstat($this->stream);

        return ($stat === false) ? null : $stat['size'];
    }

    /**
     * Get the current position of the stream read/write pointer
     *
     * @throws Exception
     * @return int
     */
    public function tell(): int
    {
        if ($this->stream === null) {
            throw new Exception('Error: No stream is available.');
        }

        $position = ftell($this->stream);
        if ($position === false) {
            throw new Exception('Error: Unable to determine the stream position.');
        }

        return $position;
    }

    /**
     * Determine if the stream is at end-of-file
     *
     * @return bool
     */
    public function eof(): bool
    {
        return ($this->stream === null) || feof($this->stream);
    }

    /**
     * Determine if the stream is seekable
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        if ($this->stream === null) {
            return false;
        }

        return (bool)stream_get_meta_data($this->stream)['seekable'];
    }

    /**
     * Seek to a position in the stream
     *
     * @param  int $offset
     * @param  int $whence
     * @throws Exception
     * @return void
     */
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (($this->stream === null) || (!$this->isSeekable()) || (fseek($this->stream, $offset, $whence) === -1)) {
            throw new Exception('Error: Unable to seek the stream.');
        }
    }

    /**
     * Seek to the beginning of the stream
     *
     * @throws Exception
     * @return void
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Determine if the stream is writable
     *
     * @return bool
     */
    public function isWritable(): bool
    {
        if ($this->stream === null) {
            return false;
        }

        $mode = stream_get_meta_data($this->stream)['mode'];

        return (str_contains($mode, 'w') || str_contains($mode, 'a') || str_contains($mode, '+') || str_contains($mode, 'x') || str_contains($mode, 'c'));
    }

    /**
     * Write data to the stream
     *
     * @param  string $string
     * @throws Exception
     * @return int
     */
    public function write(string $string): int
    {
        if ($this->stream === null) {
            throw new Exception('Error: No stream is available.');
        }

        $result = fwrite($this->stream, $string);
        if ($result === false) {
            throw new Exception('Error: Unable to write to the stream.');
        }

        return $result;
    }

    /**
     * Determine if the stream is readable
     *
     * @return bool
     */
    public function isReadable(): bool
    {
        if ($this->stream === null) {
            return false;
        }

        $mode = stream_get_meta_data($this->stream)['mode'];

        return (str_contains($mode, 'r') || str_contains($mode, '+'));
    }

    /**
     * Read up to $length bytes from the stream
     *
     * @param  int $length
     * @throws Exception
     * @return string
     */
    public function read(int $length): string
    {
        if ($this->stream === null) {
            throw new Exception('Error: No stream is available.');
        }

        $result = fread($this->stream, $length);
        if ($result === false) {
            throw new Exception('Error: Unable to read from the stream.');
        }

        return $result;
    }

    /**
     * Get the remaining raw stream contents, per PSR-7 - bypasses render()'s
     * encoding/split transforms, unlike __toString()
     *
     * @throws Exception
     * @return string
     */
    public function getContents(): string
    {
        return $this->getContent();
    }

    /**
     * Get stream metadata
     *
     * @param  ?string $key
     * @return mixed
     */
    public function getMetadata(?string $key = null): mixed
    {
        if ($this->stream === null) {
            return ($key === null) ? [] : null;
        }

        $meta = stream_get_meta_data($this->stream);

        return ($key === null) ? $meta : ($meta[$key] ?? null);
    }

    /**
     * Deep-duplicate the underlying stream resource so a clone never shares
     * a read/write position with the instance it was cloned from
     *
     * @return void
     */
    public function __clone(): void
    {
        if ($this->stream !== null) {
            $position  = ftell($this->stream);
            $duplicate = fopen('php://temp', 'r+');
            rewind($this->stream);
            stream_copy_to_stream($this->stream, $duplicate);
            fseek($this->stream, $position);
            // Note: duplicate is left at the end by stream_copy_to_stream(), not reset
            $this->stream = $duplicate;
        }
    }

    /**
     * Set the encoding
     *
     * @param  string $encoding
     * @return Body
     */
    public function setEncoding(string $encoding): Body
    {
        switch ($encoding) {
            case self::BASE64:
            case self::QUOTED:
            case self::URL:
            case self::RAW_URL:
                $this->encoding = $encoding;
        }
        return $this;
    }

    /**
     * Get the encoding
     *
     * @return string|null
     */
    public function getEncoding(): string|null
    {
        return $this->encoding;
    }

    /**
     * Has encoding
     *
     * @return bool
     */
    public function hasEncoding(): bool
    {
        return ($this->encoding !== null);
    }

    /**
     * Is encoding base64
     *
     * @return bool
     */
    public function isBase64Encoding(): bool
    {
        return ($this->encoding == self::BASE64);
    }

    /**
     * Is encoding quoted-printable
     *
     * @return bool
     */
    public function isQuotedEncoding(): bool
    {
        return ($this->encoding == self::QUOTED);
    }

    /**
     * Is encoding URL
     *
     * @return bool
     */
    public function isUrlEncoding(): bool
    {
        return ($this->encoding == self::URL);
    }

    /**
     * Is encoding raw URL
     *
     * @return bool
     */
    public function isRawUrlEncoding(): bool
    {
        return ($this->encoding == self::RAW_URL);
    }

    /**
     * Set the split
     *
     * @param  int|bool $split
     * @return Body
     */
    public function setSplit(int|bool $split): Body
    {
        $this->split = $split;
        return $this;
    }

    /**
     * Get the split
     *
     * @return int|bool|null
     */
    public function getSplit(): int|bool|null
    {
        return $this->split;
    }

    /**
     * Has split
     *
     * @return bool
     */
    public function hasSplit(): bool
    {
        return ($this->split !== null);
    }

    /**
     * Set as encoded
     *
     * @param  bool $isEncoded
     * @return Body
     */
    public function setAsEncoded(bool $isEncoded): Body
    {
        $this->isEncoded = $isEncoded;
        return $this;
    }

    /**
     * Is encoded
     *
     * @return bool
     */
    public function isEncoded(): bool
    {
        return $this->isEncoded;
    }

    /**
     * Set as file
     *
     * @param  bool $isFile
     * @return Body
     */
    public function setAsFile(bool $isFile): Body
    {
        $this->isFile = $isFile;
        return $this;
    }

    /**
     * Is file
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return $this->isFile;
    }

    /**
     * Render the body
     *
     * @return string
     */
    public function render(): string
    {
        $content = $this->getContent();

        if (!$this->isEncoded) {
            switch ($this->encoding) {
                case self::BASE64:
                    $content = base64_encode($content);
                    break;
                case self::QUOTED:
                    $content = quoted_printable_encode($content);
                    break;
                case self::URL:
                    $content = urlencode($content);
                    break;
                case self::RAW_URL:
                    $content = rawurlencode($content);
                    break;
            }
        }

        if ($this->split !== null) {
            $content = ($this->split === true) ? chunk_split($content) : chunk_split($content, (int)$this->split);
        }

        return (string)$content;
    }

    /**
     * Render the body
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

}
