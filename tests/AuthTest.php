<?php

namespace Pop\Http\Test;

use Pop\Http\Auth;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{

    public function testCreateBasic()
    {
        $auth = Auth::createBasic('username', 'password');
        $this->assertEquals('Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=', (string)$auth);
    }

    public function testCreateBearer()
    {
        $auth = Auth::createBearer('sdc0usdjcioksdcsidc0hsinodk');
        $this->assertEquals('Authorization: Bearer sdc0usdjcioksdcsidc0hsinodk', (string)$auth);
    }

    public function testCreateKey()
    {
        $auth = Auth::createKey('sd0c98sdc-fhygn9b90f-fgb90fgb', 'X-Api-Key', 'api:');
        $this->assertEquals('X-Api-Key: api:sd0c98sdc-fhygn9b90f-fgb90fgb', (string)$auth);
    }

    public function testParseBasic()
    {
        $header = Auth::parse('Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=');
        $this->assertEquals('Authorization', $header->getHeader());
        $this->assertEquals('Basic', $header->getScheme());
        $this->assertEquals('username', $header->getUsername());
        $this->assertEquals('password', $header->getPassword());
    }

    public function testParseBearer()
    {
        $header = Auth::parse('Authorization: Bearer sdc0usdjcioksdcsidc0hsinodk');
        $this->assertEquals('Authorization', $header->getHeader());
        $this->assertEquals('Bearer', $header->getScheme());
        $this->assertEquals('sdc0usdjcioksdcsidc0hsinodk', $header->getToken());
    }

    public function testParseKey()
    {
        $header = Auth::parse('X-Api-Key: api:sd0c98sdc-fhygn9b90f-fgb90fgb', 'api:');
        $this->assertEquals('X-Api-Key', $header->getHeader());
        $this->assertEquals('api:', $header->getScheme());
        $this->assertEquals('sd0c98sdc-fhygn9b90f-fgb90fgb', $header->getToken());
    }

    public function testGetters1()
    {
        $auth = Auth::createBasic('username', 'password');

        $this->assertTrue($auth->hasScheme());
        $this->assertTrue($auth->hasUsername());
        $this->assertTrue($auth->hasPassword());
        $this->assertEquals('Authorization', $auth->getHeader());
        $this->assertEquals('Basic', $auth->getScheme());
        $this->assertEquals('username', $auth->getUsername());
        $this->assertEquals('password', $auth->getPassword());
    }

    public function testGetters2()
    {
        $auth = Auth::createBearer('sdc0usdjcioksdcsidc0hsinodk');

        $this->assertTrue($auth->hasScheme());
        $this->assertTrue($auth->hasToken());
        $this->assertEquals('sdc0usdjcioksdcsidc0hsinodk', $auth->getToken());
    }

    public function testGetAuthHeader()
    {
        $auth = Auth::createBasic('username', 'password');
        $this->assertInstanceOf('Pop\Mime\Part\Header', $auth->createAuthHeader());
        $this->assertInstanceOf('Pop\Mime\Part\Header', $auth->getAuthHeader());
        $this->assertTrue($auth->hasAuthHeader());
    }

    public function testGetAuthHeaderAssocArray()
    {
        $auth = Auth::createBasic('username', 'password');
        $header = $auth->getAuthHeaderAsArray();
        $this->assertTrue(isset($header['Authorization']));
        $this->assertEquals('Basic dXNlcm5hbWU6cGFzc3dvcmQ=', $header['Authorization']);
    }

    public function testGetAuthHeaderNumericArray()
    {
        $auth = Auth::createBasic('username', 'password');
        $header = $auth->getAuthHeaderAsArray(false);
        $this->assertTrue(isset($header[0]));
        $this->assertTrue(isset($header[1]));
        $this->assertEquals('Authorization', $header[0]);
        $this->assertEquals('Basic dXNlcm5hbWU6cGFzc3dvcmQ=', $header[1]);
    }

    public function testGetAuthHeaderAsString()
    {
        $auth = Auth::createBasic('username', 'password');
        $this->assertEquals("Authorization: Basic dXNlcm5hbWU6cGFzc3dvcmQ=\r\n", $auth->getAuthHeaderAsString(true));
    }

    public function testCreateAuthHeaderException1()
    {
        $this->expectException('Pop\Http\Exception');
        $auth = new Auth('Authorization', 'Basic');
        $auth->createAuthHeader();
    }

    public function testCreateAuthHeaderException2()
    {
        $this->expectException('Pop\Http\Exception');
        $auth = new Auth('Authorization', 'Bearer');
        $auth->createAuthHeader();
    }

}