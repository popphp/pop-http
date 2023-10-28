<?php

namespace Pop\Http\Test;

use Pop\Http\Server;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{

    public function testConstructor()
    {
        $server = new Server(new Server\Request(), new Server\Response());
        $this->assertInstanceOf('Pop\Http\Server', $server);
    }


    public function testCreateWithBasePath()
    {
        $server = Server::createWithBasePath('/foo');
        $this->assertInstanceOf('Pop\Http\Server', $server);
        $this->assertEquals('/foo', $server->request()->getUri()->getBasePath());
    }

    public function testGettersAndSetters()
    {
        $server = new Server();
        $server->setRequest(new Server\Request())
            ->setResponse(new Server\Response());

        $this->assertTrue($server->hasRequest());
        $this->assertTrue($server->hasResponse());
        $this->assertInstanceOf('Pop\Http\Server\Request', $server->getRequest());
        $this->assertInstanceOf('Pop\Http\Server\Response', $server->getResponse());
        $this->assertInstanceOf('Pop\Http\Server\Request', $server->request());
        $this->assertInstanceOf('Pop\Http\Server\Response', $server->response());
        $this->assertInstanceOf('Pop\Http\Server\Request', $server->request);
        $this->assertInstanceOf('Pop\Http\Server\Response', $server->response);
    }

    public function testStatusMethods1()
    {
        $server = new Server();
        $server->setRequest(new Server\Request())
            ->setResponse(new Server\Response(['code' => 200]));

        $this->assertTrue($server->isOk());
        $this->assertFalse($server->isCreated());
        $this->assertFalse($server->isAccepted());
        $this->assertFalse($server->isNoContent());
    }

    public function testStatusMethods2()
    {
        $server = new Server();
        $server->setRequest(new Server\Request())
            ->setResponse(new Server\Response(['code' => 301]));

        $this->assertTrue($server->isMovedPermanently());
        $this->assertFalse($server->isFound());
    }

    public function testStatusMethods3()
    {
        $server = new Server();
        $server->setRequest(new Server\Request())
            ->setResponse(new Server\Response(['code' => 401]));

        $this->assertTrue($server->isUnauthorized());
        $this->assertFalse($server->isBadRequest());
        $this->assertFalse($server->isForbidden());
        $this->assertFalse($server->isNotFound());
        $this->assertFalse($server->isMethodNotAllowed());
        $this->assertFalse($server->isNotAcceptable());
        $this->assertFalse($server->isRequestTimeout());
        $this->assertFalse($server->isConflict());
        $this->assertFalse($server->isLengthRequired());
        $this->assertFalse($server->isUnsupportedMediaType());
        $this->assertFalse($server->isUnprocessableEntity());
        $this->assertFalse($server->isTooManyRequests());
    }

    public function testStatusMethods4()
    {
        $server = new Server();
        $server->setRequest(new Server\Request())
            ->setResponse(new Server\Response(['code' => 502]));

        $this->assertTrue($server->isBadGateway());
        $this->assertFalse($server->isInternalServerError());
        $this->assertFalse($server->isServiceUnavailable());
    }

    public function testGetHeadersAsString()
    {
        $response = new Server\Response();
        $response->addHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 123456'
        ]);

        $headers = "Content-Type: application/json\r\nAuthorization: Bearer 123456\r\n";

        $server = new Server(new Server\Request(), $response);
        $this->assertEquals($headers, $server->getHeadersAsString());
    }

    public function testSend()
    {
        $response = new Server\Response();
        $response->addHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 123456'
        ]);
        $response->setBody(json_encode(['foo' => 'bar'], JSON_PRETTY_PRINT));

        $server = new Server(new Server\Request(), $response);

        ob_start();
        $server->send();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertTrue(str_contains($result, 'foo'));
    }

    public function testSendHeaders()
    {
        $response = new Server\Response();
        $response->addHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 123456'
        ]);
        $response->setBody(json_encode(['foo' => 'bar'], JSON_PRETTY_PRINT));

        $server = new Server(new Server\Request(), $response);

        ob_start();
        $server->sendHeaders();
        $result = ob_get_contents();
        ob_end_clean();

        $this->assertEmpty($result);
    }

    public function testRender()
    {
        $response = new Server\Response();
        $response->addHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer 123456'
        ]);
        $response->setBody(json_encode(['foo' => 'bar'], JSON_PRETTY_PRINT));

        $server = new Server(new Server\Request(), $response);

        $responseString = (string)$server;

        $this->assertTrue(str_contains($responseString, 'HTTP/1.1 200 OK'));
        $this->assertTrue(str_contains($responseString, 'Bearer 123456'));
        $this->assertTrue(str_contains($responseString, 'application/json'));
        $this->assertTrue(str_contains($responseString, '"foo"'));
    }

}