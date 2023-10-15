<?php

namespace Pop\Http\Test\Client\Handler\Curl;

use Pop\Http\Client\Handler\Curl;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{

    public function testValidOption()
    {
        $this->assertTrue(Curl\Options::isValidOption('-i'));
        $this->assertFalse(Curl\Options::isValidOption('-BAD'));
    }

    public function testCommandOption()
    {
        $this->assertTrue(Curl\Options::isCommandOption('-i'));
        $this->assertFalse(Curl\Options::isCommandOption('-BAD'));
    }

    public function testPhpOption()
    {
        $this->assertTrue(Curl\Options::isPhpOption('CURLOPT_ABSTRACT_UNIX_SOCKET'));
        $this->assertFalse(Curl\Options::isPhpOption('-BAD'));
    }

    public function testValueOption()
    {
        $this->assertTrue(Curl\Options::isValueOption('--abstract-unix-socket'));
        $this->assertFalse(Curl\Options::isValueOption('-BAD'));
    }

    public function testBooleanOption()
    {
        $this->assertTrue(Curl\Options::isBooleanOption('-i'));
        $this->assertFalse(Curl\Options::isBooleanOption('--abstract-unix-socket'));
    }

    public function testGetCommandOptions()
    {
        $this->assertEquals('CURLOPT_ABSTRACT_UNIX_SOCKET', Curl\Options::getCommandOptions()['--abstract-unix-socket']);
        $this->assertEquals('CURLOPT_ABSTRACT_UNIX_SOCKET', Curl\Options::getCommandOption('--abstract-unix-socket'));
    }

    public function testGetPhpOptions()
    {
        $this->assertEquals('--abstract-unix-socket', Curl\Options::getPhpOptions()['CURLOPT_ABSTRACT_UNIX_SOCKET']);
        $this->assertEquals('--abstract-unix-socket', Curl\Options::getPhpOption('CURLOPT_ABSTRACT_UNIX_SOCKET'));
    }

    public function testGetValueOptions()
    {
        $this->assertEquals(CURL_HTTP_VERSION_1_1, Curl\Options::getValueOptions()['--http1.1']);
        $this->assertEquals(CURL_HTTP_VERSION_1_1, Curl\Options::getValueOption('--http1.1'));
    }

    public function testGetOptionValue()
    {
        $this->assertTrue(Curl\Options::hasOptionValueByName('CURLOPT_ABSTRACT_UNIX_SOCKET'));
        $this->assertTrue(Curl\Options::hasOptionNameByValue(10264));
        $this->assertEquals(10264, Curl\Options::getOptionValueByName('CURLOPT_ABSTRACT_UNIX_SOCKET'));
        $this->assertEquals('CURLOPT_ABSTRACT_UNIX_SOCKET', Curl\Options::getOptionNameByValue(10264));
    }

    public function testGetOmitOptions()
    {
        $this->assertTrue(is_array(Curl\Options::getOmitOptions()));
        $this->assertTrue(Curl\Options::isOmitOption('-k'));
    }

}
