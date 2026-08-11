<?php

namespace Pop\Http\Test\Client\Handler\Curl;

use Pop\Http\Client\Handler\Curl;
use Pop\Http\Client\Handler\Curl\Command;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{

    public function testValidOption()
    {
        $this->assertTrue(Curl\Options::isValidOption('-i'));
        $this->assertFalse(Curl\Options::isValidOption('-BAD'));
    }

    public function testCommandOption()
    {
        $this->assertTrue(Curl\Options::isCommandOption('-i'));
        $this->assertFalse(Curl\Options::isCommandOption('-BAD'));
    }

    public function testPhpOption()
    {
        $this->assertTrue(Curl\Options::isPhpOption('CURLOPT_ABSTRACT_UNIX_SOCKET'));
        $this->assertFalse(Curl\Options::isPhpOption('-BAD'));
    }

    public function testValueOption()
    {
        $this->assertTrue(Curl\Options::isValueOption('--abstract-unix-socket'));
        $this->assertFalse(Curl\Options::isValueOption('-BAD'));
    }

    public function testBooleanOption()
    {
        $this->assertTrue(Curl\Options::isBooleanOption('-i'));
        $this->assertFalse(Curl\Options::isBooleanOption('--abstract-unix-socket'));
    }

    public function testGetCommandOptions()
    {
        $this->assertEquals('CURLOPT_ABSTRACT_UNIX_SOCKET', Curl\Options::getCommandOptions()['--abstract-unix-socket']);
        $this->assertEquals('CURLOPT_ABSTRACT_UNIX_SOCKET', Curl\Options::getCommandOption('--abstract-unix-socket'));
    }

    public function testGetPhpOptions()
    {
        $this->assertEquals('--abstract-unix-socket', Curl\Options::getPhpOptions()['CURLOPT_ABSTRACT_UNIX_SOCKET']);
        $this->assertEquals('--abstract-unix-socket', Curl\Options::getPhpOption('CURLOPT_ABSTRACT_UNIX_SOCKET'));
    }

    public function testGetValueOptions()
    {
        $this->assertEquals(CURL_HTTP_VERSION_1_1, Curl\Options::getValueOptions()['--http1.1']);
        $this->assertEquals(CURL_HTTP_VERSION_1_1, Curl\Options::getValueOption('--http1.1'));
    }

    public function testGetOptionValue()
    {
        $this->assertTrue(Curl\Options::hasOptionValueByName('CURLOPT_ABSTRACT_UNIX_SOCKET'));
        $this->assertTrue(Curl\Options::hasOptionNameByValue(10264));
        $this->assertEquals(10264, Curl\Options::getOptionValueByName('CURLOPT_ABSTRACT_UNIX_SOCKET'));
        $this->assertEquals('CURLOPT_ABSTRACT_UNIX_SOCKET', Curl\Options::getOptionNameByValue(10264));
    }

    public function testGetOmitOptions()
    {
        $this->assertTrue(is_array(Curl\Options::getOmitOptions()));
        $this->assertTrue(Curl\Options::isOmitOption('-k'));
    }

    public function testDerivedPhpOptionsMatchExisting()
    {
        // Snapshot of today's hand-written $phpOptions, captured before this task's
        // change, to prove the derived version is a faithful replacement. If this
        // assertion ever needs to change, it means a real mapping changed, not that
        // the test is wrong - review the diff carefully before updating it.
        // Using assertSame() instead of assertEquals() to enforce strict type checking
        // (e.g., catching if flag values accidentally become integers instead of strings)
        $existing = require __DIR__ . '/fixtures/php-options-snapshot.php';
        $derived  = Curl\Options::getPhpOptions();

        $this->assertSame($existing, $derived);
    }

    public function testCommandAndPhpOptionsAreInverses()
    {
        // Every command flag must map to a curl constant that maps back to
        // that same flag (directly or as one entry in a multi-flag list).
        foreach (Curl\Options::getCommandOptions() as $flag => $constants) {
            foreach ((array)$constants as $constant) {
                $backMapped = Curl\Options::getPhpOption($constant);
                $this->assertTrue(
                    ($backMapped === $flag) || (is_array($backMapped) && in_array($flag, $backMapped)),
                    "Flag '$flag' -> '$constant' does not map back to itself in \$phpOptions"
                );
            }
        }
    }

    public function testGetOptionValueByNameMatchesRealConstant()
    {
        $this->assertEquals(CURLOPT_URL, Curl\Options::getOptionValueByName('CURLOPT_URL'));
        $this->assertEquals(CURLOPT_POSTFIELDS, Curl\Options::getOptionValueByName('CURLOPT_POSTFIELDS'));
    }

    public function testGetOptionValueByNameReturnsNullForUnknownConstant()
    {
        $this->assertNull(Curl\Options::getOptionValueByName('CURLOPT_DOES_NOT_EXIST'));
    }

    public function testGetOptionNameByValueRoundTrips()
    {
        $this->assertEquals('CURLOPT_URL', Curl\Options::getOptionNameByValue(CURLOPT_URL));
    }

    public function testEveryCommandOptionConstantHasARealValue()
    {
        // Catches the exact bug class this task fixes: a hand-typed integer that
        // doesn't actually match the real curl constant it claims to represent.
        foreach (Curl\Options::getCommandOptions() as $constants) {
            foreach ((array)$constants as $constant) {
                if (defined($constant)) {
                    $this->assertEquals(
                        constant($constant), Curl\Options::getOptionValueByName($constant),
                        "$constant value mismatch"
                    );
                }
            }
        }
    }

    public function testGetOptionNameByValueMatchesOldTableForCollidingValues()
    {
        // Several curl-constant integer values are shared by more than one
        // CURLOPT_*/CURLSSLOPT_* name (e.g. CURLOPT_SSLVERSION and
        // CURLSSLOPT_AUTO_CLIENT_CERT both equal 32). The old hand-typed
        // $optionValues table resolved these deterministically via array_search()
        // (first-declared name wins). get_defined_constants() iteration order is
        // NOT guaranteed to agree, so getOptionNameByValue() must explicitly
        // prefer the constant that actually has a CLI-flag mapping, with alphabetical
        // order as the tiebreak when neither/both sides are mapped - reproducing the
        // old table's behavior exactly for every one of its former collisions.
        //
        // Ground truth extracted (not hand re-typed) from the pre-Task-2 table via
        // git show 5b406e0:src/Client/Handler/Curl/Options.php.
        $collisionWinners = require __DIR__ . '/fixtures/option-value-collision-winners.php';

        $this->assertNotEmpty($collisionWinners);

        foreach ($collisionWinners as $value => $expectedName) {
            $this->assertEquals(
                $expectedName, Curl\Options::getOptionNameByValue($value),
                "Value $value should resolve to '$expectedName' (matching the old table), " .
                "got '" . Curl\Options::getOptionNameByValue($value) . "'"
            );
        }
    }

    public function testGetOptionNameByValuePrefersSslVersionOverUnrelatedAlias()
    {
        // Named regression test for the specific collision the reviewer flagged as most
        // dangerous: CURLOPT_SSLVERSION is a real, settable curl option; the value it
        // shares (32) is also CURLSSLOPT_AUTO_CLIENT_CERT, an unrelated bitmask constant
        // for CURLOPT_SSL_OPTIONS that is not itself settable via CURLOPT_*.
        $this->assertEquals('CURLOPT_SSLVERSION', Curl\Options::getOptionNameByValue(32));
        $this->assertNotEquals('CURLSSLOPT_AUTO_CLIENT_CERT', Curl\Options::getOptionNameByValue(32));
    }

    public function testGetOptionNameByValuePrefersSslKeyPasswdOverUnmappedAliases()
    {
        // CURLOPT_SSLKEYPASSWD (=10026) is mapped to --pass; its aliases
        // CURLOPT_KEYPASSWD and CURLOPT_SSLCERTPASSWD are not mapped to any CLI flag.
        $this->assertEquals('CURLOPT_SSLKEYPASSWD', Curl\Options::getOptionNameByValue(10026));
        $this->assertEquals('--pass', Curl\Options::getPhpOption('CURLOPT_SSLKEYPASSWD'));
        $this->assertNull(Curl\Options::getPhpOption('CURLOPT_KEYPASSWD'));
    }

    public function testValueOptionsFlagKeysMatchCommandOptionsForDataAndForm()
    {
        // Regression test: $valueOptions' flag-keyed portion had a stale '-data' typo
        // (single dash, never recognized as a real CLI flag) instead of '--data', and was
        // missing '-F'/'--form' entirely, even though $commandOptions has correct entries
        // for all of these (all four map to CURLOPT_POSTFIELDS). No live behavior changes
        // here since these flags are all in $omitOptions and short-circuit before
        // isValueOption() is ever consulted for them - this test only locks in that the two
        // tables stay internally consistent for a future contributor editing one of them.
        $this->assertFalse(Curl\Options::isValueOption('-data'), "'-data' (single dash) should not be a recognized key");
        $this->assertTrue(Curl\Options::isValueOption('--data'));
        $this->assertTrue(Curl\Options::isValueOption('-F'));
        $this->assertTrue(Curl\Options::isValueOption('--form'));
    }

    public function testCommandOptionsDataAndFormFlagsAreAllValueOptions()
    {
        // Broader structural safeguard: every CLI flag in $commandOptions that maps to
        // CURLOPT_POSTFIELDS is, per real curl CLI semantics, a value-taking option and must
        // have a corresponding entry in $valueOptions' flag-keyed portion, so the two tables
        // can't silently drift apart again the way '-data'/-F/--form did.
        foreach (Curl\Options::getCommandOptions() as $flag => $constants) {
            if ((array)$constants === ['CURLOPT_POSTFIELDS'] || $constants === 'CURLOPT_POSTFIELDS') {
                $this->assertTrue(
                    Curl\Options::isValueOption($flag),
                    "Flag '$flag' maps to CURLOPT_POSTFIELDS but is missing from \$valueOptions"
                );
            }
        }
    }

    public function testTimeConditionShortAndLongFlagsAreSeparateValueOptions()
    {
        // Regression test: -z/--time-cond used to be a single combined '-z --time-cond'
        // dictionary key in both $commandOptions and $valueOptions, which real curl commands
        // can never tokenize to (each flag parses to just '-z' or '--time-cond' alone), so
        // the entry could never match in commandToClient() and emitted a malformed literal
        // '-z --time-cond' string in toCurlCommand(). Both flags must now be separate,
        // correctly-recognized keys mapping to the same constant.
        $this->assertTrue(Curl\Options::isCommandOption('-z'));
        $this->assertTrue(Curl\Options::isCommandOption('--time-cond'));
        $this->assertEquals('CURLOPT_TIMECONDITION', Curl\Options::getCommandOption('-z'));
        $this->assertEquals('CURLOPT_TIMECONDITION', Curl\Options::getCommandOption('--time-cond'));
        $this->assertFalse(Curl\Options::isCommandOption('-z --time-cond'));

        $this->assertTrue(Curl\Options::isValueOption('-z'));
        $this->assertTrue(Curl\Options::isValueOption('--time-cond'));
        $this->assertFalse(Curl\Options::isValueOption('-z --time-cond'));

        $this->assertEquals(['-z', '--time-cond'], Curl\Options::getPhpOption('CURLOPT_TIMECONDITION'));
    }

    public function testGetOptionNameByValuePrefersInfileOverReaddata()
    {
        // CURLOPT_INFILE (=10009) is used for PUT uploads (-T/--upload-file);
        // CURLOPT_READDATA shares the same value but has no CLI-flag mapping.
        $this->assertEquals('CURLOPT_INFILE', Curl\Options::getOptionNameByValue(10009));
        $this->assertEquals(['-T', '--upload-file'], Curl\Options::getPhpOption('CURLOPT_INFILE'));
        $this->assertNull(Curl\Options::getPhpOption('CURLOPT_READDATA'));
    }

    public function testConnectTimeoutResolvesToConnectTimeoutConstant()
    {
        // Regression test for the final whole-branch review finding: --connect-timeout's
        // $commandOptions entry lists ['CURLOPT_TIMEOUT', 'CURLOPT_CONNECTTIMEOUT',
        // 'CURLOPT_TIMEOUT_MS'] and, because $phpOptions is derived from $commandOptions in
        // iteration order and convertCommandOptions()'s generic matcher breaks on the first
        // match, CURLOPT_TIMEOUT (total transfer timeout) was silently winning instead of the
        // semantically-correct CURLOPT_CONNECTTIMEOUT (connection-phase timeout only).
        $client = Command::commandToClient('curl --connect-timeout 5 http://localhost/');

        $this->assertTrue($client->getHandler()->hasOption(CURLOPT_CONNECTTIMEOUT));
        $this->assertEquals('5', $client->getHandler()->getOption(CURLOPT_CONNECTTIMEOUT));
        $this->assertFalse($client->getHandler()->hasOption(CURLOPT_TIMEOUT));
    }

    public function testFirstListedConstantWinsForMultiConstantCommandOptions()
    {
        // Structural safeguard for the whole class of bug the connect-timeout regression above
        // belongs to: any $commandOptions entry mapping one CLI flag to multiple CURLOPT_*
        // constants relies on the FIRST-listed constant being the one that resolves in
        // convertCommandOptions()'s generic "Handle all other options" matcher (it iterates
        // Options::getPhpOptions(), which is derived from $commandOptions in declaration order,
        // and breaks on the first match). That makes declaration order silently load-bearing for
        // every such entry, not just --connect-timeout - a future reorder of any one of them
        // would reintroduce this exact bug with no test catching it. This iterates every
        // multi-constant entry and locks in that the first-listed constant is always the one
        // that ends up set.
        //
        // -X/--request, -u/--user, and -k/--insecure are excluded: convertCommandOptions()
        // resolves them via dedicated special-case blocks BEFORE the generic loop ever runs
        // (they're unset from $optionValues before reaching it), so they never exercise the
        // generic first-match mechanism this test guards.
        $excluded = ['-X', '--request', '-u', '--user', '-k', '--insecure'];

        $multiConstantEntries = 0;

        foreach (Curl\Options::getCommandOptions() as $flag => $constants) {
            if (!is_array($constants) || in_array($flag, $excluded, true)) {
                continue;
            }

            $multiConstantEntries++;

            $firstConstant = $constants[0];
            $value         = Curl\Options::isValueOption($flag) ? '1' : null;
            $command       = 'curl ' . $flag . ($value !== null ? ' ' . $value : '') . ' http://localhost/';

            $client = Command::commandToClient($command);

            $this->assertTrue(
                $client->getHandler()->hasOption(constant($firstConstant)),
                "Flag '$flag': expected first-listed constant '$firstConstant' to be set, but it wasn't"
            );
        }

        // Sanity check that this test is actually exercising entries (i.e. it isn't silently
        // vacuous if $commandOptions ever loses all its multi-constant entries).
        $this->assertGreaterThan(0, $multiConstantEntries);
    }

}
