<?php

namespace Pop\Http\Test\Body;

use Pop\Http\Body\Multipart;
use Pop\Http\Body\Exception;
use PHPUnit\Framework\TestCase;

class MultipartTest extends TestCase
{

    public function testToArrayWithScalarFields()
    {
        $result = Multipart::toArray([
            'username' => 'admin',
            'password' => '123456',
        ]);

        $this->assertEquals('admin', $result['username']);
        $this->assertEquals('123456', $result['password']);
    }

    public function testToArrayWithArrayField()
    {
        $result = Multipart::toArray([
            'colors' => ['Red', 'Green'],
        ]);

        $this->assertEquals('Red', $result['colors[0]']);
        $this->assertEquals('Green', $result['colors[1]']);
    }

    public function testToArrayWithFileByPath()
    {
        $file = sys_get_temp_dir() . '/pop-http-multipart-test-' . uniqid() . '.txt';
        file_put_contents($file, 'file contents');

        $result = Multipart::toArray([
            'upload' => ['filename' => $file, 'contentType' => 'text/plain'],
        ]);

        $this->assertInstanceOf('CURLFile', $result['upload']);
        $this->assertEquals($file, $result['upload']->getFilename());
        $this->assertEquals('text/plain', $result['upload']->getMimeType());

        unlink($file);
    }

    public function testToArrayWithFileByRawContents()
    {
        $result = Multipart::toArray([
            'upload' => ['filename' => 'test.pdf', 'contents' => 'raw pdf bytes', 'mimeType' => 'application/pdf'],
        ]);

        $this->assertInstanceOf('CURLFile', $result['upload']);
        $this->assertEquals('raw pdf bytes', file_get_contents($result['upload']->getFilename()));
        $this->assertEquals('application/pdf', $result['upload']->getMimeType());
        $this->assertEquals('test.pdf', $result['upload']->getPostFilename());
    }

    public function testToArrayGuessesMimeTypeWhenNotProvided()
    {
        $file = sys_get_temp_dir() . '/pop-http-multipart-test-' . uniqid() . '.json';
        file_put_contents($file, '{}');

        $result = Multipart::toArray([
            'upload' => ['filename' => $file],
        ]);

        $this->assertEquals('application/json', $result['upload']->getMimeType());

        unlink($file);
    }

    public function testToArrayRawContentsFileIsInSystemTempDir()
    {
        $result = Multipart::toArray([
            'upload' => ['filename' => 'test.pdf', 'contents' => 'raw pdf bytes', 'mimeType' => 'application/pdf'],
        ]);

        $this->assertFileExists($result['upload']->getFilename());
        $this->assertStringStartsWith(sys_get_temp_dir(), $result['upload']->getFilename());
    }

    public function testBuildRendersScalarFields()
    {
        $body = Multipart::build(['username' => 'admin'], 'TESTBOUNDARY');

        $rendered = $body->getContent();
        $this->assertStringContainsString('--TESTBOUNDARY', $rendered);
        $this->assertStringContainsString('Content-Disposition: form-data; name="username"', $rendered);
        $this->assertStringContainsString("\r\n\r\nadmin\r\n", $rendered);
        $this->assertStringContainsString('--TESTBOUNDARY--', $rendered);
    }

    public function testBuildRendersArrayFields()
    {
        $body = Multipart::build(['colors' => ['Red', 'Green']], 'TESTBOUNDARY');

        $rendered = $body->getContent();
        $this->assertStringContainsString('Content-Disposition: form-data; name="colors[0]"', $rendered);
        $this->assertStringContainsString('Content-Disposition: form-data; name="colors[1]"', $rendered);
        $this->assertStringContainsString("\r\n\r\nRed\r\n", $rendered);
        $this->assertStringContainsString("\r\n\r\nGreen\r\n", $rendered);
    }

    public function testBuildRendersFileFieldWithoutBase64()
    {
        $file = sys_get_temp_dir() . '/pop-http-multipart-test-' . uniqid() . '.txt';
        file_put_contents($file, 'raw binary-ish content');

        $body = Multipart::build([
            'upload' => ['filename' => $file, 'contentType' => 'text/plain'],
        ], 'TESTBOUNDARY');

        $rendered = $body->getContent();
        $this->assertStringContainsString(
            'Content-Disposition: form-data; name="upload"; filename="' . basename($file) . '"', $rendered
        );
        $this->assertStringContainsString('Content-Type: text/plain', $rendered);
        // The raw content must appear as-is, NOT base64-encoded.
        $this->assertStringContainsString("\r\n\r\nraw binary-ish content\r\n", $rendered);
        $this->assertStringNotContainsString(base64_encode('raw binary-ish content'), $rendered);

        unlink($file);
    }

