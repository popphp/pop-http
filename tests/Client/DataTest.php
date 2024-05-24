<?php

namespace Pop\Http\Test\Client;

use Pop\Http\Client\Data;
use PHPUnit\Framework\TestCase;

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
