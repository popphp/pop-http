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

    /**
     * Regression test: a repeated -H flag where one occurrence has a value and the
     * other is valueless (e.g. the last token in the command, immediately followed by
     * the request URI) collects a null alongside the real value in
     * extractCommandOptionValues(). Handling that null must not throw and must not
     * produce a bogus empty header - the valueless occurrence should simply be dropped.
     */
    public function testConvertCommandOptionsRepeatedShortHeaderWithValuelessOccurrence()
    {
        $command = 'curl -H "X-Foo: 1" -H http://localhost/';
        $client  = Command::commandToClient($command);
        $this->assertTrue($client->getRequest()->hasHeader('X-Foo'));
        $this->assertEquals('1', $client->getRequest()->getHeaderValueAsString('X-Foo'));
        $this->assertCount(1, $client->getRequest()->getHeaders());
        $this->assertEquals('http://localhost/', $client->getRequest()->getUriAsString());
    }

    /**
     * Same as above, but for the long-form --header flag.
     */
    public function testConvertCommandOptionsRepeatedLongHeaderWithValuelessOccurrence()
    {
        $command = 'curl --header "X-Foo: 1" --header http://localhost/';
        $client  = Command::commandToClient($command);
        $this->assertTrue($client->getRequest()->hasHeader('X-Foo'));
        $this->assertEquals('1', $client->getRequest()->getHeaderValueAsString('X-Foo'));
        $this->assertCount(1, $client->getRequest()->getHeaders());
        $this->assertEquals('http://localhost/', $client->getRequest()->getUriAsString());
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

    public function testConvertCommandOptionsTimeConditionShortFlag()
    {
        // Regression test: -z's $commandOptions/$valueOptions entries used to be a single
        // combined '-z --time-cond' key, which the generic option matcher in
        // convertCommandOptions() could only match via fuzzy substring (str_starts_with()),
        // and isValueOption('-z') always returned false since '-z' alone was never a real key
        // - so CURLOPT_TIMECONDITION silently got set to `true` instead of the actual time value.
        $command = 'curl -z "Wed, 21 Oct 2015 07:28:00 GMT" "http://localhost:8000/post.php"';
        $client  = Command::commandToClient($command);
        $this->assertTrue($client->getHandler()->hasOption(CURLOPT_TIMECONDITION));
        $this->assertEquals('Wed, 21 Oct 2015 07:28:00 GMT', $client->getHandler()->getOption(CURLOPT_TIMECONDITION));
    }

    public function testConvertCommandOptionsTimeConditionLongFlag()
    {
        // Same as above, but for the --time-cond long-flag form of the same option -
        // both flags must map to the same constant and behave identically.
        $command = 'curl --time-cond "Wed, 21 Oct 2015 07:28:00 GMT" "http://localhost:8000/post.php"';
        $client  = Command::commandToClient($command);
        $this->assertTrue($client->getHandler()->hasOption(CURLOPT_TIMECONDITION));
        $this->assertEquals('Wed, 21 Oct 2015 07:28:00 GMT', $client->getHandler()->getOption(CURLOPT_TIMECONDITION));
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

    public function testClientToCommandTimeConditionRoundTrips()
    {
        // Regression test: before the -z/--time-cond fix, $phpOptions['CURLOPT_TIMECONDITION']
        // was the single malformed string '-z --time-cond' rather than an array of the two
        // separate flags, so clientToCommand() emitted the literal, non-functional flag
        // '-z --time-cond "<value>"' instead of a well-formed '-z "<value>"'. Confirm the
        // emitted command is well-formed AND that re-parsing it via commandToClient() recovers
        // the original value (not `true`).
        $client = new Client('http://localhost:8000/post.php', new Client\Handler\Curl());
        $client->getHandler()->setOption(CURLOPT_TIMECONDITION, 'Wed, 21 Oct 2015 07:28:00 GMT');

        $command = Command::clientToCommand($client);
        $this->assertEquals(
            'curl -i -X GET -z "Wed, 21 Oct 2015 07:28:00 GMT" "http://localhost:8000/post.php"', $command
        );
        $this->assertStringNotContainsString('-z --time-cond', $command);

        $reparsed = Command::commandToClient($command);
        $this->assertTrue($reparsed->getHandler()->hasOption(CURLOPT_TIMECONDITION));
        $this->assertEquals('Wed, 21 Oct 2015 07:28:00 GMT', $reparsed->getHandler()->getOption(CURLOPT_TIMECONDITION));
    }

    public function testParseCommandOptionsHandlesSpaceDashInQuotedData()
    {
        $options = Command::parseCommandOptions('-X POST --data \'{"range": "1 - 5"}\'');

        $this->assertContains('-X POST', $options);
        $this->assertContains('--data "{"range": "1 - 5"}"', $options);
    }

    public function testParseCommandOptionsHandlesMultipleQuotedValues()
    {
        $options = Command::parseCommandOptions('-H "Authorization: Bearer abc-123" -H "X-Custom: a - b"');

        $this->assertContains('-H "Authorization: Bearer abc-123"', $options);
        $this->assertContains('-H "X-Custom: a - b"', $options);
    }

    public function testCommandToClientHandlesSpaceDashInQuotedData()
    {
        $command = 'curl -X POST --header "Content-Type: application/json" --data \'{"range": "1 - 5"}\' "http://localhost:8000/post.php"';
        $client  = Command::commandToClient($command);
        $this->assertEquals('1 - 5', $client->getRequest()->getData()->getData('range'));
    }

    public function testJsonDataWithEqualsSignIsNotUrlDecoded()
    {
        $client = Command::commandToClient(
            'curl -X POST -H "Content-Type: application/json" --data \'{"filter":"a=b"}\' http://localhost/'
        );

        // The '=' character inside the JSON value must not be mistaken for a
        // urlencoded key=value delimiter (the confirmed bug: parse_str() previously
        // mangled this into ['{"filter":"a' => 'b"}"']). With the Content-Type
        // correctly driving the decision, the JSON is decoded as-is instead, so the
        // 'filter' key survives intact with its full 'a=b' value.
        $this->assertEquals(['filter' => 'a=b'], $client->getRequest()->getData()->getData());
        $this->assertEquals('a=b', $client->getRequest()->getData()->getData('filter'));
    }

    public function testDataWithJsonContentTypeButInvalidJsonBodyDoesNotCrash()
    {
        // A declared "Content-Type: application/json" header doesn't guarantee the -d body is
        // actually valid JSON. json_decode() returns null on failure - previously that null flowed
        // straight into $request->setData(), which threw a TypeError from Data::__construct()
        // three calls away. This must degrade gracefully (fall back to the raw string) instead of
        // crashing commandToClient().
        $client = Command::commandToClient(
            'curl -X POST -H "Content-Type: application/json" -d "foo=bar" http://localhost/'
        );

        $this->assertEquals('foo=bar', $client->getRequest()->getData()->getRawData());
    }

    public function testDataWithJsonContentTypeAndLiteralNullBodyDoesNotCrash()
    {
        // Second gap in the same guard, found in the final whole-branch review:
        // json_decode('null') is VALID JSON - it succeeds with JSON_ERROR_NONE and legitimately
        // decodes to PHP null. The json_last_error() === JSON_ERROR_NONE guard alone therefore
        // let a literal 'null' body flow through as $data = null, which then crashed the same way
        // (TypeError from Data::__construct(), three calls away via $request->setData()) as the
        // invalid-JSON case this guard was originally added to fix. Must degrade to the raw
        // string, same as the invalid-JSON case above.
        $client = Command::commandToClient(
            'curl -X POST -H "Content-Type: application/json" --data "null" http://localhost/'
        );

        $this->assertEquals('null', $client->getRequest()->getData()->getRawData());
    }

    public function testMultipleDataFlagsWithJsonContentTypeDoesNotCrash()
    {
        // Combines two previously-separate concerns: multiple -d occurrences (merged into an
        // array of raw string chunks by extractCommandOptionValues(), then re-joined with '&' by
        // convertCommandOptions()) AND a declared JSON Content-Type over a non-JSON body. This
        // exact combination was untested and is what hid the json_decode()-returns-null crash.
        $client = Command::commandToClient(
            'curl -X POST -H "Content-Type: application/json" -d "foo=bar" -d "baz=123" http://localhost/'
        );

        $this->assertEquals('foo=bar&baz=123', $client->getRequest()->getData()->getRawData());
    }

    public function testJsonShapedDataWithNoContentTypeIsStillMangled()
    {
        // Known, accepted limitation (not a regression from this fix, not something this task
        // set out to fix): with NO Content-Type header at all, a JSON-shaped -d value falls
        // through to the same "treat as urlencoded" default curl itself uses for untyped -d
        // bodies, so a '=' inside the JSON still gets misread as a key=value delimiter. This test
        // locks in that current (imperfect) behavior so it doesn't silently change; fixing it
        // would require guessing content type from data shape again, which is the exact class of
        // heuristic this task removed for the (much more common) explicit-Content-Type case.
        $client = Command::commandToClient(
            'curl -X POST --data \'{"filter":"a=b"}\' http://localhost/'
        );

        $this->assertEquals(['{"filter":"a' => 'b"}'], $client->getRequest()->getData()->getData());
    }

    public function testQuotedDataValueStartingWithDashIsNotMistakenForAFlag()
    {
        // Regression test for the final whole-branch review finding: tokenize() strips quote
        // characters but (before this fix) didn't track whether a token was originally quoted,
        // so parseCommandOptions()'s str_starts_with($token, '-') "is this a new flag" check
        // couldn't tell a quoted value that happens to start with '-' from an actual new flag.
        // '-foo=bar' (quoted, meant as the --data value) was misread as a stray new flag
        // '-foo=bar', leaving --data with no value at all - which then flowed into
        // extractCommandOptionValues()'s trimQuotes(null) call and threw an uncaught TypeError.
        $client = Command::commandToClient(
            'curl -X POST --data \'-foo=bar\' http://localhost/'
        );

        $this->assertEquals(['-foo' => 'bar'], $client->getRequest()->getData()->getData());
    }

    public function testFormValueContainingButNotStartingWithDashStillWorks()
    {
        // Companion regression test locking in a case the reviewer confirmed was already fine
        // (a value that CONTAINS but doesn't START with '-'), exercised through the same
        // tokenize()/parseCommandOptions() code path touched by this fix, so a future change to
        // that path can't silently break it.
        $client = Command::commandToClient(
            'curl -X POST -F \'note=-x\' http://localhost/'
        );

        $this->assertEquals(['note' => '-x'], $client->getRequest()->getData()->getData());
    }

    public function testQuotedFlagAsFirstTokenIsStillRecognizedAsAFlag()
    {
        // Regression test for the re-review finding: the fix above (treating any quoted
        // leading-dash token as a value continuation) over-corrected, so a legitimately
        // QUOTED flag - not just a quoted data VALUE - was also being silently dropped.
        // '-X' quoted as the very first token must still be recognized as the method flag,
        // not swallowed as a phantom "value" with no preceding option to attach to.
        $client = Command::commandToClient("curl '-X' POST http://localhost/");

        $this->assertEquals('POST', $client->getRequest()->getMethod());
    }

    public function testQuotedFlagAloneIsStillRecognizedAsAFlag()
    {
        // Same over-correction, exercised via a boolean (non-value) flag quoted on its own.
        $client = Command::commandToClient('curl "-L" http://localhost/');

        $this->assertTrue($client->getHandler()->getOption(CURLOPT_FOLLOWLOCATION));
    }

    public function testQuotedFlagAfterABareBooleanFlagIsNotSwallowedAsItsValue()
    {
        // Regression test for the $awaitingValue fix: a bare boolean flag like '-L' never
        // takes a value, so it never has anything to "await." Before this fix, $awaitingValue
        // only checked whether $current lacked a space yet - true for '-L' just as much as for
        // a real value-taking flag like '--data' - so the very next quoted, recognized flag
        // ('-X') was wrongly swallowed as '-L's value, leaving the method stuck on GET.
        $client = Command::commandToClient("curl '-L' '-X' POST http://localhost/");

        $this->assertTrue($client->getHandler()->getOption(CURLOPT_FOLLOWLOCATION));
        $this->assertEquals('POST', $client->getRequest()->getMethod());
    }

    public function testQuotedFlagAfterABareBooleanFlagIsNotDroppedEntirely()
    {
        // Companion regression test with a second bare boolean flag ('-k') following the first
        // ('-L'). Before this fix, '-k' was misread as part of '-L's swallowed "value" and
        // dropped entirely, leaving SSL verification on.
        $client = Command::commandToClient("curl '-L' '-k' http://localhost/");

        $this->assertTrue($client->getHandler()->getOption(CURLOPT_FOLLOWLOCATION));
        $this->assertEquals(0, $client->getHandler()->getOption(CURLOPT_SSL_VERIFYPEER));
    }

    public function testQuotedFlagAfterACompletedOptionIsStillRecognizedAsAFlag()
    {
        // A quoted, recognized flag ("-H") arriving after a PRIOR option ("-X POST") has
        // already received its value must start a new option, not be swallowed as more of
        // -X's value.
        $client = Command::commandToClient(
            'curl -X POST "-H" "Accept: text/plain" http://localhost/'
        );

        $this->assertEquals('POST', $client->getRequest()->getMethod());
        $this->assertTrue($client->getRequest()->hasHeader('Accept'));
        $this->assertEquals('text/plain', $client->getRequest()->getHeaderValueAsString('Accept'));
    }

    public function testQuotedDataFlagStillConsumesItsOwnQuotedValue()
    {
        // A quoted, recognized flag ("--data") that is itself still AWAITING its value
        // (nothing has been accumulated for it yet) must still consume the very next token
        // as its value, even though that next token is also quoted - it must not be misread
        // as yet another new flag.
        $client = Command::commandToClient(
            "curl -X POST '--data' 'foo=bar' http://localhost/"
        );

        $this->assertEquals(['foo' => 'bar'], $client->getRequest()->getData()->getData());
    }

    public function testLocationFlagSetsFollowLocation()
    {
        $client = Command::commandToClient('curl -L http://localhost/');
        $this->assertTrue($client->getHandler()->getOption(CURLOPT_FOLLOWLOCATION));
    }

    public function testClientToCommandIncludesLocationFlag()
    {
        $client = new \Pop\Http\Client('http://localhost/');
        $client->prepare();
        $client->getHandler()->setOption(CURLOPT_FOLLOWLOCATION, true);

        $this->assertStringContainsString('-L', $client->toCurlCommand());
    }

    public function testValueTakingFlagWithNoValueDoesNotCrash()
    {
        // Regression test for the generic "handle all other options" loop in
        // convertCommandOptions(): a value-taking flag given with NO value at all left $value
        // as null for that option, and both branches of the loop passed it straight into
        // self::trimQuotes($value), which requires a string argument - throwing an uncaught
        // TypeError instead of degrading gracefully. Confirmed this throws
        // TypeError: Command::trimQuotes(): Argument #1 ($value) must be of type string, null
        // given, against the pre-fix code.
        $client = Command::commandToClient('curl --abstract-unix-socket http://localhost/');

        $this->assertInstanceOf('Pop\Http\Client', $client);
        $this->assertEquals('', $client->getHandler()->getOption(CURLOPT_ABSTRACT_UNIX_SOCKET));
    }

    public function testOtherValueTakingFlagsWithNoValueAlsoDoNotCrash()
    {
        // Companion regression test confirming the fix generalizes across the whole generic
        // loop (both the is_array($curlOption) branch and its sibling else-if branch), not just
        // the one flag exercised above.
        $cacertClient = Command::commandToClient('curl --cacert http://localhost/');
        $this->assertEquals('', $cacertClient->getHandler()->getOption(CURLOPT_CAINFO));

        $cookieClient = Command::commandToClient('curl -b http://localhost/');
        $this->assertEquals('', $cookieClient->getHandler()->getOption(CURLOPT_COOKIE));
    }

    public function testRepeatedUserFlagLastOneWins()
    {
        // Regression test: extractCommandOptionValues() collects EVERY occurrence of a repeated
        // -u/--user flag into an array, but curl's own real semantics for -u/--user is
        // "last occurrence wins," not accumulate. Before the fix, $userData was handed straight
        // to str_contains($userData, ':') with no array guard at all, throwing an uncaught
        // TypeError: str_contains(): Argument #1 ($haystack) must be of type string, array given.
        $client = Command::commandToClient('curl -u "alice:pw1" -u "bob:pw2" http://localhost/');

        $this->assertTrue($client->hasAuth());
        $this->assertEquals('bob', $client->getAuth()->getUsername());
        $this->assertEquals('pw2', $client->getAuth()->getPassword());
    }

    public function testRepeatedUserFlagWithValuelessOccurrenceUsesTheRealOne()
    {
        // Companion case: the second -u occurrence is valueless (nothing follows it but the
        // request URI), collecting a null alongside the real value. The null occurrence
        // contributes nothing and must simply be ignored - not crash, and not clobber the
        // one real value present.
        $client = Command::commandToClient('curl -u "alice:pw1" -u http://localhost/');

        $this->assertTrue($client->hasAuth());
        $this->assertEquals('alice', $client->getAuth()->getUsername());
        $this->assertEquals('pw1', $client->getAuth()->getPassword());
    }

    public function testSingleUserFlagStillWorksUnaffected()
    {
        // Confirms the normalization added for the repeated-flag fix doesn't disturb the
        // ordinary, non-repeated -u path.
        $client = Command::commandToClient('curl -u "alice:pw1" http://localhost/');

        $this->assertTrue($client->hasAuth());
        $this->assertEquals('alice', $client->getAuth()->getUsername());
        $this->assertEquals('pw1', $client->getAuth()->getPassword());
    }

    public function testRepeatedUserAgentFlagLastOneWins()
    {
        // Regression test for the generic "handle all other options" loop: a repeated
        // single-value flag (e.g. -A/--user-agent) collects every occurrence into an array, but
        // curl's real semantics for these generic options is "last occurrence wins." Before the
        // fix, the existing '?? ""' guard only covered $value being null, not $value being an
        // array - so trimQuotes($value ?? '') threw an uncaught TypeError: trimQuotes():
        // Argument #1 ($value) must be of type string, array given - even with two real values
        // and no null involved at all.
        $client = Command::commandToClient('curl -A "AgentOne" -A "AgentTwo" http://localhost/');

        $this->assertEquals('AgentTwo', $client->getHandler()->getOption(CURLOPT_USERAGENT));
    }

    public function testRepeatedUserAgentFlagWithValuelessOccurrenceUsesTheRealOne()
    {
        // Companion case: the second -A occurrence is valueless, collecting a null alongside
        // the real value. Must not crash, and the one real value must win.
        $client = Command::commandToClient('curl -A "AgentOne" -A http://localhost/');

        $this->assertEquals('AgentOne', $client->getHandler()->getOption(CURLOPT_USERAGENT));
    }

    public function testSingleUserAgentFlagStillWorksUnaffected()
    {
        // Confirms the generic-loop normalization doesn't disturb the ordinary, non-repeated
        // -A path.
        $client = Command::commandToClient('curl -A "SingleAgent" http://localhost/');

        $this->assertEquals('SingleAgent', $client->getHandler()->getOption(CURLOPT_USERAGENT));
    }

    public function testConnectTimeoutSingleOccurrenceStillResolvesThroughGenericLoop()
    {
        // Confirms the generic-loop $value normalization (added for the repeated-flag fix)
        // doesn't break the ordinary non-repeated path for a different value-taking flag routed
        // through the same loop.
        $client = Command::commandToClient('curl --connect-timeout 5 http://localhost/');

        $this->assertTrue($client->getHandler()->hasOption(CURLOPT_CONNECTTIMEOUT));
        $this->assertEquals('5', $client->getHandler()->getOption(CURLOPT_CONNECTTIMEOUT));
    }

    public function testRepeatedFormFlagWithValuelessOccurrenceDoesNotCorruptData()
    {
        // Regression test: -F/--form already had array handling (correct, since -F is
        // cumulative by curl's real design, unlike -u), but a repeated -F with one valueless
        // occurrence used to collect a placeholder into the array alongside the real value,
        // silently corrupting the multipart body with a bogus extra entry.
        //
        // The fix lives upstream in extractCommandOptionValues(): a valueless -F/--form
        // occurrence is now skipped entirely at the point of extraction, rather than merged as
        // a placeholder and filtered back out downstream - an earlier version of this fix tried
        // filtering by "key is an integer" in convertCommandOptions()'s form-data block, but
        // that produced a false positive (see testFormFlagWithNumericFieldNameIsNotDropped
        // below): parse_str() casts numeric-string keys like "0" to real int keys, so a
        // legitimate field named "0" was indistinguishable from the bogus artifact by key type
        // alone and got silently dropped too.
        $client = Command::commandToClient('curl -F "foo=bar" -F http://localhost/');

        $data = $client->getRequest()->getData()->getData();
        $this->assertEquals(['foo' => 'bar'], $data);
        $this->assertCount(1, $data);
    }

    public function testSingleFormFlagStillWorksUnaffected()
    {
        // Confirms the fix doesn't disturb the ordinary, non-repeated -F path.
        $client = Command::commandToClient('curl -F "foo=bar" http://localhost/');

        $this->assertEquals(['foo' => 'bar'], $client->getRequest()->getData()->getData());
    }

    public function testFormFlagWithNumericFieldNameIsNotDropped()
    {
        // Regression test for the false positive found in code review: an earlier version of
        // the -F fix filtered convertCommandOptions()'s form-data array by "key is an integer"
        // to drop the bogus valueless-occurrence placeholder - but parse_str() (used to split a
        // "name=value" -F value into a key/value pair) casts a purely-numeric-string key like
        // "0" into a real PHP int key, exactly like the bogus placeholder's auto-generated key.
        // That made a genuinely-named "0" field indistinguishable from the artifact by key type
        // alone, so it was silently dropped too. Paired with a second, non-numeric field so the
        // result isn't collapsed by Data::setData()'s unrelated single-numeric-element ==>
        // raw-data special case (see testFormFlagWithSoleNumericFieldNameIsPreservedAsRawData).
        $client = Command::commandToClient('curl -F "0=value" -F "name=alice" http://localhost/');

        $data = $client->getRequest()->getData()->getData();
        $this->assertEquals(['0' => 'value', 'name' => 'alice'], $data);
        $this->assertCount(2, $data);
    }

    public function testFormFlagWithSoleNumericFieldNameIsPreservedAsRawData()
    {
        // Companion case to the one above: -F "0=value" as the ONLY form field. This value is
        // fully preserved by the Command.php fix (not lost/dropped, unlike the old is_int()
        // filter's false positive) - but Pop\Http\Client\Data::setData() has its own PRE-EXISTING,
        // unrelated special case: an array with exactly one element under integer key 0 whose
        // value is a string is treated as raw string data rather than a named field (see
        // Data::setData(), the `count($data) == 1 && isset($data[0]) && is_string($data[0])`
        // branch - unrelated to and untouched by this fix). So the value surfaces via
        // getRawData() rather than under a literal '0' key. This test locks in that the value
        // is preserved somewhere reachable, not silently lost.
        $client = Command::commandToClient('curl -F "0=value" http://localhost/');

        $this->assertEquals('value', $client->getRequest()->getData()->getRawData());
    }

    public function testFormFlagWithEmptyValueIsPreservedAsALegitimateField()
    {
        // Confirms a deliberately empty-value field (-F "foo=", a legitimate, real form field
        // per curl's own semantics) is NOT mistaken for the valueless-occurrence placeholder and
        // survives intact - the fix in extractCommandOptionValues() only skips an occurrence
        // when the flag itself had NO value at all (nothing followed it), not when the value is
        // present but happens to be an empty string after the 'name=' key.
        $client = Command::commandToClient('curl -F "foo=" http://localhost/');

        $this->assertEquals(['foo' => ''], $client->getRequest()->getData()->getData());
    }

    public function testRepeatedRequestMethodFlagLastOneWins()
    {
        // Regression test found in code review: -X/--request has the exact same "last
        // occurrence wins" semantics as -u/--user and -A/--user-agent, but was missed in the
        // original scope sweep. Request::setMethod() has a strict `string $method` typehint, so
        // a repeated -X (collected into an array by extractCommandOptionValues(), same as any
        // other repeated flag) crashed with an uncaught TypeError: Request::setMethod():
        // Argument #1 ($method) must be of type string, array given.
        $client = Command::commandToClient('curl -X POST -X PUT http://localhost/');

        $this->assertEquals('PUT', $client->getRequest()->getMethod());
    }

    public function testRepeatedRequestMethodFlagWithValuelessOccurrenceUsesTheRealOne()
    {
        // Companion case: the second -X occurrence is valueless, collecting a null alongside
        // the real value. Must not crash, and the one real value must win.
        $client = Command::commandToClient('curl -X POST -X http://localhost/');

        $this->assertEquals('POST', $client->getRequest()->getMethod());
    }

    public function testSingleRequestMethodFlagStillWorksUnaffected()
    {
        // Confirms the normalization added for the repeated-flag fix doesn't disturb the
        // ordinary, non-repeated -X path.
        $client = Command::commandToClient('curl -X PATCH http://localhost/');

        $this->assertEquals('PATCH', $client->getRequest()->getMethod());
    }

}
