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

}
