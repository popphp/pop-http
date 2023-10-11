<?php

namespace Pop\Http\Test\Client\Handler;

use Pop\Http\Client\Handler\CurlMulti;
use PHPUnit\Framework\TestCase;

class CurlMultiTest extends TestCase
{

    public function testConstructor()
    {
        $curl = new CurlMulti();
        $this->assertInstanceOf('Pop\Http\Client\Handler\CurlMulti', $curl);
    }

    public function testSetOption()
    {
        $curl = new CurlMulti();
        $curl->setOption(CURLMOPT_MAXCONNECTS, 10);
        $this->assertTrue($curl->hasOption(CURLMOPT_MAXCONNECTS));
        $this->assertEquals(10, $curl->getOption(CURLMOPT_MAXCONNECTS));
    }

    public function testError()
    {
        $curl = new CurlMulti();
        $this->assertEquals(0, $curl->getErrorNumber());
        $this->assertEquals('No error', $curl->getErrorMessage());
    }

    public function testRemoveOption()
    {
        $curl = new CurlMulti();
        $curl->setOption(CURLMOPT_MAXCONNECTS, 10);
        $this->assertTrue($curl->hasOption(CURLMOPT_MAXCONNECTS));
        $curl->removeOption(CURLMOPT_MAXCONNECTS);
        $this->assertFalse($curl->hasOption(CURLMOPT_MAXCONNECTS));
    }

}