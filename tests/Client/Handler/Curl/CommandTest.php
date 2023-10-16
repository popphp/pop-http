<?php

namespace Pop\Http\Test\Client\Handler\Curl;

use Pop\Http\Auth;
use Pop\Http\Client;
use Pop\Http\Client\Handler\Curl\Command;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{

    public function testCommandToClient()
    {
        $command = 'curl -i -X POST --data "foo=bar&baz=123" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertInstanceOf('Pop\Http\Client', $client);
    }

    public function testClientToCommand()
    {
        $client = new Client('http://localhost:8000/post.php');
        $client->setMethod('POST')
            ->setData([
                'foo' => 'bar',
                'baz' => 123
            ]);

        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X POST --data "foo=bar&baz=123" "http://localhost:8000/post.php"', $command);
    }

    public function testCommandToClientNoOptions()
    {
        $command = 'curl "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertInstanceOf('Pop\Http\Client', $client);
    }

    public function testCommandToClientWithAuth1()
    {
        $command = 'curl --basic -u "username:password" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertTrue($client->hasAuth());
    }

    public function testCommandToClientWithAuth2()
    {
        $command = 'curl --basic --user "username:password" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertTrue($client->hasAuth());
    }

    public function testCommandToClientWithFiles1()
    {
        $command = 'curl -i -X POST --json -d "@' . realpath(__DIR__ . '/../../../tmp/data.json') . '" "http://localhost:8000/files.php"';
        $client  = Command::commandToClient($command);
        $this->assertTrue($client->hasFiles());
    }

    public function testCommandToClientWithFiles2()
    {
        $command = 'curl -i -X POST --json -d "@../../../tmp/data.json' . '" "http://localhost:8000/files.php"';
        $client  = Command::commandToClient($command);
        $this->assertFalse($client->hasFiles());
    }

    public function testCommandToClientException()
    {
        $this->expectException('Pop\Http\Client\Handler\Curl\Exception');
        $command = 'BAD -i -X POST --data "foo=bar&baz=123" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
    }

    public function testClientToCommandException()
    {
        $this->expectException('Pop\Http\Client\Handler\Curl\Exception');
        $client = new Client('http://localhost:8000/post.php', new Client\Handler\Stream());
        $client->setMethod('POST')
            ->setData([
                'foo' => 'bar',
                'baz' => 123
            ]);

        $command = Command::clientToCommand($client);
    }

    public function testExtractOptions1()
    {
        $command = 'curl -i -X POST -d"foo=bar&baz=123" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('bar', $client->getRequest()->getData()->getData('foo'));
    }

    public function testExtractOptions2()
    {
        $command = 'curl -i -X POST -d"foo=bar" -d"baz=123" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('bar', $client->getRequest()->getData()->getData('foo'));
    }

    public function testExtractOptions3()
    {
        $command = 'curl -i -X POST -d"foo" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('foo', $client->getRequest()->getData()->getRawData());
    }

    public function testConvertCommandOptions1()
    {
        $command = 'curl -G "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('GET', $client->getMethod());
    }

    public function testConvertCommandOptions2()
    {
        $command = 'curl --get "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('GET', $client->getMethod());
    }

    public function testConvertCommandOptions3()
    {
        $command = 'curl -I "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('HEAD', $client->getMethod());
    }

    public function testConvertCommandOptions4()
    {
        $command = 'curl --head "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('HEAD', $client->getMethod());
    }

    public function testConvertCommandOptions5()
    {
        $command = 'curl --request PUT "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('PUT', $client->getMethod());
    }

    public function testConvertCommandOptions6()
    {
        $command = 'curl -k "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals(0, $client->getHandler()->getOption(CURLOPT_SSL_VERIFYHOST));
        $this->assertEquals(0, $client->getHandler()->getOption(CURLOPT_SSL_VERIFYPEER));
    }

    public function testConvertCommandOptions7()
    {
        $command = 'curl -H "Content-Type: application/json" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertTrue($client->getRequest()->hasHeader('Content-Type'));
        $this->assertEquals('application/json', $client->getRequest()->getHeaderValueAsString('Content-Type'));
    }

    public function testConvertCommandOptions8()
    {
        $command = 'curl --header "Content-Type: application/json" --header "Accept: application/json" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertTrue($client->getRequest()->hasHeader('Content-Type'));
        $this->assertEquals('application/json', $client->getRequest()->getHeaderValueAsString('Content-Type'));
        $this->assertTrue($client->getRequest()->hasHeader('Accept'));
        $this->assertEquals('application/json', $client->getRequest()->getHeaderValueAsString('Accept'));
    }

    public function testConvertCommandOptions9()
    {
        $command = 'curl -X POST --header "Content-Type: application/json" --data \'{"foo":"bar","baz":"123"}\' "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('bar', $client->getRequest()->getData()->getData('foo'));
    }

    public function testConvertCommandOptions10()
    {
        $command = 'curl -X POST -F "foo=bar" -F "baz=123" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('bar', $client->getRequest()->getData()->getData('foo'));
    }

    public function testConvertCommandOptions11()
    {
        $command = 'curl -X POST --form "foo=bar" --form "baz=123" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('bar', $client->getRequest()->getData()->getData('foo'));
    }

    public function testConvertCommandOptions12()
    {
        $command = 'curl -X POST -F "file1=@' . realpath(__DIR__ . '/../../../tmp/data.xml') . '" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertTrue($client->getRequest()->getData()->hasData('file1'));
    }

    public function testConvertCommandOptions13()
    {
        $command = 'curl -X POST -F "file1=@' . realpath(__DIR__ . '/../../../tmp/data.json') . '" -F "file2=@' . realpath(__DIR__ . '/../../../tmp/data2.json') . '" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertTrue($client->getRequest()->getData()->hasData('file1'));
    }

    public function testConvertCommandOptions14()
    {
        $command = 'curl -X POST -a "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertTrue($client->getHandler()->getOption(CURLOPT_APPEND));
    }

    public function testConvertCommandOptions15()
    {
        $command = 'curl -X POST -A "popphp/pop-http 1.0" "http://localhost:8000/post.php"';
        $client = Command::commandToClient($command);
        $this->assertEquals('popphp/pop-http 1.0', $client->getHandler()->getOption(CURLOPT_USERAGENT));
    }

    public function testClientToCommand1()
    {
        $client = new Client('http://localhost:8000/post.php', ['verify_peer' => false, 'allow_self_signed' => false]);
        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X GET --insecure "http://localhost:8000/post.php"', $command);
    }

    public function testClientToCommand2()
    {
        $request = new Client\Request('http://localhost:8000/post.php');
        $request->addHeader('Authorization', 'Bearer 123456789');
        $client = new Client($request, Auth::createBasic('username', 'password'));
        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X GET --basic -u "username:password" "http://localhost:8000/post.php"', $command);
    }

    public function testClientToCommand3()
    {
        $request = new Client\Request('http://localhost:8000/post.php');
        $request->addHeader('Content-Type', 'application/json');
        $client = new Client($request);
        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X GET --header "Content-Type: application/json" "http://localhost:8000/post.php"', $command);
    }

    public function testClientToCommand4()
    {
        $request = new Client\Request('http://localhost:8000/post.php', 'POST');
        $request->createAsJson()
            ->setData([
                'foo' => 'bar',
                'baz' => 123
            ]);

        $client = new Client($request);
        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X POST --header "Content-Type: application/json" --data \'{"foo":"bar","baz":123}\' "http://localhost:8000/post.php"', $command);
    }

    public function testClientToCommand5()
    {
        $request = new Client\Request('http://localhost:8000/post.php', 'POST');
        $request->createAsMultipart()
            ->setData([
                'foo' => 'bar',
                'baz' => 123
            ]);

        $client = new Client($request);
        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X POST -F "foo=bar" -F "baz=123" "http://localhost:8000/post.php"', $command);
    }

    public function testClientToCommand6()
    {
        $request = new Client\Request('http://localhost:8000/post.php', 'POST');
        $request->createAsXml()
            ->setData([
                'file1' => [
                    'filename'    => __DIR__ . '/../../../tmp/data.xml',
                    'contentType' => 'application/xml'
                ]
            ]);

        $client = new Client($request);
        $command = Command::clientToCommand($client);
        $this->assertTrue(str_contains($command, 'curl -i -X POST --header "Content-Type: application/xml" --data @'));
        $this->assertTrue(str_contains($command, 'tmp/data.xml "http://localhost:8000/post.php'));
    }

    public function testClientToCommand7()
    {
        $request = new Client\Request('http://localhost:8000/post.php', 'POST');
        $request->setBody("This is the page's text body");

        $client = new Client($request);
        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X POST --data \'This is the page\\\'s text body\' "http://localhost:8000/post.php"', $command);
    }

    public function testClientToCommand8()
    {
        $client = new Client('http://localhost:8000/post.php', new Client\Handler\Curl());
        $client->getHandler()->setOption(CURLOPT_APPEND, 1)
            ->setOption(CURLOPT_USERAGENT, 'popphp/pop-http 1.0');

        $command = Command::clientToCommand($client);
        $this->assertEquals('curl -i -X GET -a -A "popphp/pop-http 1.0" "http://localhost:8000/post.php"', $command);
    }

}
