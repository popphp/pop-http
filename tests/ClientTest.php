<?php

namespace Pop\Http\Test;

use Pop\Http\Client;
use Pop\Http\Auth;
use PHPUnit\Framework\TestCase;
use Pop\Http\Uri;
use Pop\Mime\Part\Header;

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

    public function testRemoveOption()
    {
        $client = new Client();
        $client->addOptions([
            'async' => true,
            'auto'  => true
        ]);
        $this->assertTrue($client->hasOption('async'));
        $this->assertTrue($client->hasOption('auto'));
        $client->removeOption('async');
        $this->assertFalse($client->hasOption('async'));
    }

    public function testRemoveOptions()
    {
        $client = new Client();
        $client->addOptions([
            'async' => true,
            'auto'  => true
        ]);
        $this->assertTrue($client->hasOption('async'));
        $this->assertTrue($client->hasOption('auto'));
        $client->removeOptions();
        $this->assertFalse($client->hasOption('async'));
        $this->assertFalse($client->hasOption('auto'));
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

    public function testPrepareBaseUri1()
    {
        $client = new Client(new Client\Request(new Uri('/some-uri')),
            [
                'base_uri' => 'http://localhost'
            ]
        );
        $client->prepare();
        $this->assertTrue($client->hasRequest());
        $this->assertEquals('http://localhost/some-uri', $client->getRequest()->getUriAsString());
    }

    public function testPrepareBaseUri2()
    {
        $client = new Client(new Client\Request(),
            [
                'base_uri' => 'http://localhost'
            ]
        );
        $client->prepare('/foo/bar');
        $this->assertTrue($client->hasRequest());
        $this->assertEquals('http://localhost/foo/bar', $client->getRequest()->getUriAsString());
    }

    public function testPrepareBaseUri3()
    {
        $client = new Client(
            [
                'base_uri' => 'http://localhost/'
            ]
        );
        $client->prepare();
        $this->assertTrue($client->hasRequest());
        $this->assertEquals('http://localhost/', $client->getRequest()->getUriAsString());
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
        $this->assertEquals('123', $client->getRequest()->getQuery()->getData('baz'));
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

    public function testData1()
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

    public function testData2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData('foo'));
        $this->assertTrue($client->hasData());
        $this->assertEquals('bar', $client->getData('foo'));
        $this->assertCount(1, $client->getData());
    }

    public function testAddData1()
    {
        $client = new Client();
        $client->addData('foo', 'bar');
        $this->assertEquals('bar', $client->getData('foo'));
        $this->assertCount(1, $client->getData());
    }

    public function testAddData2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->addData('foo', 'bar');
        $this->assertEquals('bar', $client->getData('foo'));
        $this->assertCount(1, $client->getData());
    }

    public function testRemoveData1()
    {
        $client = new Client();
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData('foo'));
        $client->removeData('foo');
        $this->assertFalse($client->hasData('foo'));
    }

    public function testRemoveData2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData('foo'));
        $client->removeData('foo');
        $this->assertFalse($client->hasData('foo'));
    }

    public function testRemoveAllData1()
    {
        $client = new Client();
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData());
        $client->removeAllData();
        $this->assertFalse($client->hasData());
    }

    public function testRemoveAllData2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setData([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasData());
        $client->removeAllData();
        $this->assertFalse($client->hasData());
    }

    public function testHeader1()
    {
        $client = new Client();
        $client->setHeaders([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertTrue($client->hasHeaders());
        $this->assertEquals('bar', $client->getHeader('foo'));
        $this->assertCount(1, $client->getHeaders());
    }

    public function testHeader2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setHeaders([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertTrue($client->hasHeaders());
        $this->assertEquals('bar', $client->getHeader('foo')->getValue());
        $this->assertCount(1, $client->getHeaders());
    }

    public function testAddHeader1()
    {
        $client = new Client();
        $client->addHeader('foo', 'bar');
        $this->assertTrue($client->hasHeaders());
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertEquals('bar', $client->getHeader('foo'));
        $this->assertCount(1, $client->getHeaders());
    }

    public function testAddHeader2()
    {
        $client = new Client();
        $client->addHeader(new Header('foo', 'bar'));
        $this->assertTrue($client->hasHeaders());
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertEquals('bar', $client->getHeader('foo')->getValue());
        $this->assertCount(1, $client->getHeaders());
    }

    public function testAddHeader3()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->addHeader('foo', 'bar');
        $this->assertTrue($client->hasHeaders());
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertEquals('bar', $client->getHeader('foo')->getValue());
        $this->assertCount(1, $client->getHeaders());
    }

    public function testAddHeaders1()
    {
        $client = new Client();
        $client->addHeaders(['foo' => 'bar']);
        $this->assertTrue($client->hasHeaders());
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertEquals('bar', $client->getHeader('foo'));
        $this->assertCount(1, $client->getHeaders());
    }

    public function testAddHeaders2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->addHeaders(['foo' => 'bar']);
        $this->assertTrue($client->hasHeaders());
        $this->assertTrue($client->hasHeader('foo'));
        $this->assertEquals('bar', $client->getHeader('foo')->getValue());
        $this->assertCount(1, $client->getHeaders());
    }

    public function testRemoveHeader1()
    {
        $client = new Client();
        $client->setHeaders([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasHeader('foo'));
        $client->removeHeader('foo');
        $this->assertFalse($client->hasHeader('foo'));
    }

    public function testRemoveHeader2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setHeaders([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasHeader('foo'));
        $client->removeHeader('foo');
        $this->assertFalse($client->hasHeader('foo'));
    }

    public function testRemoveAllHeaders1()
    {
        $client = new Client();
        $client->setHeaders([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasHeaders());
        $client->removeAllHeaders();
        $this->assertFalse($client->hasHeaders());
    }

    public function testRemoveAllHeaders2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setHeaders([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasHeaders());
        $client->removeAllHeaders();
        $this->assertFalse($client->hasHeaders());
    }

    public function testQuery1()
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

    public function testQuery2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery('foo'));
        $this->assertTrue($client->hasQuery());
        $this->assertEquals('bar', $client->getQuery('foo'));
        $this->assertCount(1, $client->getQuery());
    }

    public function testAddQuery1()
    {
        $client = new Client();
        $client->addQuery('foo', 'bar');
        $this->assertEquals('bar', $client->getQuery('foo'));
        $this->assertCount(1, $client->getQuery());
    }

    public function testAddQuery2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->addQuery('foo', 'bar');
        $this->assertEquals('bar', $client->getQuery('foo'));
        $this->assertCount(1, $client->getQuery());
    }

    public function testRemoveQuery1()
    {
        $client = new Client();
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery('foo'));
        $client->removeQuery('foo');
        $this->assertFalse($client->hasQuery('foo'));
    }

    public function testRemoveQuery2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery('foo'));
        $client->removeQuery('foo');
        $this->assertFalse($client->hasQuery('foo'));
    }

    public function testRemoveAllQuery1()
    {
        $client = new Client();
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery());
        $client->removeAllQuery();
        $this->assertFalse($client->hasQuery());
    }

    public function testRemoveAllQuery2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setQuery([
            'foo' => 'bar'
        ]);
        $this->assertTrue($client->hasQuery());
        $client->removeAllQuery();
        $this->assertFalse($client->hasQuery());
    }

    public function testType1()
    {
        $client = new Client();
        $client->setType(Client\Request::JSON);
        $this->assertTrue($client->hasType());
        $this->assertEquals(Client\Request::JSON, $client->getType());
        $client->removeType();
        $this->assertFalse($client->hasType());
    }

    public function testType2()
    {
        $client = new Client(new Client\Request(new Uri('http://localhost/')));
        $client->setType(Client\Request::JSON);
        $this->assertTrue($client->hasType());
        $this->assertEquals(Client\Request::JSON, $client->getType());
        $client->removeType();
        $this->assertFalse($client->hasType());
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

    public function testReset1()
    {
        $options = [
            'method' => 'GET',
            'query'   => [
                'foo' => 'bar'
            ],
            'data'   => [
                'foo' => 'bar'
            ],
            'files'   => [
                'foo' => 'bar'
            ],
            'headers' => [
                'Authorization' => 'Bearer 123456789'
            ],
            'user_agent' => 'popphp/pop-http'
        ];
        $client = new Client('http://localhost:8000/get.php', $options);

        $this->assertTrue($client->hasOptions());
        $this->assertTrue($client->hasOption('query'));
        $this->assertTrue($client->hasOption('data'));
        $this->assertTrue($client->hasOption('files'));
        $client->reset(true, true);
        $this->assertFalse($client->hasOption('query'));
        $this->assertFalse($client->hasOption('data'));
        $this->assertFalse($client->hasOption('files'));
    }

    public function testReset2()
    {
        $options = [
            'method' => 'GET',
            'query'   => [
                'foo' => 'bar'
            ],
            'data'   => [
                'foo' => 'bar'
            ],
            'headers' => [
                'Authorization' => 'Bearer 123456789'
            ],
            'user_agent' => 'popphp/pop-http'
        ];
        $client = new Client('http://localhost:8000/get.php', $options);
        $client->prepare();

        $this->assertTrue($client->getRequest()->hasHeaders());
        $this->assertTrue($client->getRequest()->hasQuery());
        $client->reset(true, true);
        $this->assertFalse($client->getRequest()->hasHeaders());
        $this->assertFalse($client->getRequest()->hasQuery());
    }

    public function testReset3()
    {
        $options = [
            'method' => 'GET',
            'query'   => [
                'foo' => 'bar'
            ],
            'headers' => [
                'Authorization' => 'Bearer 123456789'
            ],
            'user_agent' => 'popphp/pop-http'
        ];
        $client = new Client('http://localhost:8000/get.php', $options);
        $client->prepare();

        $this->assertTrue($client->hasRequest());
        $this->assertTrue($client->hasOptions());
        $client->reset(true, true, true);
        $this->assertFalse($client->hasRequest());
        $this->assertFalse($client->hasOptions());
    }

    public function testRender1()
    {
        $options = [
            'method' => 'GET',
            'query'   => [
                'foo' => 'bar'
            ],
            'headers' => [
                'Authorization' => 'Bearer 123456789'
            ]
        ];
        $client  = new Client('http://localhost:8000/get.php', $options);
        $request = (string)$client;

        $this->assertTrue(str_contains($request, 'GET /get.php?foo=bar HTTP/1.1'));
        $this->assertTrue(str_contains($request, 'Host: localhost:8000'));
        $this->assertTrue(str_contains($request, 'Authorization: Bearer 123456789'));
    }

    public function testRender2()
    {
        $options = [
            'method' => 'POST',
            'data'   => [
                'foo' => 'bar'
            ],
            'headers' => [
                'Authorization' => 'Bearer 123456789',
                'Accept'        => 'application/json',
            ],
            'type' => Client\Request::URLENCODED
        ];
        $client  = new Client('http://localhost:8000/post.php', $options);
        $request = $client->render();

        $this->assertTrue(str_contains($request, 'POST /post.php HTTP/1.1'));
        $this->assertTrue(str_contains($request, 'Host: localhost:8000'));
        $this->assertTrue(str_contains($request, 'Authorization: Bearer 123456789'));
        $this->assertTrue(str_contains($request, 'Accept: application/json'));
        $this->assertTrue(str_contains($request, 'Content-Type: application/x-www-form-urlencoded'));
        $this->assertTrue(str_contains($request, 'Content-Length: 7'));
        $this->assertTrue(str_contains($request, 'foo=bar'));
    }

    public function testRender3()
    {
        $options = [
            'method' => 'POST',
            'data'   => [
                'foo' => 'bar'
            ],
            'headers' => [
                'Authorization' => 'Bearer 123456789',
                'Accept'        => 'application/json',
            ],
            'type' => Client\Request::URLENCODED,
            'force_custom_method' => true
        ];
        $client  = new Client('http://localhost:8000/post.php', $options);
        $request = $client->render();

        $this->assertTrue(str_contains($request, 'POST /post.php HTTP/1.1'));
        $this->assertTrue(str_contains($request, 'Host: localhost:8000'));
        $this->assertTrue(str_contains($request, 'Authorization: Bearer 123456789'));
        $this->assertTrue(str_contains($request, 'Accept: application/json'));
        $this->assertTrue(str_contains($request, 'Content-Type: application/x-www-form-urlencoded'));
        $this->assertTrue(str_contains($request, 'Content-Length: 7'));
        $this->assertTrue(str_contains($request, 'foo=bar'));
    }

}
