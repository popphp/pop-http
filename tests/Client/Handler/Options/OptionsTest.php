<?php

namespace Pop\Http\Test\Client\Handler\Options;

use Pop\Http\Client\Handler\Curl;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{

    public function testValidOption()
    {
        $this->assertTrue(Curl\Options::isValidOption('-i'));
        $this->assertFalse(Curl\Options::isValidOption('-BAD'));
    }

    public function testCliOption()
    {
        $this->assertTrue(Curl\Options::isCliOption('-i'));
        $this->assertFalse(Curl\Options::isCliOption('-BAD'));
    }

    public function testPhpOption()
    {
        $this->assertTrue(Curl\Options::isPhpOption('CURLOPT_ABSTRACT_UNIX_SOCKET'));
        $this->assertFalse(Curl\Options::isPhpOption('-BAD'));
    }

    public function testSpecialOption()
    {
        $this->assertTrue(Curl\Options::isSpecialOption('--digest'));
        $this->assertFalse(Curl\Options::isSpecialOption('-BAD'));
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

}
