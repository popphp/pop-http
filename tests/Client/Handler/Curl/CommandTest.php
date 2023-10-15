<?php

namespace Pop\Http\Test\Client\Handler\Curl;

use Pop\Http\Client\Handler\Curl;
use PHPUnit\Framework\TestCase;

class CommandTest extends TestCase
{

    public function testCommandToClient()
    {
        $command = 'curl -i -X POST -dfoo=bar -dbaz=123 http://localhost:8000/post.php';
        $client = Curl\Command::commandToClient($command);
    }

    public function testClientToCommand()
    {

    }

}
