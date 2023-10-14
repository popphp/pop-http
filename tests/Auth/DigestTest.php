<?php

namespace Pop\Http\Test\Auth;

use Pop\Http\Auth\Digest;
use PHPUnit\Framework\TestCase;

class DigestTest extends TestCase
{

    public function testConstructor()
    {
        $digest = new Digest('test@realm.com', 'username', 'password', '/uri', '2e7e5ca372e848abe4b7e9ba6fa56ccf');
        $this->assertInstanceOf('Pop\Http\Auth\Digest', $digest);
        $this->assertTrue($digest->hasRealm());
        $this->assertTrue($digest->hasUsername());
        $this->assertTrue($digest->hasPassword());
        $this->assertTrue($digest->hasUri());
        $this->assertTrue($digest->hasNonce());
        $this->assertEquals('test@realm.com', $digest->getRealm());
        $this->assertEquals('username', $digest->getUsername());
        $this->assertEquals('password', $digest->getPassword());
        $this->assertEquals('/uri', $digest->getUri());
        $this->assertEquals('2e7e5ca372e848abe4b7e9ba6fa56ccf', $digest->getNonce());
    }


    public function testCreate()
    {
        $digest = Digest::create('test@realm.com', 'username', 'password', '/uri', '2e7e5ca372e848abe4b7e9ba6fa56ccf');
        $this->assertInstanceOf('Pop\Http\Auth\Digest', $digest);
        $this->assertTrue($digest->hasRealm());
        $this->assertTrue($digest->hasUsername());
        $this->assertTrue($digest->hasPassword());
        $this->assertTrue($digest->hasUri());
        $this->assertTrue($digest->hasNonce());
        $this->assertEquals('test@realm.com', $digest->getRealm());
        $this->assertEquals('username', $digest->getUsername());
        $this->assertEquals('password', $digest->getPassword());
        $this->assertEquals('/uri', $digest->getUri());
        $this->assertEquals('2e7e5ca372e848abe4b7e9ba6fa56ccf', $digest->getNonce());
    }

    public function testCreateFromHeader()
    {
        $header = 'Authorization: Digest username="username", realm="test@realm.com", nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf", uri="/uri", response="ad005ee586d13b4625477cd18869769a"';
        $digest = Digest::createFromHeader($header, 'password');
        $this->assertInstanceOf('Pop\Http\Auth\Digest', $digest);
        $this->assertTrue($digest->hasRealm());
        $this->assertTrue($digest->hasUsername());
        $this->assertTrue($digest->hasPassword());
        $this->assertTrue($digest->hasUri());
        $this->assertTrue($digest->hasNonce());
        $this->assertEquals('test@realm.com', $digest->getRealm());
        $this->assertEquals('username', $digest->getUsername());
        $this->assertEquals('password', $digest->getPassword());
        $this->assertEquals('/uri', $digest->getUri());
        $this->assertEquals('2e7e5ca372e848abe4b7e9ba6fa56ccf', $digest->getNonce());
    }

    public function testCreateFromHeaderException1()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $header = 'Authorization: BADSCHEME username="username", realm="test@realm.com", nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf", uri="/uri", response="ad005ee586d13b4625477cd18869769a"';
        $digest = Digest::createFromHeader($header, 'password');
    }

    public function testCreateFromHeaderException2()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $header = 'Authorization: Digest username="username", nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf", uri="/uri", response="ad005ee586d13b4625477cd18869769a"';
        $digest = Digest::createFromHeader($header, 'password');
    }

    public function testCreateFromHeaderException3()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $header = 'Authorization: Digest realm="test@realm.com", nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf", uri="/uri", response="ad005ee586d13b4625477cd18869769a"';
        $digest = Digest::createFromHeader($header, 'password');
    }

    public function testCreateFromHeaderException4()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $header = 'Authorization: Digest username="username", realm="test@realm.com", uri="/uri", response="ad005ee586d13b4625477cd18869769a"';
        $digest = Digest::createFromHeader($header, 'password');
    }

    public function testCreateFromHeaderException5()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $header = 'Authorization: Digest username="username", realm="test@realm.com", nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf", response="ad005ee586d13b4625477cd18869769a"';
        $digest = Digest::createFromHeader($header, 'password');
    }

    public function testSettersAndGetters()
    {
        $digest = new Digest('test.realm.com', 'username', 'password', '/uri', '2e7e5ca372e848abe4b7e9ba6fa56ccf');

        $digest->setNonceCount('3');
        $digest->setClientNonce ('43e3e36b5af0f26f5a322a5b833aa8af');
        $digest->setMethod('POST');
        $digest->setAlgorithm(Digest::ALGO_MD5_SESS);
        $digest->setQop(Digest::QOP_AUTH_INT);
        $digest->setOpaque('7e1f8f875c8f4cb05fdb768150e327e1');
        $digest->setBody('<html></html>');
        $digest->setStale(true);
        $this->assertTrue($digest->hasNonceCount());
        $this->assertTrue($digest->hasClientNonce());
        $this->assertTrue($digest->hasMethod());
        $this->assertTrue($digest->hasAlgorithm());
        $this->assertTrue($digest->hasQop());
        $this->assertTrue($digest->hasOpaque());
        $this->assertTrue($digest->hasBody());
        $this->assertTrue($digest->isStale());
        $this->assertEquals('3', $digest->getNonceCount());
        $this->assertEquals('43e3e36b5af0f26f5a322a5b833aa8af', $digest->getClientNonce());
        $this->assertEquals('POST', $digest->getMethod());
        $this->assertEquals(Digest::ALGO_MD5_SESS, $digest->getAlgorithm());
        $this->assertEquals(Digest::QOP_AUTH_INT, $digest->getQop());
        $this->assertEquals('7e1f8f875c8f4cb05fdb768150e327e1', $digest->getOpaque());
        $this->assertEquals('<html></html>', $digest->getBody());
    }

    public function testParseWwwAuth1()
    {
        $wwwAuth    = <<<HDR
WWW-Authenticate: Digest realm="testrealm@host.com",
                        qop="auth-int",
                        nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf",
                        opaque="7e1f8f875c8f4cb05fdb768150e327e1",
                        stale="true"
HDR;
        $digest = Digest::createFromWwwAuth($wwwAuth, 'username', 'password', '/uri');


        $this->assertEquals('testrealm@host.com', $digest->getRealm());
        $this->assertEquals('username', $digest->getUsername());
        $this->assertEquals('password', $digest->getPassword());
        $this->assertEquals('/uri', $digest->getUri());
        $this->assertEquals('2e7e5ca372e848abe4b7e9ba6fa56ccf', $digest->getNonce());
        $this->assertEquals('7e1f8f875c8f4cb05fdb768150e327e1', $digest->getOpaque());
        $this->assertEquals(Digest::QOP_AUTH_INT, $digest->getQop());
        $this->assertTrue($digest->hasWwwAuth());
        $this->assertTrue($digest->isStale());
        $this->assertTrue(str_contains($digest->getWwwAuth(), 'WWW-Authenticate:'));
    }

    public function testParseWwwAuth2()
    {
        $wwwAuth    = <<<HDR
WWW-Authenticate: Digest realm="testrealm@host.com",
                        qop="auth",
                        nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf",
                        opaque="7e1f8f875c8f4cb05fdb768150e327e1",
                        stale="true"
HDR;
        $digest = Digest::createFromWwwAuth($wwwAuth, 'username', 'password', '/uri');
        $this->assertEquals(Digest::QOP_AUTH, $digest->getQop());
    }

    public function testParseWwwAuthException1()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $wwwAuth    = <<<HDR