    public function testGenerateBoundaryIsUnique()
    {
        $this->assertNotEquals(Multipart::generateBoundary(), Multipart::generateBoundary());
    }

    public function testBuildEscapesQuotesInFieldName()
    {
        $body = Multipart::build(['field"name' => 'value'], 'TESTBOUNDARY');
        $this->assertStringContainsString('name="field\\"name"', $body->getContent());
    }

    public function testBuildStripsCrlfFromFieldName()
    {
        $body = Multipart::build(["field\r\nname" => 'value'], 'TESTBOUNDARY');
        $rendered = $body->getContent();
        $this->assertStringContainsString('name="fieldname"', $rendered);
        // Verify no raw CRLF inside the name attribute value
        $this->assertStringNotContainsString('name="field\r\nname"', $rendered);
    }

    public function testBuildStripsCrlfFromContentType()
    {
        // Regression test: a CRLF in a file field's contentType must not be able to inject
        // a header line of its own into the rendered part.
        $file = sys_get_temp_dir() . '/pop-http-multipart-ctinject-' . uniqid() . '.txt';
        file_put_contents($file, 'content');

        try {
            $body = Multipart::build([
                'upload' => ['filename' => $file, 'contentType' => "text/plain\r\nX-Injected: yes"],
            ], 'TESTBOUNDARY');
            $rendered = $body->getContent();

            $this->assertStringNotContainsString("\r\nX-Injected: yes", $rendered);
            $this->assertStringContainsString('Content-Type: text/plainX-Injected: yes', $rendered);
            // The part must still have exactly the expected header lines, then the blank line
            $this->assertStringContainsString(
                "Content-Disposition: form-data; name=\"upload\"; filename=\"" . basename($file) . "\"\r\n" .
                "Content-Type: text/plainX-Injected: yes\r\n\r\ncontent\r\n",
                $rendered
            );
        } finally {
            unlink($file);
        }
    }

    public function testBuildStripsCrlfFromContentTypeSuppliedViaMimeTypeKey()
    {
        $body = Multipart::build([
            'upload' => ['filename' => 'inline.txt', 'contents' => 'abc', 'mimeType' => "text/plain\r\nX-Bad: 1"],
        ], 'TESTBOUNDARY');

        $this->assertStringNotContainsString("\r\nX-Bad: 1", $body->getContent());
    }

    public function testBuildPreservesQuotesInContentType()
    {
        // Regression test: the CRLF-stripping applied to $mimeType must not also backslash-escape
        // quotes - Content-Type is an unquoted header value, so a quoted parameter such as
        // charset="utf-8" has to render verbatim.
        $body = Multipart::build([
            'upload' => [
                'filename'    => 'inline.txt',
                'contents'    => 'abc',
                'contentType' => 'text/plain; charset="utf-8"'
            ],
        ], 'TESTBOUNDARY');
        $rendered = $body->getContent();

        $this->assertStringContainsString('Content-Type: text/plain; charset="utf-8"' . "\r\n", $rendered);
        $this->assertStringNotContainsString('charset=\\"utf-8\\"', $rendered);
        // The field name and filename still get their quote-escaping, since they are quoted params
        $this->assertStringContainsString('name="upload"; filename="inline.txt"', $rendered);
    }

    public function testBuildStripsCrlfFromCallerSuppliedBoundary()
    {
        // Defense in depth: an explicitly-supplied boundary is never rendered verbatim, so it
        // cannot break out of the boundary line and inject headers/parts of its own.
        $body     = Multipart::build(['a' => 'b'], "MYBOUND\r\nX-Bad: yes");
        $rendered = $body->getContent();

        $this->assertStringNotContainsString("\r\nX-Bad: yes", $rendered);
        $this->assertStringStartsWith("--MYBOUNDX-Bad: yes\r\n", $rendered);
        $this->assertStringEndsWith("--MYBOUNDX-Bad: yes--\r\n", $rendered);
    }

    public function testBuildThrowsWhenFileIsUnreadable()
    {
        $file = sys_get_temp_dir() . '/pop-http-multipart-test-' . uniqid() . '.txt';
        file_put_contents($file, 'content');
        chmod($file, 0000);

        try {
            Multipart::build([
                'upload' => ['filename' => $file, 'contentType' => 'text/plain'],
            ], 'TESTBOUNDARY');
            $this->fail('Expected Exception to be thrown for unreadable file');
        } catch (Exception $e) {
            $this->assertStringContainsString("Unable to open the file", $e->getMessage());
        } finally {
            chmod($file, 0644);
            unlink($file);
        }
    }

