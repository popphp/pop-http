<?php

namespace Pop\Http\Test;

use Pop\Http\Client;
use Pop\Http\Client\Handler\Curl;
use Pop\Http\Client\Middleware\CallableMiddleware;
use Pop\Http\Auth;
use Pop\Http\Test\Client\Middleware\ForeignRequest;
use Pop\Http\Test\Client\Middleware\ForeignResponse;
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

    public function testDirectDataSurvivesUnrelatedAddOption()
    {
        // Regression test: syncRequestFromOptions() must not silently discard a direct
        // addData() call made after construction when an unrelated option is later added -
        // it must merge options onto the request, not wholesale-replace it.
        $client = new Client('http://localhost/', ['data' => ['a' => 1]]);
        $client->addData('b', 2);
        $client->addOption('headers', ['X-Foo' => 'bar']);

        $this->assertEquals(['a' => 1, 'b' => 2], $client->getData());
    }

    public function testDirectQuerySurvivesUnrelatedAddOption()
    {
        $client = new Client('http://localhost/', ['query' => ['a' => 1]]);
        $client->addQuery('b', 2);
        $client->addOption('headers', ['X-Foo' => 'bar']);

        $this->assertEquals(['a' => 1, 'b' => 2], $client->getQuery());
    }

    public function testAddOptionDataMergesWithExistingData()
    {
        // addOption('data', [...]) after a direct setData() call must merge, not overwrite,
        // mirroring the old prepare()-time reconciliation's addData()-when-already-present behavior.
        $client = new Client();
        $client->setData(['a' => 1]);
        $client->addOption('data', ['c' => 3]);

        $this->assertEquals(['a' => 1, 'c' => 3], $client->getData());
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
        $this->assertEquals('bar', $client->getHeader('foo')->getValue());
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
        $this->assertEquals('bar', $client->getHeader('foo')->getValue());
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
        $this->assertEquals('bar', $client->getHeader('foo')->getValue());
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

    public function testSetFilesReplacesRatherThanAccumulates()
    {
        // Regression test: setFiles() must have replace semantics, consistent with
        // setData()/setHeaders()/setQuery(); addFile() is the accumulating counterpart.
        $client = new Client();
        $client->setFiles(__DIR__ . '/tmp/data.json');
        $this->assertCount(1, $client->getFiles());

        $client->setFiles(__DIR__ . '/tmp/data.xml');

        $this->assertCount(1, $client->getFiles());
        $this->assertEquals(__DIR__ . '/tmp/data.xml', $client->getFile('file1'));
        $this->assertFalse(in_array(__DIR__ . '/tmp/data.json', $client->getFiles()));
    }

    public function testFileException()
    {
        $this->expectException('Pop\Http\Exception');
        $client = new Client();
        $client->setFiles(__DIR__ . '/tmp/bad.json');
    }

    public function testGetFilesAndHasFilesDoNotRecurse()
    {
        // Regression test: getFiles() and hasFiles() must not call each other
        // in a cycle (getFiles() calling hasFiles() which calls getFiles()...),
        // which would crash every invocation with infinite recursion.
        $client = new Client();
        $this->assertFalse($client->hasFiles());
        $this->assertNull($client->getFiles());

        $client->addFile(__DIR__ . '/tmp/data.json');
        $this->assertTrue($client->hasFiles());
        $this->assertIsArray($client->getFiles());
        $this->assertCount(1, $client->getFiles());
        $this->assertEquals(__DIR__ . '/tmp/data.json', $client->getFiles()['file1']);
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
        $this->assertInstanceOf('Pop\Http\Body', $client->getBody());
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

        $response = $promise->wait();
        $this->assertTrue(str_contains($response->getParsedResponse(), '<html'));
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

    public function testToCurlCommandSslVersionOptionResolvesToCorrectFlag()
    {
        // Regression test: CURLOPT_SSLVERSION (=32) and CURLSSLOPT_AUTO_CLIENT_CERT (=32)
        // share the same integer value. CURLOPT_SSLVERSION is the real, settable curl
        // option with a CLI equivalent; CURLSSLOPT_AUTO_CLIENT_CERT is an unrelated
        // bitmask value used only by CURLOPT_SSL_OPTIONS. Options::getOptionNameByValue()
        // must resolve the value back to CURLOPT_SSLVERSION, not the unrelated alias -
        // otherwise toCurlCommand() silently emits the wrong flag (or drops it).
        $client = new Client('https://example.com/', new Curl());
        $client->getHandler()->setOption(CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);

        $command = $client->toCurlCommand();

        $this->assertStringNotContainsString('--ssl-auto-client-cert', $command);
        $this->assertStringContainsString('-2', $command);
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

    public function testOptionsSetBeforeARequestExistsAreAppliedOnPrepare()
    {
        // Regression test: syncRequestFromOptions() is a no-op without a request, so options set
        // on a client that has no request yet were silently dropped forever. prepare() now syncs
        // once the request is guaranteed to exist.
        $client = new Client(new Client\Handler\Curl());
        $this->assertFalse($client->hasRequest());

        $client->addOption('data', ['foo' => 'bar']);
        $client->addOption('headers', ['X-Custom' => 'abc']);
        $client->addOption('query', ['q' => '1']);

        $client->prepare('http://localhost:8000/t');

        $this->assertEquals(['foo' => 'bar'], $client->getData());
        $this->assertTrue($client->hasHeader('X-Custom'));
        $this->assertEquals('abc', $client->getRequest()->getHeaderValueAsString('X-Custom'));
        $this->assertEquals(['q' => '1'], $client->getQuery());
    }

    public function testSetOptionsBeforeARequestExistsAreAppliedOnPrepare()
    {
        $client = new Client(new Client\Handler\Curl());
        $client->setOptions([
            'method' => 'POST',
            'type'   => Client\Request::JSON,
            'data'   => ['foo' => 'bar'],
        ]);

        $client->prepare('http://localhost:8000/t');

        $this->assertEquals('POST', $client->getRequest()->getMethod());
        $this->assertEquals(Client\Request::JSON, $client->getType());
        $this->assertEquals(['foo' => 'bar'], $client->getData());
    }

    public function testPrepareSyncIsIdempotentAndDoesNotDuplicateData()
    {
        // The sync uses per-key addData()/addQuery() merges, so running again from prepare()
        // (on top of the constructor's sync) must not duplicate or wipe anything.
        $client = new Client('http://localhost:8000/t', ['data' => ['foo' => 'bar'], 'query' => ['q' => '1']]);
        $client->addData('extra', 'kept');

        $client->prepare();
        $client->prepare();

        $this->assertEquals(['foo' => 'bar', 'extra' => 'kept'], $client->getData());
        $this->assertEquals(['q' => '1'], $client->getQuery());
    }

    public function testExplicitPrepareMethodArgumentOutranksMethodOption()
    {
        $client = new Client('http://localhost:8000/t', ['method' => 'POST']);
        $client->prepare(null, 'PUT');
        $this->assertEquals('PUT', $client->getRequest()->getMethod());

        $client->prepare();
        $this->assertEquals('POST', $client->getRequest()->getMethod());
    }

    public function testStaticFactoryMethodsStillApplyDataOptions()
    {
        // The already-passing case (URI + options arriving together) must not regress
        $client  = new Client('http://localhost:8000/post.php', [
            'method' => 'POST',
            'type'   => Client\Request::URLENCODED,
            'data'   => ['foo' => 'bar'],
        ]);
        $request = $client->render();

        $this->assertTrue(str_contains($request, 'POST /post.php HTTP/1.1'));
        $this->assertTrue(str_contains($request, 'foo=bar'));
    }

    public function testRenderIncludesMultipartBodyParts()
    {
        // Regression test: multipart data is prepared lazily (the body is never buffered into
        // $dataContent), so render() has to build the displayed body itself - otherwise the
        // rendered request carries a multipart Content-Type header with an empty body.
        $client  = new Client('http://localhost:8000/post.php', [
            'method' => 'POST',
            'type'   => Client\Request::MULTIPART,
            'data'   => ['foo' => 'bar', 'baz' => 'qux'],
        ]);
        $request = $client->render();

        $this->assertTrue(str_contains($request, 'POST /post.php HTTP/1.1'));
        $this->assertTrue(str_contains($request, 'Content-Type: multipart/form-data; boundary='));
        $this->assertStringContainsString('Content-Disposition: form-data; name="foo"' . "\r\n\r\nbar\r\n", $request);
        $this->assertStringContainsString('Content-Disposition: form-data; name="baz"' . "\r\n\r\nqux\r\n", $request);

        // The boundary declared in the header and the one used in the body must agree
        $this->assertEquals(1, preg_match('/boundary=([^\s;\r\n]+)/', $request, $matches));
        $boundary = $matches[1];
        $this->assertStringContainsString('--' . $boundary . "\r\n", $request);
        $this->assertStringEndsWith('--' . $boundary . "--\r\n", $request);
    }

    public function testPrepareDoesNotResurrectDataOverriddenBySetData()
    {
        // Regression test: prepare() must not re-run the options sync on a request that already
        // existed - doing so silently merged the stale 'data' option back over an explicit
        // setData() call made after construction.
        $client = new Client('http://localhost:8000/t', ['data' => ['foo' => 'bar']]);
        $client->setData(['baz' => 'qux']);
        $client->prepare();

        $this->assertEquals(['baz' => 'qux'], $client->getData());
    }

    public function testPrepareDoesNotResurrectDataRemovedByRemoveData()
    {
        $client = new Client('http://localhost:8000/t', ['data' => ['foo' => 'bar']]);
        $client->removeData('foo');
        $client->prepare();

        $this->assertEmpty($client->getData());
    }

    public function testPrepareDoesNotResurrectFilesRemovedAfterConstruction()
    {
        $file   = __DIR__ . '/tmp/data.json';
        $client = new Client('http://localhost:8000/t', ['files' => [$file]]);
        $this->assertNotEmpty($client->getData());

        $client->setData(['foo' => 'bar']);
        $client->prepare();

        $this->assertEquals(['foo' => 'bar'], $client->getData());
    }

    public function testRenderDoesNotDeprecateOnTypelessRequest()
    {
        // Regression test: render() used to check ($this->request->isMultipart()) before
        // ($this->request->hasData()), and isMultipart() does strtolower($this->requestType) -
        // which is null on a plain request with no type set, triggering a PHP 8.4 deprecation
        // (and throwing outright under a strict error handler). hasData() must short-circuit
        // first, same as every other caller in this codebase that combines these two checks.
        $client = new Client('http://localhost/');

        set_error_handler(function ($errno, $errstr) {
            throw new \ErrorException($errstr, 0, $errno);
        }, E_DEPRECATED);

        try {
            $request = $client->render();
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('GET / HTTP/1.1', $request);
    }

    public function testSetRequestSyncsOptionsSetBeforehand()
    {
        // Regression test: options set via setOptions() before a request is injected via a
        // direct setRequest() call were silently dropped, because Client didn't override
        // setRequest() to sync options the way prepare()'s request-materializing branch does.
        $c = new Client();
        $c->setOptions(['data' => ['foo' => 'bar'], 'headers' => ['X-Test' => '1'], 'query' => ['q' => '1']]);
        $c->setRequest(new Client\Request('http://localhost:8000/t'));
        $c->prepare();

        $this->assertEquals(['foo' => 'bar'], $c->getData());
        $this->assertTrue($c->hasHeader('X-Test'));
        $this->assertEquals(['q' => '1'], $c->getQuery());
    }

    public function testClientExceptionImplementsClientExceptionInterface()
    {
        $this->assertInstanceOf('Psr\Http\Client\ClientExceptionInterface', new \Pop\Http\Client\Exception());
    }

    public function testHandlerExceptionImplementsNetworkExceptionInterface()
    {
        $request   = new \Pop\Http\Client\Request('http://localhost/');
        $exception = new \Pop\Http\Client\Handler\Exception('Error: network failure.', 0, null, 0, $request);

        $this->assertInstanceOf('Psr\Http\Client\NetworkExceptionInterface', $exception);
        $this->assertSame($request, $exception->getRequest());
    }

    public function testHandlerExceptionGetRequestThrowsWhenNeverPrepared()
    {
        $exception = new \Pop\Http\Client\Handler\Exception('Error: not prepared.');
        $this->expectException('LogicException');
        $exception->getRequest();
    }

    public function testImplementsClientInterface()
    {
        $this->assertInstanceOf('Psr\Http\Client\ClientInterface', new \Pop\Http\Client());
    }

    public function testSendRequestReturnsResponseInterface()
    {
        $client  = new \Pop\Http\Client(new \Pop\Http\Client\Handler\Stream());
        $request = new \Pop\Http\Client\Request('http://localhost/');

        $response = $client->sendRequest($request);
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
    }

    public function testSendRequestThrowsRequestExceptionWithNoUri()
    {
        $client  = new \Pop\Http\Client();
        $request = new \Pop\Http\Client\Request();

        $this->expectException('Pop\Http\Client\RequestException');
        $client->sendRequest($request);
    }

    public function testSendRequestCopiesMultiValueHeadersFromForeignRequest()
    {
        // Regression test: sendRequest()'s foreign-RequestInterface conversion looped
        // addHeader($name, $value) once per value for the same header name, but addHeader()
        // overwrites rather than accumulates - so only the last value of a multi-value header
        // survived. The fix mirrors withAddedHeader()'s pattern: seed with the first value via
        // addHeader(), then append the rest via the Header object's addValue().
        $uri = $this->createStub(\Psr\Http\Message\UriInterface::class);
        $uri->method('__toString')->willReturn('http://localhost/');

        $body = $this->createStub(\Psr\Http\Message\StreamInterface::class);
        $body->method('__toString')->willReturn('');

        $request = $this->createStub(\Psr\Http\Message\RequestInterface::class);
        $request->method('getUri')->willReturn($uri);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getHeaders')->willReturn(['X-Foo' => ['one', 'two']]);
        $request->method('getBody')->willReturn($body);

        $client = new \Pop\Http\Client(new \Pop\Http\Client\Handler\Stream());
        $client->sendRequest($request);

        $this->assertEquals(['one', 'two'], $client->getRequest()->getHeader('X-Foo'));
    }

    public function testSendDispatchesThroughMockHandler()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200, 'body' => 'Hello World!']));

        $client   = new Client('http://localhost/', $mock);
        $response = $client->send();

        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
        $this->assertEquals(200, $response->getCode());
        $this->assertEquals('Hello World!', $response->getBody()->getContent());
        $this->assertCount(1, $mock->getRequests());
    }

    public function testGetRequestsSnapshotsEachRequestOnReusedClient()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200, 'body' => 'A']));
        $mock->queue(new Client\Response(['code' => 200, 'body' => 'B']));

        $client = new Client($mock);
        $client->get('http://localhost/a');
        $client->get('http://localhost/b');

        $requests = $mock->getRequests();
        $this->assertCount(2, $requests);
        $this->assertEquals('http://localhost/a', $requests[0]->getUriAsString());
        $this->assertEquals('http://localhost/b', $requests[1]->getUriAsString());
        $this->assertNotEquals($requests[0]->getUriAsString(), $requests[1]->getUriAsString());
    }

    public function testSendAsyncDispatchesThroughMockHandler()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 201]));

        $client  = new Client('http://localhost/', $mock, ['async' => true]);
        $promise = $client->send();

        $this->assertInstanceOf('Pop\Http\Promise', $promise);

        $response = $promise->wait();
        $this->assertEquals(201, $response->getCode());
        $this->assertCount(1, $mock->getRequests());
    }

    public function testSendRequestDispatchesThroughMockHandler()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client   = new Client($mock);
        $request  = new Client\Request('http://localhost/');
        $response = $client->sendRequest($request);

        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testSendRequestThrowsQueuedFailureThroughMockHandler()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Handler\Exception('Simulated network failure.'));

        $client  = new Client($mock);
        $request = new Client\Request('http://localhost/');

        $this->expectException('Pop\Http\Client\Handler\Exception');
        $client->sendRequest($request);
    }

    public function testAddMiddlewareIsFluentAndAccumulates()
    {
        $client     = new Client();
        $middleware = new CallableMiddleware(fn($request, $handler) => $handler->handle($request));

        $result = $client->addMiddleware($middleware);

        $this->assertSame($client, $result);
        $this->assertTrue($client->hasMiddleware());
        $this->assertCount(1, $client->getMiddleware());
        $this->assertSame($middleware, $client->getMiddleware()[0]);
    }

    public function testAddMiddlewareWrapsPlainCallable()
    {
        $client = new Client();
        $client->addMiddleware(fn($request, $handler) => $handler->handle($request));

        $this->assertInstanceOf('Pop\Http\Client\Middleware\CallableMiddleware', $client->getMiddleware()[0]);
    }

    public function testMiddlewareWrapsSendWithMockHandler()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $order  = [];
        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(function($request, $handler) use (&$order) {
            $order[] = 'before';
            $response = $handler->handle($request);
            $order[] = 'after';
            return $response;
        });

        $response = $client->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertEquals(['before', 'after'], $order);
        $this->assertCount(1, $mock->getRequests());
    }

    public function testMiddlewareWrapsSendAsyncViaPromiseWait()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $ran    = false;
        $client = new Client('http://localhost/', $mock, ['async' => true]);
        $client->addMiddleware(function($request, $handler) use (&$ran) {
            $ran = true;
            return $handler->handle($request);
        });

        $promise  = $client->sendAsync();
        $response = $promise->wait();

        $this->assertEquals(200, $response->getCode());
        $this->assertTrue($ran);
        $this->assertCount(1, $mock->getRequests());
    }

    public function testMiddlewareWrapsSendRequest()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $ran    = false;
        $client = new Client($mock);
        $client->addMiddleware(function($request, $handler) use (&$ran) {
            $ran = true;
            return $handler->handle($request);
        });

        $request  = new Client\Request('http://localhost/');
        $response = $client->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($ran);
        $this->assertCount(1, $mock->getRequests());
    }

    public function testMiddlewareCanShortCircuitSendWithoutDispatching()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 500]));

        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(function($request, $handler) {
            return new Client\Response(['code' => 304]);
        });

        $response = $client->send();

        $this->assertEquals(304, $response->getCode());
        $this->assertCount(0, $mock->getRequests());
    }

    public function testDispatchRequestDoesNotResyncRemovedDataFromOptions()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/', $mock, ['data' => ['foo' => 'bar']]);
        $client->removeData('foo');

        $client->send();

        $lastRequest = $mock->getLastRequest();
        $this->assertFalse($lastRequest->getData()->hasData('foo'));
    }

    public function testDispatchRequestDoesNotOverrideExplicitMethodArgumentWithOption()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client($mock, ['method' => 'POST']);
        $client->send('http://localhost/', 'GET');

        $lastRequest = $mock->getLastRequest();
        $this->assertEquals('GET', $lastRequest->getMethod());
    }

    public function testDispatchRequestDoesNotResyncStaleSetDataAfterExplicitSetData()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/', $mock, ['data' => ['foo' => 'bar']]);
        $client->setData(['baz' => 'qux']);

        $client->send();

        $lastRequest = $mock->getLastRequest();
        $this->assertFalse($lastRequest->getData()->hasData('foo'));
        $this->assertTrue($lastRequest->getData()->hasData('baz'));
    }

    public function testDispatchRequestDoesNotReAddRemovedHeaderFromOptions()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/', $mock, ['headers' => ['X-Foo' => 'bar']]);
        $client->removeHeader('X-Foo');

        $client->send();

        $lastRequest = $mock->getLastRequest();
        $this->assertFalse($lastRequest->hasHeader('X-Foo'));
    }

    public function testMiddlewareSubstitutedForeignPsr7RequestIsDispatched()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/original', $mock);
        $client->addMiddleware(function($request, $handler) {
            $foreign = new ForeignRequest('GET', 'http://localhost/substituted');
            $foreign = $foreign->withHeader('X-Substituted', 'yes');
            return $handler->handle($foreign);
        });

        $client->send();

        $lastRequest = $mock->getLastRequest();
        $this->assertEquals('http://localhost/substituted', $lastRequest->getUriAsString());
        $this->assertEquals('yes', $lastRequest->getHeaderValueAsString('X-Substituted'));
    }

    public function testMiddlewareReturningForeignResponseTypeThrowsClearException()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(function($request, $handler) {
            return new ForeignResponse(200);
        });

        $this->expectException('Pop\Http\Client\Exception');
        $client->send();
    }

    public function testExceptionThrownByMiddlewarePropagatesThroughSend()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(function($request, $handler) {
            throw new \RuntimeException('Simulated middleware failure.');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Simulated middleware failure.');
        $client->send();
    }

    public function testRetryMiddlewareRetriesTransientNetworkFailureThenSucceeds()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Handler\Exception('Simulated network blip.'));
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(
            (new \Pop\Http\Client\Middleware\RetryMiddleware(3))
                ->setSleeper(function (float $seconds): void {})
        );

        $response = $client->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(2, $mock->getRequests());
    }

    public function testRetryMiddlewareRetriesRetryableStatusThenSucceeds()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 503]));
        $mock->queue(new Client\Response(['code' => 200]));

        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(
            (new \Pop\Http\Client\Middleware\RetryMiddleware(3))
                ->setSleeper(function (float $seconds): void {})
        );

        $response = $client->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(2, $mock->getRequests());
    }

    public function testLoggingMiddlewareLogsOutcomeThroughSend()
    {
        $mock = new Client\Handler\Mock();
        $mock->queue(new Client\Response(['code' => 200]));

        $logger = new \Pop\Http\Test\Client\Middleware\RecordingLogger();
        $client = new Client('http://localhost/', $mock);
        $client->addMiddleware(new \Pop\Http\Client\Middleware\LoggingMiddleware($logger));

        $response = $client->send();

        $this->assertEquals(200, $response->getCode());
        $this->assertCount(1, $logger->records);
        $this->assertEquals('info', $logger->records[0]['level']);
        $this->assertEquals(200, $logger->records[0]['context']['status']);
    }

}
