<?php

namespace Pop\Http\Test\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Http\Client\Handler\Curl;
use PHPUnit\Framework\TestCase;

class CurlTest extends TestCase
{

    public function testConstructor()
    {
        $curl = new Curl([CURLOPT_RETURNTRANSFER => 1]);
        $this->assertInstanceOf('Pop\Http\Client\Handler\Curl', $curl);
        $this->assertInstanceOf('CurlHandle', $curl->curl());
        $this->assertInstanceOf('CurlHandle', $curl->resource());
        $this->assertInstanceOf('CurlHandle', $curl->getResource());
        $this->assertTrue($curl->hasOption(CURLOPT_RETURNTRANSFER));
        $this->assertEquals(1, $curl->getOption(CURLOPT_RETURNTRANSFER));
        $this->assertTrue(isset($curl->version()['version_number']));
        $this->assertTrue($curl->hasResource());
    }

    public function testCreate()
    {
        $curl = Curl::create('POST');
        $this->assertInstanceOf('Pop\Http\Client\Handler\Curl', $curl);
    }

    public function testSetHttpVersion()
    {
        $curl = new Curl();
        $curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        $this->assertEquals('1.0', $curl->getHttpVersion());
        $curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $this->assertEquals('1.1', $curl->getHttpVersion());
        $curl->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
        $this->assertEquals('2.0', $curl->getHttpVersion());
    }

    public function testForceMethod()
    {
        $curl = new Curl();
        $curl->setMethod('PUT', true);
        $this->assertTrue($curl->hasOption(CURLOPT_CUSTOMREQUEST));
    }

    public function testResponse()
    {
        $curl = new Curl();
        $curl->setResponse(new Response());
        $this->assertTrue($curl->hasResponse());
        $this->assertInstanceOf('Pop\Http\Client\Response', $curl->getResponse());
    }

    public function testError()
    {
        $curl = new Curl();
        $this->assertEquals(0, $curl->getErrorNumber());
        $this->assertEquals('', $curl->getErrorMessage());
    }

    public function testReturnTransfer()
    {
        $curl = new Curl();
        $curl->setReturnTransfer();
        $this->assertTrue($curl->isReturnTransfer());
    }

    public function testReturnHeader()
    {
        $curl = new Curl();
        $curl->setReturnHeader();
        $this->assertTrue($curl->isReturnHeader());
    }

    public function testVerifyPeer()
    {
        $curl = new Curl();
        $curl->setVerifyPeer(true);
        $this->assertTrue($curl->isVerifyPeer());
    }

    public function testAllowSelfSigned()
    {
        $curl = new Curl();
        $curl->allowSelfSigned(false);
        $this->assertFalse($curl->isAllowSelfSigned());
    }

    public function testGetOptions()
    {
        $curl = new Curl();
        $curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $curl->setOption(CURLOPT_HEADER, 1);
        $this->assertCount(2, $curl->getOptions());
    }

    public function testRemoveOption()
    {
        $curl = new Curl();
        $curl->setOption(CURLOPT_RETURNTRANSFER, 1);
        $this->assertTrue($curl->hasOption(CURLOPT_RETURNTRANSFER));
        $curl->removeOption(CURLOPT_RETURNTRANSFER);
        $this->assertFalse($curl->hasOption(CURLOPT_RETURNTRANSFER));
    }

    public function testParseResponse()
    {
        $curl = new Curl([CURLOPT_URL => 'http://localhost/']);
        $curl->setReturnHeader(false);
        $response = $curl->send();
        $this->assertInstanceOf('Pop\Http\Client\Response', $response);
        $this->assertTrue(str_contains($response->getBodyContent(), '<html'));
    }

    public function testReset()
    {
        $curl = new Curl();
        $curl->setResponse(new Response());
        $this->assertTrue($curl->hasResponse());
        $curl->reset();
        $this->assertFalse($curl->hasResponse());
    }

    public function testDisconnect()
    {
        $curl = new Curl();
        $this->assertTrue($curl->hasResource());
        $curl->disconnect();
        $this->assertFalse($curl->hasResource());
    }

