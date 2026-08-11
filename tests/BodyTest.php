<?php

namespace Pop\Http\Test;

use Pop\Http\Body;
use PHPUnit\Framework\TestCase;

class BodyTest extends TestCase
{

    public function testConstructor()
    {
        $body = new Body('Hello World!');
        $this->assertInstanceOf('Pop\Http\Body', $body);
        $this->assertTrue($body->hasContent());
        $this->assertEquals('Hello World!', $body->getContent());
    }

    public function testSetContent()
    {
        $body = new Body();
        $body->setContent('Testing 123');
        $this->assertEquals('Testing 123', $body->getContent());
    }

    public function testEmptyBodyHasNoContent()
    {
        $body = new Body();
        $this->assertFalse($body->hasContent());
        $this->assertEquals('', $body->getContent());
    }

    public function testGetContentIsRepeatable()
    {
        // Reading content twice must return the same thing both times -
        // this guards against a stream-backed implementation accidentally
        // consuming/rewinding incorrectly.
        $body = new Body('Repeatable content');
        $this->assertEquals('Repeatable content', $body->getContent());
        $this->assertEquals('Repeatable content', $body->getContent());
    }

    public function testBase64Encoding()
    {
        $body = new Body('Hello World!', Body::BASE64);
        $this->assertTrue($body->hasEncoding());
        $this->assertTrue($body->isBase64Encoding());
        $this->assertEquals(base64_encode('Hello World!'), $body->render());
        // isEncoded is only true if explicitly set via setAsEncoded(), not as a side effect of render()
        $this->assertFalse($body->isEncoded());
    }

    public function testQuotedEncoding()
    {
        $body = new Body("Hello=World!\n", Body::QUOTED);
        $this->assertTrue($body->isQuotedEncoding());
        $this->assertEquals(quoted_printable_encode("Hello=World!\n"), $body->render());
    }

    public function testUrlEncoding()
    {
        $body = new Body('a b+c', Body::URL);
        $this->assertTrue($body->isUrlEncoding());
        $this->assertEquals(urlencode('a b+c'), $body->render());
    }

    public function testRawUrlEncoding()
    {
        $body = new Body('a b+c', Body::RAW_URL);
        $this->assertTrue($body->isRawUrlEncoding());
        $this->assertEquals(rawurlencode('a b+c'), $body->render());
    }

    public function testSplit()
    {
        $body = new Body(str_repeat('a', 200));
        $body->setSplit(76);
        $this->assertTrue($body->hasSplit());
        $this->assertEquals(chunk_split(str_repeat('a', 200), 76), $body->render());
    }

    public function testToString()
    {
        $body = new Body('Hello World!');
        $this->assertEquals('Hello World!', (string)$body);
    }

    public function testSetAsEncoded()
    {
        // If the caller has pre-encoded the content themselves, render()
        // must not encode it a second time.
        $body = new Body(base64_encode('Hello World!'), Body::BASE64);
        $body->setAsEncoded(true);
        $this->assertEquals(base64_encode('Hello World!'), $body->render());
    }

    public function testGetSplitDoesNotThrowBeforeSetSplit()
    {
        $body = new Body('hello');
        $this->assertNull($body->getSplit());
    }

    public function testRenderIsIdempotentForEncodedContent()
    {
        $body = new Body('Hello World!', Body::BASE64);
        $first  = $body->render();
        $second = $body->render();
        $this->assertEquals(base64_encode('Hello World!'), $first);
        $this->assertEquals($first, $second);
    }

    public function testSetContentFromFile()
    {
        $file = sys_get_temp_dir() . '/pop-http-body-test-' . uniqid() . '.txt';
        file_put_contents($file, 'File contents for testing');

        $body = new Body();
        $body->setContentFromFile($file);

        $this->assertTrue($body->isFile());
        $this->assertEquals('File contents for testing', $body->getContent());

        unlink($file);
    }

    public function testSetContentFromFileThrowsOnMissingFile()
    {
        $this->expectException(\Pop\Http\Exception::class);
        $body = new Body();
        $body->setContentFromFile('/path/does/not/exist.txt');
    }

    public function testSetContentFromFileDoesNotBufferWholeFile()
    {
        // This is the actual bug-fix assertion: setContentFromFile() must not
        // read the file into memory itself. We can't directly observe "no
        // file_get_contents() was called," but we CAN assert that getStream()
        // returns a real, still-open, seekable file resource rather than a
        // php://temp copy - proving the content was never copied into a
        // separate in-memory buffer at set-time.
        $file = sys_get_temp_dir() . '/pop-http-body-test-' . uniqid() . '.txt';
        file_put_contents($file, 'stream me');

        $body = new Body();
        $body->setContentFromFile($file);

        $meta = stream_get_meta_data($body->getStream());
        $this->assertEquals($file, $meta['uri']);

        unlink($file);
    }

    public function testGetSizeForStringContent()
    {
        $body = new Body('12345');
        $this->assertEquals(5, $body->getSize());
    }

