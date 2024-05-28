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

    public function testType()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $data->setType(Request::JSON);
        $this->assertTrue($data->hasType());
        $this->assertEquals(Request::JSON, $data->getType());
    }

    public function testIsJson()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $data->setType(Request::JSON);
        $this->assertTrue($data->isJson());
    }

    public function testIsXml()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $data->setType(Request::XML);
        $this->assertTrue($data->isXml());
    }

    public function testIsUrlEncoded()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $data->setType(Request::URLENCODED);
        $this->assertTrue($data->isUrlEncoded());
    }

    public function testIsMultipart()
    {
        $data = new Data([
            'foo' => 'bar'
        ]);
        $data->setType(Request::MULTIPART);
        $this->assertTrue($data->isMultipart());
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

}
