<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Data;
use PHPUnit\Framework\TestCase;
use Pop\Http\Client\Request;

class DataTest extends TestCase
{

    public function testConstructor()
    {
        $data = new Data(['test' => '<b>Hello World & Stuff!</b>'], ['strip_tags', 'htmlentities']);
        $this->assertInstanceOf('Pop\Http\Client\Data', $data);

        $data = new Data(['test' => '<b>Hello World & Stuff!</b>'], 'strip_tags');
        $this->assertInstanceOf('Pop\Http\Client\Data', $data);
    }

    public function testAddAndRemoveData()
    {
        $data = new Data();
        $data->addData('test', 123)
            ->addData(['foo' => 'bar']);

        $this->assertTrue($data->hasData());
        $this->assertCount(2, $data->getData());
        $this->assertEquals(123, $data->getData()['test']);
        $this->assertEquals('bar', $data->getData()['foo']);

        $data->removeData('test');
        $this->assertCount(1, $data->getData());
        $this->assertNull($data->getData('test'));

        $data->removeAllData();
        $this->assertCount(0, $data->getData());
    }

    public function testRequest()
    {
        $request = new Request();
        $data = new Data([
            'foo' => 'bar'
        ]);
        $this->assertFalse($data->hasRequest());
        $data->setRequest($request);
        $this->assertTrue($data->hasRequest());
        $this->assertInstanceOf('Pop\Http\Client\Request', $data->getRequest());
    }

    public function testIsPrepared1()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $this->assertTrue($data->isPrepared());
    }

    public function testIsPrepared2()
    {
        $data = new Data([0 => 'This is a string'], null, null);
        $data->prepare();
        $this->assertTrue($data->isPrepared());
    }

    public function testIsPrepared3()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $data->prepare();
        $this->assertTrue($data->isPrepared());
        $data->reset();
        $this->assertFalse($data->isPrepared());
    }

    public function testHasData()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $this->assertTrue($data->hasData('foo'));
    }

    public function testDataString()
    {
        $data = new Data();
        $data->setData(['This is a string']);
        $this->assertEquals(['POP_CLIENT_REQUEST_RAW_DATA' => 'This is a string'], $data->getData());
        $this->assertTrue($data->hasRawData());
        $this->assertEquals(16, $data->getRawDataLength());
    }

    public function testGetMimeTypes()
    {
        $mimeTypes = Data::getMimeTypes();
        $this->assertTrue(is_array($mimeTypes));
        $this->assertEquals('image/jpeg', $mimeTypes['jpg']);
        $this->assertEquals('image/jpeg', Data::getMimeType('jpg'));
    }

    public function testHasMimeTypes()
    {
        $this->assertTrue(Data::hasMimeType('jpg'));
    }

    public function testGetDefaultMimeTypes()
    {
        $this->assertEquals('application/octet-stream', Data::getDefaultMimeType());
    }

    public function testJsonStringValueIsAlwaysEncoded()
    {
        $request = new Request('http://localhost/', 'POST', null, Request::JSON);
        $data    = new Data(['flag' => 'true'], null, $request);
        $data->prepare();

        // 'true' as a JSON *value* must be encoded as the JSON string "true",
        // not silently treated as the already-encoded boolean literal true.
        // (prepareJson() renders with JSON_PRETTY_PRINT, matching existing behavior.)
        $this->assertEquals(json_encode(['flag' => 'true'], JSON_PRETTY_PRINT), $data->getDataContent());
    }

    public function testExplicitRawDataStillBypassesEncoding()
    {
        $request = new Request('http://localhost/', 'POST', null, Request::JSON);
        $request->setRawData(true);
        $preEncoded = '{"already":"encoded"}';
        $data       = new Data($preEncoded, null, $request);
        $data->prepare();

        $this->assertEquals($preEncoded, $data->getDataContent());
    }

    public function testMultipartPreparationIsLazyAndOnlyDeclaresTheBoundary()
    {
        $request = new Request('http://localhost/', 'POST', null, Request::MULTIPART);
        $data    = new Data(['username' => 'admin'], null, $request);
        $data->prepare();

        // The multipart body is NEVER rendered here - Curl hands the curl-native array shape
        // straight to CURLOPT_POSTFIELDS and Stream renders the string itself, exactly once.
        $this->assertEmpty($data->getDataContent());
        $this->assertFalse($data->hasDataContent());

        // The boundary declaration on the Content-Type header is the only observable effect
        $contentType = $request->getHeaderValueAsString('Content-Type');
        $this->assertStringStartsWith('multipart/form-data; boundary=', $contentType);
        $this->assertStringContainsString('----PopHttpBoundary', $contentType);

        // Content-Length is left to the transport: curl builds the multipart framing itself for
        // an array POSTFIELDS, and PHP's http:// stream wrapper derives it from 'content'.
        $this->assertFalse($request->hasHeader('Content-Length'));
    }

    public function testMultipartPreparationClearsContentFromAPriorPreparation()
    {
        $request = new Request('http://localhost/', 'POST', null, Request::URLENCODED);
        $data    = new Data(['username' => 'admin'], null, $request);
        $data->prepare();

        $this->assertNotEmpty($data->getDataContent());
        $this->assertTrue($request->hasHeader('Content-Length'));

        $request->setRequestType(Request::MULTIPART);
        $data->reset();
        $data->prepare();

        $this->assertEmpty($data->getDataContent());
        $this->assertFalse($request->hasHeader('Content-Length'));
        $this->assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderValueAsString('Content-Type'));
    }

    public function testMultipartPreparationDoesNotBufferFileContentIntoMemory()
    {
        $file = tempnam(sys_get_temp_dir(), 'pop-http-lazy-multipart-');
        // 8MB, comfortably larger than any incidental allocation during prepare()
        $size = 8 * 1024 * 1024;
        file_put_contents($file, str_repeat('X', $size));

        $request = new Request('http://localhost/', 'POST', null, Request::MULTIPART);
        $data    = new Data([], null, $request);

        // Deliberately measured with memory_get_usage() (current usage), not
        // memory_get_peak_usage(): a peak delta only registers when the allocation exceeds the
        // process's existing high-water mark, so whatever else the suite ran first could silently
        // neuter this assertion. Current usage is unaffected by that ordering. Note setData()
        // prepares as a side effect, so it has to sit inside the measured window too.
        gc_collect_cycles();
        $usageBefore = memory_get_usage();
        $data->setData(['file1' => ['filename' => $file, 'contentType' => 'application/octet-stream']]);
        $data->prepare();
        $usageAfter = memory_get_usage();

        unlink($file);

        // Before the lazy-multipart fix this delta was ~8MB - the whole file, rendered and held
        // in $dataContent only to be thrown away unread. It must now stay a small fraction of
        // the file size.
        $this->assertLessThan($size / 4, $usageAfter - $usageBefore);
        $this->assertEmpty($data->getDataContent());
    }

}
