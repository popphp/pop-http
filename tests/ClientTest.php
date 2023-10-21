<?php

namespace Pop\Http\Test;

use Pop\Http\Client;
use Pop\Http\Auth;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    public function testConstructor()
    {
        $client = new Client(
            new Client\Request(),
            new Client\Response(),
            new Client\Handler\Stream(),
            Auth::createBearer('123456'),
            ['base_uri' => 'http://localhost']
        );
        $this->assertInstanceOf('Pop\Http\Client', $client);
        $this->assertTrue($client->hasRequest());
        $this->assertTrue($client->hasResponse());
        $this->assertTrue($client->hasHandler());
        $this->assertTrue($client->hasAuth());
        $this->assertTrue($client->hasOptions());
        $this->assertTrue($client->hasOption('base_uri'));
        $this->assertInstanceOf('Pop\Http\Client\Request', $client->getRequest());
        $this->assertInstanceOf('Pop\Http\Client\Response', $client->getResponse());
        $this->assertInstanceOf('Pop\Http\Client\Handler\Stream', $client->getHandler());
        $this->assertInstanceOf('Pop\Http\Auth', $client->getAuth());
        $this->assertEquals('http://localhost', $client->getOption('base_uri'));
        $this->assertCount(1, $client->getOptions());
    }

    public function testMultihandler()
    {
        $client = new Client(new Client\Request('http://localhost'), new Client\Handler\CurlMulti());
        $this->assertTrue($client->hasMultiHandler());
        $this->assertInstanceOf('Pop\Http\Client\Handler\CurlMulti', $client->getMultiHandler());
    }

    public function testCreateMulti()
    {
        $multiHandler = Client::createMulti([
            'http://localhost/test1.php',
            'http://localhost/test2.php',
            'http://localhost/test3.php'
        ]);
        $this->assertInstanceOf('Pop\Http\Client\Handler\CurlMulti', $multiHandler);
    }

    public function testPrepareException()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client();
        $client->prepare();
    }

    public function testAddOptions()
    {
        $client = new Client();
        $client->addOptions([
            'async' => true,
            'auto'  => true
        ]);
        $this->assertTrue($client->hasOption('async'));
        $this->assertTrue($client->hasOption('auto'));
    }

    public function testMethod1()
    {
        $client = new Client();
        $client->setMethod('POST');
        $this->assertTrue($client->hasMethod());
        $this->assertEquals('POST', $client->getMethod());
    }

    public function testMethod2()
    {
        $client = new Client(new Client\Request());
        $client->setMethod('POST');
        $this->assertTrue($client->hasMethod());
        $this->assertEquals('POST', $client->getMethod());
    }

    public function testPrepareCurl()
    {
        $client = new Client(
            [
                'base_uri'          => 'http://localhost',
                'method'            => 'POST',
                'headers'           => ['Authorization' => 'Bearer 123456'],
                'user_agent'        => 'popphp/pop-http 1.0',
                'data'              => ['foo' => 'bar'],
                'query'             => ['baz' => '123'],
                'type'              => 'application/x-www-form-urlencoded',
                'verify_peer'       => true,
                'allow_self_signed' => false
            ]
        );
        $client->prepare('/foo/bar');
        $this->assertTrue($client->hasRequest());
        $this->assertEquals('POST', $client->getRequest()->getMethod());
        $this->assertEquals('popphp/pop-http 1.0', $client->getHandler()->getOption(CURLOPT_USERAGENT));
        $this->assertTrue($client->hasHandler());
        $this->assertTrue($client->getRequest()->hasData());
        $this->assertTrue($client->getRequest()->hasQuery());
        $this->assertEquals('123', $client->getRequest()->getQuery('baz'));
    }

    public function testPrepareStream()
    {
        $client = new Client(
            new Client\Handler\Stream(),
            [
                'base_uri'          => 'http://localhost',
                'method'            => 'POST',
                'headers'           => ['Authorization' => 'Bearer 123456'],
                'user_agent'        => 'popphp/pop-http 1.0',
                'data'              => ['foo' => 'bar'],
                'type'              => 'application/x-www-form-urlencoded',
                'verify_peer'       => true,
                'allow_self_signed' => false
            ]
        );
        $client->prepare('/foo/bar');
        $this->assertTrue($client->hasRequest());
        $this->assertEquals('POST', $client->getRequest()->getMethod());
        $this->assertEquals('popphp/pop-http 1.0', $client->getHandler()->getContextOption('http')['user_agent']);
        $this->assertTrue($client->hasHandler());
        $this->assertTrue($client->getRequest()->hasData());
    }

    public function testPrepareFiles()
    {
        $client = new Client(
            [
                'base_uri' => 'http://localhost',
                'method'   => 'POST',
                'files'    => [
                    __DIR__ . '/tmp/data.json',
                    __DIR__ . '/tmp/data.xml',
                ],
            ]
        );
        $client->prepare('/foo/bar');
        $this->assertTrue($client->hasRequest());
        $this->assertTrue($client->getRequest()->hasData());
    }

    public function testIsComplete()
    {
        $response = new Client\Response([
            'code'    => 200,
            'message' => 'OK'
        ]);

        $client = new Client($response);
        $this->assertTrue($client->isComplete());
    }

    public function testIsContinue()
    {
        $response = new Client\Response([
            'code'    => 100,
            'message' => 'Continue'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isContinue());
        $this->assertNull($client2->isContinue());
    }

    public function testIsOk()
    {
        $response = new Client\Response([
            'code'    => 200,
            'message' => 'OK'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isOk());
        $this->assertNull($client2->isOk());
    }

    public function testIsSuccess()
    {
        $response = new Client\Response([
            'code'    => 200,
            'message' => 'OK'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isSuccess());
        $this->assertNull($client2->isSuccess());
    }

    public function testIsRedirect()
    {
        $response = new Client\Response([
            'code'    => 302,
            'message' => 'Redirect'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isRedirect());
        $this->assertNull($client2->isRedirect());
    }

    public function testIsError()
    {
        $response = new Client\Response([
            'code'    => 404,
            'message' => 'Not Found'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isError());
        $this->assertNull($client2->isError());
    }

    public function testIsClientError()
    {
        $response = new Client\Response([
            'code'    => 404,
            'message' => 'Not Found'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isClientError());
        $this->assertNull($client2->isClientError());
    }

    public function testIsServerError()
    {
        $response = new Client\Response([
            'code'    => 500,
            'message' => 'Server Error'
        ]);

        $client  = new Client($response);
        $client2 = new Client();
        $this->assertTrue($client->isServerError());
        $this->assertNull($client2->isServerError());
    }

    public function testData()
    {
        $client = new Client();
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData('foo'));
        $this->assertTrue($client->hasData());
        $this->assertEquals('bar', $client->getData('foo'));
        $this->assertCount(1, $client->getData());
    }

    public function testAddData()
    {
        $client = new Client();
        $client->addData('foo', 'bar');
        $this->assertEquals('bar', $client->getData('foo'));
        $this->assertCount(1, $client->getData());
    }

    public function testRemoveData()
    {
        $client = new Client();
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData('foo'));
        $client->removeData('foo');
        $this->assertFalse($client->hasData('foo'));
    }

    public function testRemoveAllData()
    {
        $client = new Client();
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData());
        $client->removeAllData();
        $this->assertFalse($client->hasData());
    }

    public function testQuery()
    {
        $client = new Client();
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery('foo'));
        $this->assertTrue($client->hasQuery());
        $this->assertEquals('bar', $client->getQuery('foo'));
        $this->assertCount(1, $client->getQuery());
    }

    public function testAddQuery()
    {
        $client = new Client();
        $client->addQuery('foo', 'bar');
        $this->assertEquals('bar', $client->getQuery('foo'));
        $this->assertCount(1, $client->getQuery());
    }

    public function testRemoveQuery()
    {
        $client = new Client();
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery('foo'));
        $client->removeQuery('foo');
        $this->assertFalse($client->hasQuery('foo'));
    }

    public function testRemoveAllQuery()
    {
        $client = new Client();
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery());
        $client->removeAllQuery();
        $this->assertFalse($client->hasQuery());
    }

    public function testFiles()
    {
        $client = new Client();
        $client->setFiles(__DIR__ . '/tmp/data.json');
        $this->assertTrue($client->hasFile('file1'));
        $this->assertTrue($client->hasFiles());
        $this->assertEquals(__DIR__ . '/tmp/data.json', $client->getFile('file1'));
        $this->assertCount(1, $client->getFiles());
    }

    public function testFileException()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client();
        $client->setFiles(__DIR__ . '/tmp/bad.json');
    }

    public function testAddFile()
    {
        $client = new Client();
        $client->addFile(__DIR__ . '/tmp/data.json');
        $client->addFile(__DIR__ . '/tmp/data.xml');
        $this->assertTrue($client->hasFile('file1'));
        $this->assertTrue($client->hasFile('file2'));
        $this->assertTrue($client->hasFiles());
        $this->assertEquals(__DIR__ . '/tmp/data.json', $client->getFile('file1'));
        $this->assertEquals(__DIR__ . '/tmp/data.xml', $client->getFile('file2'));
        $this->assertCount(2, $client->getFiles());
    }

    public function testAddFileException()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client();
        $client->addFile(__DIR__ . '/tmp/bad.json');
    }

    public function testRemoveFile()
    {
        $client = new Client();
        $client->setFiles(__DIR__ . '/tmp/data.json');
        $this->assertTrue($client->hasFile('file1'));
        $client->removeFile('file1');
        $this->assertFalse($client->hasFile('file1'));
    }

    public function testRemoveAllFiles()
    {
        $client = new Client();
        $client->setFiles(__DIR__ . '/tmp/data.json');
        $this->assertTrue($client->hasFiles());
        $client->removeFiles();
        $this->assertFalse($client->hasFiles());
    }

    public function testSetBody()
    {
        $client = new Client(new Client\Request());
        $client->setBody('This is a text body');
        $this->assertTrue($client->hasBody());
        $this->assertInstanceOf('Pop\Mime\Part\Body', $client->getBody());
        $this->assertEquals('This is a text body', $client->getBodyContent());
        $this->assertEquals(19, $client->getBodyContentLength());
    }

    public function testSetBodyException()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client();
        $client->setBody('This is a text body');
    }

    public function testSetBodyFromFile()
    {
        $client = new Client(new Client\Request());
        $client->setBodyFromFile(__DIR__ . '/tmp/data.json');
        $this->assertTrue($client->hasBody());
    }

    public function testSetBodyFromFileException1()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client();
        $client->setBodyFromFile(__DIR__ . '/tmp/data.json');
        $this->assertTrue($client->hasBody());
    }

    public function testSetBodyFromFileException2()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client(new Client\Request());
        $client->setBodyFromFile(__DIR__ . '/tmp/bad.json');
        $this->assertTrue($client->hasBody());
    }

    public function testRemoveBody()
    {
        $client = new Client(new Client\Request());
        $client->setBody('This is a text body');
        $this->assertTrue($client->hasBody());
        $client->removeBody();
        $this->assertFalse($client->hasBody());
    }

    public function testSendForceCustomMethod()
    {
        $client = new Client('http://localhost/', ['method' => 'GET', 'force_custom_method' => true]);
        $client->send();
        $this->assertTrue($client->hasResponse());
    }

    public function testSendAsync()
    {
        $client = new Client('http://localhost/', ['async' => true]);
        $promise = $client->send();
        $this->assertInstanceOf('Pop\Http\Promise', $promise);
    }

    public function testToCurlCommand()
    {
        $client = new Client('http://localhost:8000/post.php');
        $client->setMethod('POST')
            ->setData([
                'foo' => 'bar',
                'baz' => 123
            ]);

        $command = $client->toCurlCommand();
        $this->assertEquals('curl -i -X POST --data "foo=bar&baz=123" "http://localhost:8000/post.php"', $command);
    }

    public function testToCurlCommandException()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client('http://localhost:8000/post.php', new Client\Handler\Stream());
        $client->setMethod('POST')
            ->setData([
                'foo' => 'bar',
                'baz' => 123
            ]);

        $command = $client->toCurlCommand();
    }

    public function testFromCurlCommand()
    {
        $command = 'curl -i -X POST --data "foo=bar&baz=123" "http://localhost:8000/post.php"';
        $client = Client::fromCurlCommand($command);
        $this->assertInstanceOf('Pop\Http\Client', $client);
    }

    public function testMagicCall()
    {
        $client = new Client('http://localhost/');
        $this->assertInstanceOf('Pop\Http\Client\Response', $client->get());
    }

    public function testMagicCallAsync()
    {
        $client = new Client();
        $this->assertInstanceOf('Pop\Http\Promise', $client->getAsync('http://localhost/'));
    }

    public function testMagicCallStatic()
    {
        $this->assertInstanceOf('Pop\Http\Client\Response', Client::get('http://localhost/'));
    }

    public function testMagicCallStaticArguments1()
    {
        $response = Client::get(
            'http://localhost/', new Client\Response(), new Client\Handler\Stream(), Auth::createBearer(123456), ['async' => false]
        );
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
    }

    public function testMagicCallStaticArguments2()
    {
        $response = Client::get(
            new Client\Request('http://localhost/'), new Client\Response(),
            new Client\Handler\Stream(), Auth::createBearer(123456), ['async' => false]
        );
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
    }

    public function testMagicCallStaticAsync()
    {
        $this->assertInstanceOf('Pop\Http\Promise', Client::getAsync('http://localhost/'));
    }

}
