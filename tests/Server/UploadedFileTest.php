<?php

namespace Pop\Http\Test\Server;

use Pop\Http\Server\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{

    protected string $tmpFile;

    protected function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'pop-http-uploaded-file-test-');
        file_put_contents($this->tmpFile, 'Hello World!');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testImplementsUploadedFileInterface()
    {
        $file = new UploadedFile($this->tmpFile, 12, UPLOAD_ERR_OK, 'photo.jpg', 'image/jpeg');
        $this->assertInstanceOf('Psr\Http\Message\UploadedFileInterface', $file);
    }

    public function testGetters()
    {
        $file = new UploadedFile($this->tmpFile, 12, UPLOAD_ERR_OK, 'photo.jpg', 'image/jpeg');
        $this->assertEquals(12, $file->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertEquals('photo.jpg', $file->getClientFilename());
        $this->assertEquals('image/jpeg', $file->getClientMediaType());
    }

    public function testGetStreamReturnsBodyWithFileContents()
    {
        $file   = new UploadedFile($this->tmpFile, 12, UPLOAD_ERR_OK);
        $stream = $file->getStream();
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals('Hello World!', $stream->getContents());
    }

    public function testGetStreamThrowsOnUploadError()
    {
        $file = new UploadedFile($this->tmpFile, 0, UPLOAD_ERR_NO_FILE);
        $this->expectException('Pop\Http\Server\Exception');
        $file->getStream();
    }

    public function testGetStreamErrorIsCatchableAsRuntimeException()
    {
        // Finding 5: Pop\Http\Server\Exception now extends \RuntimeException, so PSR-7's
        // documented @throws \RuntimeException for UploadedFileInterface::getStream() is honored.
        $file = new UploadedFile($this->tmpFile, 0, UPLOAD_ERR_NO_FILE);

        try {
            $file->getStream();
            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf('Pop\Http\Server\Exception', $e);
        }
    }

    public function testMoveToMovesTheFile()
    {
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pop-http-uploaded-file-moved-' . uniqid();
        $file   = new UploadedFile($this->tmpFile, 12, UPLOAD_ERR_OK);
        $file->moveTo($target);

        $this->assertFileDoesNotExist($this->tmpFile);
        $this->assertFileExists($target);
        $this->assertEquals('Hello World!', file_get_contents($target));

        unlink($target);
        $this->tmpFile = ''; // already moved, nothing for tearDown() to clean up
    }

    public function testMoveToThrowsWhenCalledTwice()
    {
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pop-http-uploaded-file-moved-' . uniqid();
        $file   = new UploadedFile($this->tmpFile, 12, UPLOAD_ERR_OK);
        $file->moveTo($target);
        $this->tmpFile = ''; // already moved, nothing for tearDown() to clean up

        try {
            $this->expectException('Pop\Http\Server\Exception');
            $file->moveTo($target);
        } finally {
            if (file_exists($target)) {
                unlink($target);
            }
        }
    }

    public function testMoveToWithSecureTrueThrowsWhenNotAnUploadedFile()
    {
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pop-http-uploaded-file-secure-' . uniqid();
        $file   = new UploadedFile($this->tmpFile, 12, UPLOAD_ERR_OK);

        $this->expectException('Pop\Http\Server\Exception');
        try {
            $file->moveTo($target, true);
        } finally {
            $this->assertFileExists($this->tmpFile);
            $this->assertFileDoesNotExist($target);
        }
    }

    public function testMoveToWithSecureFalseStillFallsBackToRename()
    {
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pop-http-uploaded-file-secure-' . uniqid();
        $file   = new UploadedFile($this->tmpFile, 12, UPLOAD_ERR_OK);
        $file->moveTo($target, false);

        $this->assertFileDoesNotExist($this->tmpFile);
        $this->assertFileExists($target);

        unlink($target);
        $this->tmpFile = ''; // already moved, nothing for tearDown() to clean up
    }

    public function testCreateFromFilesArraySingleFile()
    {
        $files = [
            'avatar' => ['name' => 'photo.jpg', 'type' => 'image/jpeg', 'tmp_name' => $this->tmpFile, 'error' => UPLOAD_ERR_OK, 'size' => 12]
        ];
        $result = UploadedFile::createFromFilesArray($files);

        $this->assertCount(1, $result);
        $this->assertInstanceOf('Pop\Http\Server\UploadedFile', $result['avatar']);
        $this->assertEquals('photo.jpg', $result['avatar']->getClientFilename());
    }

    public function testCreateFromFilesArrayMultiFileField()
    {
        $files = [
            'docs' => [
                'name'     => ['a.txt', 'b.txt'],
                'type'     => ['text/plain', 'text/plain'],
                'tmp_name' => [$this->tmpFile, $this->tmpFile],
                'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size'     => [12, 12],
            ]
        ];
        $result = UploadedFile::createFromFilesArray($files);

        $this->assertCount(2, $result['docs']);
        $this->assertEquals('a.txt', $result['docs'][0]->getClientFilename());
        $this->assertEquals('b.txt', $result['docs'][1]->getClientFilename());
    }

    public function testFromFileBuildsEquivalentInstance()
    {
        $raw = [
            'tmp_name' => $this->tmpFile,
            'size'     => 12,
            'error'    => UPLOAD_ERR_OK,
            'name'     => 'photo.jpg',
            'type'     => 'image/jpeg',
        ];

        $file = UploadedFile::fromFile($raw);

        $this->assertEquals(12, $file->getSize());
        $this->assertEquals(UPLOAD_ERR_OK, $file->getError());
        $this->assertEquals('photo.jpg', $file->getClientFilename());
        $this->assertEquals('image/jpeg', $file->getClientMediaType());
    }

}