    public function testGetSizeForFileContentDoesNotReadFile()
    {
        $file = sys_get_temp_dir() . '/pop-http-body-test-' . uniqid() . '.txt';
        file_put_contents($file, str_repeat('x', 1000));

        $body = new Body();
        $body->setContentFromFile($file);

        $this->assertEquals(1000, $body->getSize());
        // The stream's internal pointer must still be at 0 - getSize() must
        // not have consumed the stream to compute the length.
        $this->assertEquals(0, ftell($body->getStream()));

        unlink($file);
    }

    public function testGetSizeReturnsNullForNonStatableStream()
    {
        // Per PSR-7, non-statable streams (php://output, some pipes/sockets) should return null
        // (meaning "size unknown"), not 0 (which would mean "known to be zero bytes").
        $stream = fopen('php://output', 'w');
        $this->assertFalse(@fstat($stream), 'php://output is expected to be non-statable');

        $body = new Body();
        $body->setContentFromStream($stream);

        $this->assertSame(null, $body->getSize());

        fclose($stream);
    }

    public function testSetContentFromFileThrowsWhenFileIsUnreadable()
    {
        $file = sys_get_temp_dir() . '/pop-http-body-test-' . uniqid() . '.txt';
        file_put_contents($file, 'content');
        chmod($file, 0000);

        try {
            $this->expectException(\Pop\Http\Exception::class);
            $body = new Body();
            $body->setContentFromFile($file);
        } finally {
            chmod($file, 0644);
            unlink($file);
        }
    }

    public function testSetAsFile()
    {
        $body = new Body('Hello World!');
        $this->assertFalse($body->isFile());

        $this->assertInstanceOf(Body::class, $body->setAsFile(true));
        $this->assertTrue($body->isFile());
    }

    public function testSetContentFromStreamWithSetAsFile()
    {
        // setContentFromStream() cannot reliably detect whether an arbitrary stream
        // resource is file-backed, so callers who know it is must chain setAsFile(true)
        // themselves. This confirms that path now correctly flags the body as a file.
        $file = sys_get_temp_dir() . '/pop-http-body-test-' . uniqid() . '.txt';
        file_put_contents($file, 'File contents for testing');

        $stream = fopen($file, 'rb');

        $body = new Body();
        $body->setContentFromStream($stream)->setAsFile(true);

        $this->assertTrue($body->isFile());
        $this->assertEquals('File contents for testing', $body->getContent());

        fclose($stream);
        unlink($file);
    }

    public function testImplementsStreamInterface()
    {
        $body = new Body('Hello World!');
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $body);
    }

    public function testGetContentsReturnsRawContentBypassingEncoding()
    {
        $body = new Body('Hello World!', Body::BASE64);
        // __toString()/render() applies the base64 transform...
        $this->assertEquals(base64_encode('Hello World!'), (string)$body);
        // ...getContents() does not.
        $this->assertEquals('Hello World!', $body->getContents());
    }

    public function testReadWriteSeekTellEof()
    {
        $body = new Body('Hello World!');
        $this->assertSame(0, $body->tell());
        $this->assertEquals('Hello', $body->read(5));
        $this->assertSame(5, $body->tell());
        $this->assertFalse($body->eof());
        $body->seek(0);
        $this->assertSame(0, $body->tell());
        $body->write('Howdy');
        $this->assertEquals('Howdy World!', $body->getContents());
        $body->rewind();
        $this->assertSame(0, $body->tell());
    }

    public function testIsReadableIsWritableIsSeekable()
    {
        $body = new Body('Hello World!');
        $this->assertTrue($body->isReadable());
        $this->assertTrue($body->isWritable());
        $this->assertTrue($body->isSeekable());
    }

    public function testGetMetadata()
    {
        $body = new Body('Hello World!');
        $this->assertIsArray($body->getMetadata());
        $this->assertTrue($body->getMetadata('seekable'));
        $this->assertNull($body->getMetadata('no-such-key'));
    }

    public function testCloseAndDetach()
    {
        $body = new Body('Hello World!');
        $resource = $body->detach();
        $this->assertIsResource($resource);
        $this->assertEquals('', $body->getContents());

        $body2 = new Body('Hello World!');
        $body2->close();
        $this->assertEquals('', $body2->getContents());
    }

    public function testGetSizeStillReturnsZeroForEmptyBody()
    {
        $body = new Body();
        $this->assertSame(0, $body->getSize());
    }

    public function testCloneDuplicatesUnderlyingStreamIndependently()
    {
        $body  = new Body('Hello World!');
        $clone = clone $body;
        $clone->write('!!!');
        $clone->rewind();

        $this->assertEquals('Hello World!', $body->getContents());
        $this->assertEquals('Hello World!!!!', $clone->getContents());
    }

    public function testReadOnDetachedBodyThrowsExceptionExtendingRuntimeException()
    {
        // Finding 5: Pop\Http\Exception now extends \RuntimeException, so PSR-7's
        // documented @throws \RuntimeException for StreamInterface::read() is honored.
        $body = new Body();
        $this->expectException(\Pop\Http\Exception::class);
        $body->read(1);
    }

    public function testWriteOnDetachedBodyIsCatchableAsRuntimeException()
    {
        $body = new Body();

        try {
            $body->write('x');
            $this->fail('Expected a RuntimeException to be thrown.');
        } catch (\RuntimeException $e) {
            $this->assertInstanceOf(\Pop\Http\Exception::class, $e);
        }
    }

}
