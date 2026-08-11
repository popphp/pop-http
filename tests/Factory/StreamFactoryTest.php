<?php

namespace Pop\Http\Test\Factory;

use Pop\Http\Factory\StreamFactory;
use PHPUnit\Framework\TestCase;

class StreamFactoryTest extends TestCase
{

    public function testImplementsStreamFactoryInterface()
    {
        $this->assertInstanceOf('Psr\Http\Message\StreamFactoryInterface', new StreamFactory());
    }

    public function testCreateStream()
    {
        $stream = (new StreamFactory())->createStream('Hello World!');
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $stream);
        $this->assertEquals('Hello World!', $stream->getContents());
    }

    public function testCreateStreamFromFile()
    {
        $file = tempnam(sys_get_temp_dir(), 'pop-http-stream-factory-test-');
        file_put_contents($file, 'File contents');

        $stream = (new StreamFactory())->createStreamFromFile($file);
        $this->assertEquals('File contents', $stream->getContents());

        unlink($file);
    }

    public function testCreateStreamFromResource()
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, 'Resource contents');
        rewind($resource);

        $stream = (new StreamFactory())->createStreamFromResource($resource);
        $this->assertEquals('Resource contents', $stream->getContents());
    }

    public function testCreateStreamFromFileHonorsWriteMode()
    {
        $file = tempnam(sys_get_temp_dir(), 'pop-http-stream-factory-test-');

        $stream = (new StreamFactory())->createStreamFromFile($file, 'w');
        $this->assertTrue($stream->isWritable());

        $bytesWritten = $stream->write('New content');
        $this->assertEquals(11, $bytesWritten);
        $stream->close();

        $this->assertEquals('New content', file_get_contents($file));

        unlink($file);
    }

    public function testCreateStreamFromFileThrowsRuntimeExceptionOnMissingFile()
    {
        $this->expectException(\RuntimeException::class);
        (new StreamFactory())->createStreamFromFile(sys_get_temp_dir() . '/pop-http-stream-factory-test-missing-file');
    }

}