    public function testParseScalarFields()
    {
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"username\"\r\n\r\n" .
            "admin\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertEquals('admin', $result['username']);
    }

    public function testParseArrayFields()
    {
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"colors[]\"\r\n\r\n" .
            "Red\r\n" .
            "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"colors[]\"\r\n\r\n" .
            "Green\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertEquals(['Red', 'Green'], $result['colors']);
    }

    public function testParseArrayFieldsBareBracketConventionFromHtmlForm()
    {
        // Simulates a plain HTML form submission (or any other multipart producer) using the
        // conventional bare 'name[]' style - this must keep working regardless of this codebase's
        // own build()/toArray() convention.
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"colors[]\"\r\n\r\n" .
            "Red\r\n" .
            "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"colors[]\"\r\n\r\n" .
            "Green\r\n" .
            "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"colors[]\"\r\n\r\n" .
            "Blue\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertEquals(['Red', 'Green', 'Blue'], $result['colors']);
    }

    public function testParseArrayFieldsIndexedConventionOutOfOrder()
    {
        // Indexed 'name[N]' parts arriving out of order in the raw body must still land in the
        // correct index position - proving the indexed form is order-independent, not just
        // encounter-order like the bare '[]' convention.
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"colors[1]\"\r\n\r\n" .
            "Green\r\n" .
            "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"colors[0]\"\r\n\r\n" .
            "Red\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertSame(['Red', 'Green'], $result['colors']);
        $this->assertSame('Red', $result['colors'][0]);
        $this->assertSame('Green', $result['colors'][1]);
    }

    public function testParseFileField()
    {
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"upload\"; filename=\"test.txt\"\r\n" .
            "Content-Type: text/plain\r\n\r\n" .
            "file body content\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertEquals('test.txt', $result['upload']['filename']);
        $this->assertEquals('text/plain', $result['upload']['contentType']);
        $this->assertEquals('file body content', $result['upload']['contents']);
    }

    public function testParseRoundTripsWithBuild()
    {
        $data = ['username' => 'admin', 'colors' => ['Red', 'Green']];
        $body = Multipart::build($data, 'ROUNDTRIP');

        $result = Multipart::parse($body->getContent(), 'ROUNDTRIP');
        $this->assertEquals('admin', $result['username']);
        $this->assertEquals(['Red', 'Green'], $result['colors']);
    }

    public function testParseRoundTripsArrayFieldIndexedOrderPreserved()
    {
        // build() now emits indexed keys (colors[0], colors[1], colors[2]) matching toArray()'s
        // convention, and parse() must place each value back at its correct index - confirming the
        // array field round-trips through build()/parse() with order intact.
        $data = ['colors' => ['Red', 'Green', 'Blue']];
        $body = Multipart::build($data, 'ROUNDTRIP2');

        $result = Multipart::parse($body->getContent(), 'ROUNDTRIP2');
        $this->assertSame(['Red', 'Green', 'Blue'], $result['colors']);
    }

    public function testParsePreservesTrailingCrlfInFieldValue()
    {
        // A field value with embedded blank lines should preserve them through parse
        $value = "line1\r\n\r\nline2";
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"text\"\r\n\r\n" .
            $value . "\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertEquals($value, $result['text']);
    }

    public function testParseRoundTripWithEscapedQuotesInFieldName()
    {
        // A field name with a quote character should escape it during build and unescape it during parse
        $data = ['field"name' => 'value'];
        $body = Multipart::build($data, 'QUOTETEST');

        $result = Multipart::parse($body->getContent(), 'QUOTETEST');
        $this->assertEquals('value', $result['field"name']);
        $this->assertArrayHasKey('field"name', $result);
    }

    public function testParseFieldValueWithTrailingCrlf()
    {
        // Regression test: field value ending in \r\n should not be stripped
        $value = "hello\r\n";
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; name=\"text\"\r\n\r\n" .
            $value . "\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertEquals($value, $result['text']);
    }

    public function testParseFilenameBeforeNameAttribute()
    {
        // Regression test: parse should correctly extract name even when filename appears first
        $raw = "--TESTBOUNDARY\r\n" .
            "Content-Disposition: form-data; filename=\"test.txt\"; name=\"upload\"\r\n" .
            "Content-Type: text/plain\r\n\r\n" .
            "file content\r\n" .
            "--TESTBOUNDARY--\r\n";

        $result = Multipart::parse($raw, 'TESTBOUNDARY');
        $this->assertArrayHasKey('upload', $result);
        $this->assertEquals('test.txt', $result['upload']['filename']);
        $this->assertEquals('file content', $result['upload']['contents']);
    }

}