WWW-Authenticate: BADSCHEME realm="testrealm@host.com",
                        qop="auth,auth-int",
                        nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf",
                        opaque="7e1f8f875c8f4cb05fdb768150e327e1"
HDR;
        $digest = Digest::createFromWwwAuth($wwwAuth, 'username', 'password', '/uri');
    }

    public function testParseWwwAuthException2()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $wwwAuth    = <<<HDR
WWW-Authenticate: Digest qop="auth,auth-int",
                        nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf",
                        opaque="7e1f8f875c8f4cb05fdb768150e327e1"
HDR;
        $digest = Digest::createFromWwwAuth($wwwAuth, 'username', 'password', '/uri');
    }

    public function testParseWwwAuthException3()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $wwwAuth    = <<<HDR
WWW-Authenticate: Digest realm="testrealm@host.com",
                        qop="auth,auth-int",
                        opaque="7e1f8f875c8f4cb05fdb768150e327e1"
HDR;
        $digest = Digest::createFromWwwAuth($wwwAuth, 'username', 'password', '/uri');
    }

    public function testParseWwwAuthException4()
    {
        $this->expectException('Pop\Http\Auth\Exception');
        $wwwAuth    = <<<HDR
WWW-Authenticate: Digest realm="testrealm@host.com",
                        qop="auth,auth-int",
                        nonce="2e7e5ca372e848abe4b7e9ba6fa56ccf"
HDR;
        $digest = Digest::createFromWwwAuth($wwwAuth, 'username', 'password', '/uri');
    }

    public function testIsValid1()
    {
        $digest = Digest::create('test@realm.com', 'username', '', '/uri', '2e7e5ca372e848abe4b7e9ba6fa56ccf');
        $digest->setAlgorithm(Digest::ALGO_MD5_SESS)
            ->setQop(Digest::QOP_AUTH_INT);

        $this->assertFalse($digest->isValid());
        $this->assertTrue($digest->hasErrors());
        $this->assertCount(4, $digest->getErrors());
    }

    public function testCreateDigestString()
    {
        $digest = new Digest('test@realm.com', 'username', 'password', '/uri', '2e7e5ca372e848abe4b7e9ba6fa56ccf');
        $digestString = (string)$digest;
        $this->assertTrue(str_contains($digestString, 'username'));
        $this->assertTrue(str_contains($digestString, 'test@realm.com'));
        $this->assertTrue(str_contains($digestString, '2e7e5ca372e848abe4b7e9ba6fa56ccf'));
        $this->assertTrue(str_contains($digestString, '/uri'));
        $this->assertTrue(str_contains($digestString, '48ffc1e3e6ecc0350eab1e0d27c5198e'));
    }

    public function testCreateDigestStringMd5Sess()
    {
        $digest = new Digest('test@realm.com', 'username', 'password', '/uri', '2e7e5ca372e848abe4b7e9ba6fa56ccf');
        $digest->setAlgorithm(Digest::ALGO_MD5_SESS)
            ->setQop(Digest::QOP_AUTH_INT)
            ->setClientNonce('43e3e36b5af0f26f5a322a5b833aa8af')
            ->setBody('<html></html>');

        $digestString = (string)$digest;

        $this->assertTrue(str_contains($digestString, 'username'));
        $this->assertTrue(str_contains($digestString, 'test@realm.com'));
        $this->assertTrue(str_contains($digestString, '2e7e5ca372e848abe4b7e9ba6fa56ccf'));
        $this->assertTrue(str_contains($digestString, '/uri'));
        $this->assertTrue(str_contains($digestString, '426c2071879d6d8b7970775f2b98146d'));
    }

}