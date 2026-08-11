<?php

namespace Pop\Http\Test\Factory;

use Pop\Http\Factory\StreamFactory;
use Pop\Http\Factory\UploadedFileFactory;
use PHPUnit\Framework\TestCase;

class UploadedFileFactoryTest extends TestCase
{

    public function testImplementsUploadedFileFactoryInterface()
    {
        $this->assertInstanceOf('Psr\Http\Message\UploadedFileFactoryInterface', new UploadedFileFactory());
    }

    public function testCreateUploadedFile()
    {
        $stream = (new StreamFactory())->createStream('Hello World!');
        $file   = (new UploadedFileFactory())->createUploadedFile($stream, 12, UPLOAD_ERR_OK, 'photo.jpg', 'image/jpeg');

        $this->assertInstanceOf('Psr\Http\Message\UploadedFileInterface', $file);
        $this->assertEquals(12, $file->getSize());
        $this->assertEquals('photo.jpg', $file->getClientFilename());
        $this->assertEquals('Hello World!', $file->getStream()->getContents());
    }

}