    public function testPrepareWithAuth()
    {
        $curl   = new Curl();
        $client = new Client('http://localhost/', Auth::createBearer(123456), $curl);
        $client->getHandler()->prepare($client->getRequest(), $client->getAuth());
        $this->assertTrue($client->getRequest()->hasHeader('Authorization'));
        $this->assertEquals('Authorization: Bearer 123456', $client->getRequest()->getHeaderAsString('Authorization'));
    }

    public function testPrepareWithHeaders1()
    {
        $curl  = new Curl();
        $curl->setOption(CURLOPT_HTTPHEADER, [
            'Authorization: Bearer 1234567890'
        ]);
        $request = new Request('http://localhost/', 'POST');
        $request->addHeader('Content-Type', 'application/json');

        $curl->prepare($request);
        $headers = $curl->getOption(CURLOPT_HTTPHEADER);
        $this->assertCount(1, $headers);
    }

    public function testPrepareWithJsonData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::JSON);
        $request->setData(['foo' => 'bar']);

        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals("{\n    \"foo\": \"bar\"\n}", $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testPrepareWithPostUrlFormData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::URLENCODED);
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testPrepareWithPostMultipartFormData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setRequestType(Request::MULTIPART);
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        // Multipart bodies are now resolved to the curl-native array shape (scalars + CURLFile)
        // via Pop\Http\Body\Multipart::toArray(), letting curl stream file uploads directly from
        // disk instead of pop-http fully buffering the multipart body as a string.
        $this->assertIsArray($curl->getOption(CURLOPT_POSTFIELDS));
        $this->assertEquals(['foo' => 'bar'], $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testPrepareWithPostFormData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setData(['foo' => 'bar']);
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testPrepareWithBodyData()
    {
        $curl    = new Curl();
        $request = new Request('http://localhost/', 'POST');
        $request->setBody('foo=bar');
        $client  = new Client($request, $curl);
        $client->getHandler()->prepare($client->getRequest());
        $this->assertEquals('foo=bar', $curl->getOption(CURLOPT_POSTFIELDS));
    }

    public function testSendException()
    {
        $this->expectException('Pop\Http\Client\Handler\Exception');
        $curl = new Curl([CURLOPT_URL => '$%#%$^#$%$$#']);
        $curl->send();
    }

    public function testMultipartPostFieldsIsArrayNotString()
    {
        $file = sys_get_temp_dir() . '/pop-http-curl-test-' . uniqid() . '.txt';
        file_put_contents($file, 'streamed content');

        $request = new \Pop\Http\Client\Request('http://localhost/', 'POST');
        $request->setData(['upload' => ['filename' => $file, 'contentType' => 'text/plain']])
            ->createAsMultipart();

        $curl = new Curl();
        $curl->prepare($request);

        $this->assertIsArray($curl->getOption(CURLOPT_POSTFIELDS));
        $this->assertInstanceOf('CURLFile', $curl->getOption(CURLOPT_POSTFIELDS)['upload']);

        unlink($file);
    }

    public function testCurlErrorExceptionExposesErrno()
    {
        $curl = new Curl();
        $curl->setOption(CURLOPT_URL, 'http://this-domain-should-not-resolve.invalid/');
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, 1);

        try {
            $curl->send();
            $this->fail('Expected an exception to be thrown');
        } catch (\Pop\Http\Client\Handler\Exception $e) {
            $this->assertGreaterThan(0, $e->getCurlErrno());
        }
    }

    public function testMultipartPostFieldsHasNoContentTypeHeader()
    {
        $file = sys_get_temp_dir() . '/pop-http-curl-test-' . uniqid() . '.txt';
        file_put_contents($file, 'streamed content');

        $request = new \Pop\Http\Client\Request('http://localhost/', 'POST');
        $request->setData(['upload' => ['filename' => $file, 'contentType' => 'text/plain']])
            ->createAsMultipart();

        $curl = new Curl();
        $curl->prepare($request);

        $headers            = $curl->getOption(CURLOPT_HTTPHEADER);
        $contentTypeHeaders = array_filter($headers, fn($header) => str_starts_with($header, 'Content-Type:'));

        $this->assertCount(0, $contentTypeHeaders);

        unlink($file);
    }

    public function testMultipartStripsLowercaseContentTypeHeader()
    {
        // Regression test: HTTP header names are case-insensitive, so a user-supplied
        // 'content-type: ...' must be stripped too - otherwise it would be sent alongside
        // curl's own auto-generated multipart Content-Type, giving two conflicting headers.
        $request = new \Pop\Http\Client\Request('http://localhost/', 'POST');
        $request->createAsMultipart()
            ->setData(['foo' => 'bar']);
        $request->addHeader('content-type', 'text/plain');

        // Sanity check: the lowercase header really is on the request and really is rendered
        $this->assertTrue($request->hasHeader('content-type'));

        $curl = new Curl();
        $curl->prepare($request);

        $headers = $curl->getOption(CURLOPT_HTTPHEADER);
        $this->assertIsArray($headers);
        $contentTypeHeaders = array_filter($headers, fn($header) => stripos($header, 'content-type:') === 0);
        $this->assertCount(0, $contentTypeHeaders, 'Sent headers: ' . implode(' | ', $headers));
    }

    public function testMultipartPostFieldsIsZeroCopyAndCarriesTheBoundaryOnTheRequest()
    {
        // Task 14 regression guard, re-asserted after multipart preparation was made lazy:
        // curl still gets the native array shape and the request still carries a boundary.
        $file = sys_get_temp_dir() . '/pop-http-curl-lazy-' . uniqid() . '.txt';
        file_put_contents($file, 'streamed content');

        $request = new \Pop\Http\Client\Request('http://localhost/', 'POST');
        $request->createAsMultipart()
            ->setData(['username' => 'admin', 'upload' => ['filename' => $file, 'contentType' => 'text/plain']]);

        $curl = new Curl();
        $curl->prepare($request);

        $postFields = $curl->getOption(CURLOPT_POSTFIELDS);
        $this->assertIsArray($postFields);
        $this->assertEquals('admin', $postFields['username']);
        $this->assertInstanceOf('CURLFile', $postFields['upload']);
        $this->assertEquals($file, $postFields['upload']->getFilename());

        $this->assertStringStartsWith('multipart/form-data; boundary=', $request->getHeaderValueAsString('Content-Type'));

        // Nothing rendered the body into a string, so there is no pop-http Content-Length -
        // curl computes its own, since it builds the multipart framing itself.
        $this->assertEmpty($request->getData()->getDataContent());
        $this->assertFalse($request->hasHeader('Content-Length'));

        unlink($file);
    }

    public function testReusedHandlerClearsStalePostFields()
    {
        // Regression test: curl switches the method to POST whenever CURLOPT_POSTFIELDS is set,
        // so a stale body left over from a prior prepare() would silently turn a subsequent
        // body-less GET into a POST.
        $curl = new Curl();

        $curl->prepare(new Request('http://localhost/', 'POST', ['foo' => 'bar']));
        $this->assertEquals('foo=bar', $curl->getOption(CURLOPT_POSTFIELDS));

        $curl->prepare(new Request('http://localhost/other', 'GET'));

        $this->assertFalse($curl->hasOption(CURLOPT_POSTFIELDS));
        $this->assertNull($curl->getOption(CURLOPT_POSTFIELDS));
        $this->assertFalse($curl->hasOption(CURLOPT_POST));
        $this->assertFalse($curl->hasOption(CURLOPT_CUSTOMREQUEST));
        // libcurl keeps a handle in POST mode until GET is asserted explicitly, so unsetting
        // CURLOPT_POST/CURLOPT_POSTFIELDS alone is not enough to make this a real GET.
        $this->assertTrue($curl->hasOption(CURLOPT_HTTPGET));
        $this->assertTrue($curl->getOption(CURLOPT_HTTPGET));
    }

    public function testPrepareWithClearFalsePreservesPreDefinedPostFields()
    {
        // Regression test: the stale-body clearing must respect $clear the same way the header
        // handling does - with $clear = false a body pre-defined directly on the handler has to
        // survive rather than being wiped by a body-less request.
        $curl = new Curl();
        $curl->setOption(CURLOPT_POSTFIELDS, 'pre-defined=body');

        $curl->prepare(new Request('http://localhost/', 'POST'), null, false, false);

        $this->assertEquals('pre-defined=body', $curl->getOption(CURLOPT_POSTFIELDS));
    }

    /**
     * Drive a prepared Curl handler against an in-process loopback listener and return the
     * raw request bytes it actually put on the wire. Uses an ephemeral port and no external
     * process, so it stays deterministic.
     */
    private function captureRequestOnTheWire(Curl $curl, string $method): string
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $this->assertIsResource($server, 'Could not bind a loopback listener: ' . $errstr);

        $name = stream_socket_get_name($server, false);
        $port = (int)substr($name, strrpos($name, ':') + 1);
        stream_set_blocking($server, false);

        $curl->prepare(new Request('http://127.0.0.1:' . $port . '/', $method, ($method === 'POST') ? ['foo' => 'bar'] : null));
        $curl->setOption(CURLOPT_TIMEOUT, 5);

        $multi = curl_multi_init();
        curl_multi_add_handle($multi, $curl->resource());

        $captured = '';
        $conn     = null;
        $start    = microtime(true);

        do {
            curl_multi_exec($multi, $running);

            if ($conn === null) {
                $conn = @stream_socket_accept($server, 0);
                if (is_resource($conn)) {
                    stream_set_blocking($conn, false);
                } else {
                    $conn = null;
                }
            }

            if (is_resource($conn)) {
                $chunk = fread($conn, 8192);
                if (is_string($chunk)) {
                    $captured .= $chunk;
                }
                if (str_contains($captured, "\r\n\r\n")) {
                    fwrite($conn, "HTTP/1.1 200 OK\r\nContent-Length: 2\r\nConnection: close\r\n\r\nok");
                    fclose($conn);
                    $conn = false;
                }
            }

            usleep(1000);
        } while ($running && ((microtime(true) - $start) < 5));

        curl_multi_remove_handle($multi, $curl->resource());
        curl_multi_close($multi);
        fclose($server);

        return $captured;
    }

    public function testReusedHandlerSendsARealGetOnTheWireAfterAPost()
    {
        // The option-level assertions above are necessary but not sufficient: clearing
        // CURLOPT_POSTFIELDS is itself a curl_setopt(..., null) call that puts the handle right
        // back into POST mode, and that is completely invisible in the tracked options array.
        // Only the bytes on the wire prove the method.
        $curl = new Curl();

        $post = $this->captureRequestOnTheWire($curl, 'POST');
        $this->assertStringStartsWith('POST / HTTP/1.1', $post);
        $this->assertStringContainsString('foo=bar', $post);

        $get = $this->captureRequestOnTheWire($curl, 'GET');
        $this->assertStringStartsWith('GET / HTTP/1.1', $get);
        $this->assertStringNotContainsString('foo=bar', $get);
        $this->assertStringNotContainsString('Content-Type: application/x-www-form-urlencoded', $get);
    }

    public function testHttpGetIsClearedWhenAReusedHandlerSwitchesBackToPost()
    {
        $curl = new Curl();

        $curl->prepare(new Request('http://localhost/', 'GET'));
        $this->assertTrue($curl->hasOption(CURLOPT_HTTPGET));

        $curl->prepare(new Request('http://localhost/', 'POST', ['foo' => 'bar']));
        $this->assertFalse($curl->hasOption(CURLOPT_HTTPGET));
        $this->assertTrue($curl->hasOption(CURLOPT_POST));
        $this->assertEquals('foo=bar', $curl->getOption(CURLOPT_POSTFIELDS));

        $curl->prepare(new Request('http://localhost/', 'PUT'));
        $this->assertFalse($curl->hasOption(CURLOPT_HTTPGET));
        $this->assertEquals('PUT', $curl->getOption(CURLOPT_CUSTOMREQUEST));
    }

    public function testSendFailureExceptionCarriesTheRequest()
    {
        $request = new \Pop\Http\Client\Request('http://invalid.invalid/');
        $handler = new \Pop\Http\Client\Handler\Curl();
        $handler->prepare($request);
        $handler->setOption(CURLOPT_TIMEOUT_MS, 200);
        $handler->setOption(CURLOPT_CONNECTTIMEOUT_MS, 200);

        try {
            $handler->send();
            $this->fail('Expected a Client\Handler\Exception to be thrown.');
        } catch (\Pop\Http\Client\Handler\Exception $exception) {
            $this->assertSame($request, $exception->getRequest());
        }
    }

}
