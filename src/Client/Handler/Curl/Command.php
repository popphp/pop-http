<?php
declare(strict_types=1);
/**
 * Pop PHP Framework (https://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Pop\Http\Client\Handler\Curl;

use Pop\Http\Auth;
use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Handler\Curl;
use Pop\Http\Promise;

/**
 * HTTP client curl command class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@noladev.com>
 * @copyright  Copyright (c) 2009-2027 NOLA Interactive, LLC.
 * @license    https://www.popphp.org/license     New BSD License
 * @version    6.0.0
 */
class Command
{

    /**
     * Create a compatible command string to execute with the curl CLI application
     *
     * @param  Client $client
     * @return string
     */
    public static function clientToCommand(Client $client): string
    {
        $command        = 'curl';
        $currentOptions = [];

        // If client has a Curl handler, get current options before reset
        if (($client->hasHandler()) && ($client->getHandler() instanceof Curl) && ($client->getHandler()->hasOptions())) {
            $currentOptions = $client->getHandler()->getOptions();
        }

        $client->prepare();

        if (!($client->getHandler() instanceof Curl)) {
            throw new Exception('Error: The client object must use a Curl handler.');
        }

        $request = $client->getRequest();

        // Set return header
        if ($client->getHandler()->isReturnHeader()) {
            $command .= ' -i';
        }

        // Set method
        $method   = $request->getMethod();
        $command .= ' -X ' . $method;

        // Handle insecure settings
        if (($client->hasOption('verify_peer') && ($client->getOption('verify_peer'))) ||
            ($client->hasOption('allow_self_signed') && ($client->getOption('allow_self_signed'))) ||
            ($client->getHandler()->hasOption(CURLOPT_SSL_VERIFYHOST) && (!$client->getHandler()->getOption(CURLOPT_SSL_VERIFYHOST))) ||
            ($client->getHandler()->hasOption(CURLOPT_SSL_VERIFYPEER) && (!$client->getHandler()->getOption(CURLOPT_SSL_VERIFYPEER)))) {
            $command .= ' --insecure';
        }

        // Handle basic auth
        if (($client->hasAuth()) && ($client->getAuth()->isBasic())) {
            $command .= ' --basic -u "' . $client->getAuth()->getUsername() . ':' .  $client->getAuth()->getPassword() . '"';
            if ($request->hasHeader('Authorization')) {
                $request->removeHeader('Authorization');
            }
        }

        // Handle headers
        if ($request->hasHeaders()) {
            foreach ($request->getHeaderObjects() as $header) {
                if ((!str_contains($header->getValueAsString(), 'multipart/form-data')) &&
                    (!str_contains($header->getValueAsString(), 'x-www-form-urlencoded')) && ($header->getName() != 'Content-Length')) {
                    $command .= ' --header "' . $header  . '"';
                }
            }
        }

        // Handle data
        if ($request->hasData()) {
            // Multipart form data
            if ($request->isMultipart()) {
                $data = $request->getData()->toArray();
                foreach ($data as $key => $value) {
                    $command .= (isset($value['filename']) && file_exists($value['filename'])) ?
                        ' -F "' . $key . '=@' . $value['filename'] . '"' :
                        ' -F "' . http_build_query([$key => $value]) . '"';
                }
            // JSON data
            } else if ($request->isJson()) {
                $data = $request->getData()->toArray();
                foreach ($data as $key => $datum) {
                    if (isset($datum['filename']) && file_exists($datum['filename'])) {
                        $command .= ' --data @' . $datum['filename'];
                        unset($data[$key]);
                    }
                }
                if (!empty($data)) {
                    $json = json_encode($data);
                    if (str_contains($json, "'")) {
                        $json = str_replace("'", "\\'", $json);
                    }
                    $command .= " --data '" . $json . "'";
                }
            // XML data
            } else if ($request->isXml()) {
                $data = $request->getData()->toArray();
                foreach ($data as $key => $datum) {
                    if (isset($datum['filename']) && file_exists($datum['filename'])) {
                        $command .= ' --data @' . $datum['filename'];
                        unset($data[$key]);
                    }
                }

                if (!empty($data)) {
                    foreach ($data as $datum) {
                        if (str_contains($datum, "'")) {
                            $datum = str_replace("'", "\\'", $datum);
                        }
                        $command .= " --data '" . $datum . "'";
                    }
                }
            // URL-encoded data
            } else if (($request->getMethod() == 'GET') || ($request->isUrlEncoded()) ||
                !($request->hasRequestType())) {
                $command .= ' --data "' . $request->getData()->prepare()->getDataContent()  . '"';
            }
        }

        // Add body content as data
        if ($request->hasBody()) {
            $body = $request->getBodyContent();
            if (str_contains($body, "'")) {
                $body = str_replace("'", "\\'", $body);
            }
            $command .= " --data '" . $body . "'";
        }

        // Handle all other options
        $curlOptions =  $client->getHandler()->getOptions() + $currentOptions;
        foreach ($curlOptions as $curlOption => $curlOptionValue) {
            $curlOptionName = Options::getOptionNameByValue($curlOption);
            if (!Options::isOmitOption($curlOptionName)) {
                $commandOption = Options::getPhpOption($curlOptionName);
                $command .= (is_array($commandOption) && isset($commandOption[0])) ?
                    ' ' . $commandOption[0] : ' ' . $commandOption;
                if (Options::isValueOption($curlOptionName) && !empty($curlOptionValue)) {
                    $command .= ' ' . self::addQuotes((string)$curlOptionValue);
                }
            }
        }

        $command .= ' ' . self::addQuotes($request->getUriAsString());

        return $command;
    }

    /**
     * Create a client object from a command string from the Curl CLI application
     *
     * @param  string $command
     * @return Client
     */
    public static function commandToClient(string $command): Client
    {
        $command = trim($command);

        if (!str_starts_with($command, 'curl')) {
            throw new Exception("Error: The command isn't a valid cURL command.");
        }

        $command = substr($command, 4);
        $options = [];

        // No options
        if (!str_contains($command, '-')) {
            $requestUri = trim($command);
        // Else, parse options
        } else {
            $optionString = substr($command, 0, strrpos($command, ' '));
            $requestUri   = substr($command, (strrpos($command, ' ') + 1));
            $options      = self::parseCommandOptions($optionString);
        }

        $request = new Request(self::trimQuotes($requestUri));
        $curl    = new Curl();
        $files   = null;

        if (!empty($options)) {
            [$auth, $files] = self::convertCommandOptions($options, $curl, $request);
        }

        $client = new Client($request, $curl);

        if (!empty($auth)) {
            $client->setAuth($auth);
        }
        if (!empty($files)) {
            $client->setFiles($files, false);
        }

        return $client;
    }

    /**
     * Tokenize a command-line string into words, respecting single/double
     * quotes so a space-then-dash inside a quoted value isn't mistaken for
     * the start of a new option.
     *
     * Each token is returned as ['value' => string, 'quoted' => bool] rather
     * than a plain string: the quote characters themselves are consumed here
     * and never appear in 'value', so once tokenizing is done there is no way
     * to tell from the value alone whether a token like '-foo=bar' was a
     * quoted value or a real flag - callers (parseCommandOptions()) need the
     * 'quoted' flag to make that distinction. 'quoted' is true only when the
     * token's very FIRST character came from inside a quote (e.g. '-foo=bar'
     * or "-foo=bar"), not merely whether a quote appears anywhere in the
     * token - a compact flag+value like -d"foo=bar" (no space, unquoted -d
     * prefix) must still be recognized as starting with a real, unquoted
     * dash.
     *
     * @param  string $string
     * @return array
     */
    protected static function tokenize(string $string): array
    {
        $tokens    = [];
        $current   = '';
        $inQuote   = null;
        $hasToken  = false;
        $quoted    = false;

        for ($i = 0, $length = strlen($string); $i < $length; $i++) {
            $char = $string[$i];

            if ($inQuote !== null) {
                if ($char === $inQuote) {
                    $inQuote = null;
                } else {
                    $current .= $char;
                }
                continue;
            }

            if (($char === '"') || ($char === "'")) {
                // Only mark the token as "quoted" if this quote is the very first
                // character of the token - a quote appearing after some already-consumed
                // unquoted characters (e.g. -d"foo=bar") doesn't retroactively make the
                // token's leading dash a quoted one.
                if (!$hasToken) {
                    $quoted = true;
                }
                // Opening a quote starts a token even if its contents end up
                // empty (e.g. an empty '' or "" argument), so it isn't lost.
                $inQuote  = $char;
                $hasToken = true;
                continue;
            }

            if ($char === ' ') {
                if ($hasToken) {
                    $tokens[]  = ['value' => $current, 'quoted' => $quoted];
                    $current   = '';
                    $hasToken  = false;
                    $quoted    = false;
                }
                continue;
            }

            $current  .= $char;
            $hasToken  = true;
        }

        if ($hasToken) {
            $tokens[] = ['value' => $current, 'quoted' => $quoted];
        }

        return $tokens;
    }

    /**
     * Parse the CLI command options string
     *
     * @param  string $optionString
     * @return array
     */
    public static function parseCommandOptions(string $optionString): array
    {
        $tokens  = self::tokenize($optionString);
        $options = [];
        $current = null;

        foreach ($tokens as $token) {
            $value  = $token['value'];
            $quoted = $token['quoted'];

            // A quoted token that happens to start with '-' (e.g. --data '-foo=bar') is
            // normally a value continuation, never a new flag - only an UNQUOTED leading
            // dash starts a new option. Without this, a quoted value like '-foo=bar' gets
            // misread as a stray flag, leaving the preceding option (e.g. --data) with no
            // value at all.
            //
            // The exception: a quoted token that is ALSO a real, recognized CLI flag
            // (e.g. curl '-X' POST, or -X POST "-H" "Accept: text/plain") must still be
            // treated as a new flag - curl itself doesn't care whether a flag was quoted
            // on the shell, only whether the currently-accumulated option is still
            // awaiting its own value. $awaitingValue captures that: right after starting
            // $current = '--data' (no space yet), the very next token - even if quoted
            // and shaped like a real flag - is still --data's value, matching curl's
            // real semantics where a value-taking option always consumes whatever
            // immediately follows it. Once $current has a space in it (e.g. '-X POST'),
            // it's "complete," so the next quoted-and-recognized-flag token correctly
            // starts a NEW option. A null $current (the very first token) is never
            // "awaiting a value," so a quoted, recognized flag as the first token is
            // correctly treated as starting the first option.
            //
            // Critically, $current only "awaits a value" when it's actually a value-taking
            // flag (per Options::isValueOption()). A bare boolean flag like '-L' also has
            // no space right after it starts, but it never takes a value - so the very next
            // token, even if quoted and shaped like a real flag (e.g. curl '-L' '-X' POST),
            // must still be read as a NEW option rather than being swallowed as '-L's
            // (nonexistent) value.
            $isRecognizedFlag = str_starts_with($value, '-') && Options::isCommandOption($value);
            $awaitingValue    = ($current !== null) && !str_contains($current, ' ') && Options::isValueOption($current);

            if ((!$quoted && str_starts_with($value, '-')) || ($quoted && $isRecognizedFlag && !$awaitingValue)) {
                if ($current !== null) {
                    $options[] = $current;
                }
                $current = $value;
            } else if ($current !== null) {
                $current .= ' ' . self::addQuotes($value, str_contains($value, ' ') ? '"' : '');
            }
        }

        if ($current !== null) {
            $options[] = $current;
        }

        return $options;
    }

    /**
     * Extract command option values
     *
     * @param  array $options
     * @return array
     */
    public static function extractCommandOptionValues(array $options): array
    {
        $optionValues = [];

        foreach ($options as $option) {
            $opt = null;
            $val = null;
            if (str_starts_with($option, '--')) {
                if (str_contains($option, ' ')) {
                    $opt = substr($option, 0, strpos($option, ' '));
                    $val = substr($option, (strpos($option, ' ') + 1));
                } else {
                    $opt = $option;
                }
            } else {
                if (strlen($option) > 2) {
                    if (substr($option, 2, 1) == ' ') {
                        $opt = substr($option, 0, 2);
                        $val = substr($option, 3);
                    } else {
                        $opt = substr($option, 0, 2);
                        $val = substr($option, 2);
                    }
                } else {
                    $opt = $option;
                }
            }

            if (($opt == '-d') || ($opt == '--data') || ($opt == '-F') || ($opt == '--form')) {
                if ((($opt == '-F') || ($opt == '--form')) && ($val === null)) {
                    // A valueless -F/--form occurrence (nothing follows the flag - e.g. it's
                    // the last token before the request URI, as in 'curl -F "foo=bar" -F
                    // http://x/') contributes no real form field at all. Unlike -d/--data
                    // (which still merges a coalesced '' as raw string data below - see the
                    // defense-in-depth comment there), -F/--form must skip this occurrence
                    // entirely rather than merging a placeholder value into the array: once
                    // merged, a placeholder is structurally indistinguishable downstream from
                    // a legitimate field (parse_str() casts numeric-string keys like "0" to
                    // real int keys, and an intentionally empty value like -F "foo=" is a
                    // valid field too), so there's no way to filter it back out later without
                    // false-positiving on real data. Skipping it here, before it ever enters
                    // $optionValues, avoids the ambiguity altogether.
                    continue;
                }
                // Defense-in-depth: $val can still legitimately be null here for -d/--data
                // (e.g. a valueless -d/--data with truly nothing following it, such as
                // 'curl -X POST --data http://x/'). Coalesce to '' instead of letting a null
                // reach trimQuotes()'s string-typed parameter, which would throw a confusing
                // TypeError from an unrelated method rather than degrading gracefully.
                $val = self::trimQuotes($val ?? '');
                // Only -F/--form values are parsed into key/value pairs here: convertCommandOptions()
                // needs them pre-split into an array (see its "Handle form data" block). -d/--data is
                // left as the raw (unquoted) string so convertCommandOptions() can decide how to
                // interpret it based on the request's actual Content-Type, rather than guessing from
                // whether the value happens to contain an '=' (which false-positives on JSON bodies
                // like '{"filter":"a=b"}').
                if (($opt == '-F') || ($opt == '--form')) {
                    if (str_contains($val, '=') && !str_contains($val, '<?xml')) {
                        parse_str(self::trimQuotes($val), $val);
                    }
                }
            }

            if (isset($optionValues[$opt])) {
                if (!is_array($optionValues[$opt])) {
                    $optionValues[$opt] = [$optionValues[$opt]];
                }
                if (is_array($val)) {
                    $optionValues[$opt] = array_merge($optionValues[$opt], $val);
                } else {
                    $optionValues[$opt][] = $val;
                }
            } else {
                $optionValues[$opt] = $val;
            }
        }

        return $optionValues;
    }

    /**
     * Convert CLI options to usable values for the Curl handler and request
     *
     * @param  array   $options
     * @param  Curl    $curl
     * @param  Request $request
     * @return array
     */
    public static function convertCommandOptions(array $options, Curl $curl, Request $request): array
    {
        $optionValues = self::extractCommandOptionValues($options);
        $auth         = null;
        $files        = [];

        // Handle method
        // If forced GET method
        if (array_key_exists('-G', $optionValues) || array_key_exists('--get', $optionValues)) {
            $request->setMethod('GET');
            if (array_key_exists('-G', $optionValues)) {
                unset($optionValues['-G']);
            } else {
                unset($optionValues['--get']);
            }
            // If HEAD method
        } else if (array_key_exists('-I', $optionValues) || array_key_exists('--head', $optionValues)) {
            $request->setMethod('HEAD');
            if (array_key_exists('-I', $optionValues)) {
                unset($optionValues['-I']);
            } else {
                unset($optionValues['--head']);
            }
        // All other methods
        } else if (isset($optionValues['-X']) || isset($optionValues['--request'])) {
            // -X/--request is a "last occurrence wins" flag, like -u/--user and -A/--user-agent,
            // not cumulative - a repeated occurrence collects every value into an array (see
            // extractCommandOptionValues()), which Request::setMethod()'s strict `string $method`
            // typehint can't accept. Normalize via the same helper used for -u/--user and the
            // generic options loop; fall back to 'GET', matching Request's own default method,
            // for the (unlikely) case every repeated occurrence was valueless.
            $request->setMethod(self::lastNonNullOptionValue($optionValues['-X'] ?? $optionValues['--request']) ?? 'GET');
            if (isset($optionValues['-X'])) {
                unset($optionValues['-X']);
            } else {
                unset($optionValues['--request']);
            }
        }

        // Handle insecure settings
        if (array_key_exists('-k', $optionValues) || array_key_exists('--insecure', $optionValues)) {
            $curl->setOption(CURLOPT_SSL_VERIFYHOST, 0);
            $curl->setOption(CURLOPT_SSL_VERIFYPEER, 0);
        }

        // Handle headers
        if (isset($optionValues['-H']) || isset($optionValues['--header'])) {
            $headerOpts = ($optionValues['-H'] ?? $optionValues['--header']);
            if (is_array($headerOpts)) {
                // A repeated -H/--header flag with one valueless occurrence collects a null
                // into this array alongside the real values (see extractCommandOptionValues()).
                // Drop those null entries rather than coercing them to '' and handing an empty
                // string to trimQuotes()/addHeaders() - an empty header string isn't a valid
                // 'Name: Value' pair and would blow up downstream in Header::parse() instead.
                $headers = array_map(function ($value) {
                    return Command::trimQuotes($value);
                }, array_filter($headerOpts, function ($value) {
                    return $value !== null;
                }));
            } else {
                $headers = [Command::trimQuotes($headerOpts ?? '')];
            }

            $request->addHeaders($headers);

            if (isset($optionValues['-H'])) {
                unset($optionValues['-H']);
            } else {
                unset($optionValues['--header']);
            }
        }

        // Handle basic auth
        if ((!array_key_exists('--digest', $optionValues) || array_key_exists('--basic', $optionValues) || array_key_exists('--anyauth', $optionValues)) &&
            (isset($optionValues['-u']) || isset($optionValues['--user']))) {
            $userData = self::lastNonNullOptionValue($optionValues['-u'] ?? $optionValues['--user']);
            if (($userData !== null) && str_contains($userData, ':')) {
                [$username, $password] = explode(':', self::trimQuotes($userData), 2);
                $auth = Auth::createBasic($username, $password);
                if (isset($optionValues['-u'])) {
                    unset($optionValues['-u']);
                } else {
                    unset($optionValues['--user']);
                }
                if (array_key_exists('--basic', $optionValues)) {
                    unset($optionValues['--basic']);
                }
            }
        }

        // Handle JSON request
        if (array_key_exists('--json', $optionValues)) {
            $request->addHeaders([
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            unset($optionValues['--json']);
        }

        // Handle data
        if (isset($optionValues['-d']) || isset($optionValues['--data'])) {
            $data        = ($optionValues['-d'] ?? $optionValues['--data']);
            $contentType = $request->hasHeader('Content-Type') ? $request->getHeaderValueAsString('Content-Type') : null;

            // Multiple -d/--data occurrences are extracted as an array of raw string chunks
            // (curl's own behavior is to join repeated -d values with '&'), so collapse that
            // back into a single string before applying the same Content-Type dispatch used
            // for a single occurrence below.
            if (is_array($data) && (count($data) === count(array_filter($data, 'is_string')))) {
                $data = implode('&', $data);
            }

            if (is_string($data)) {
                if (str_starts_with($data, '@')) {
                    //&& file_exists(getcwd() . DIRECTORY_SEPARATOR . substr($data, 1))) {
                    $file = substr($data, 1);
                    if (!str_starts_with($file, '/')) {
                        $file = getcwd() . DIRECTORY_SEPARATOR . substr($data, 1);
                    }
                    if (file_exists($file)) {
                        $files[] = $file;
                    }
                } else if (($contentType !== null) && str_contains($contentType, 'json')) {
                    // A declared JSON Content-Type doesn't guarantee the body is actually valid
                    // JSON (e.g. a mismatched -d value). json_decode() returns null on failure,
                    // and passing null through to $request->setData() would throw a TypeError
                    // from Data::__construct() three calls away with no indication of what went
                    // wrong - so on decode failure, fall back to leaving $data as the raw string,
                    // matching this block's existing "leave as raw string" fallback philosophy.
                    // json_decode('null') is also VALID JSON (JSON_ERROR_NONE) that legitimately
                    // decodes to PHP null, so the success check alone isn't enough - a literal
                    // 'null' body must fall through to the same raw-string fallback too, rather
                    // than passing null through to setData() and crashing the same way.
                    $decoded = json_decode($data, true);
                    if ((json_last_error() === JSON_ERROR_NONE) && ($decoded !== null)) {
                        $data = $decoded;
                    }
                } else if (($contentType !== null) && str_contains($contentType, self::urlEncodedContentType())) {
                    parse_str($data, $data);
                } else if (($contentType === null) && str_contains($data, '=') && !str_contains($data, '<?xml')) {
                    // No explicit Content-Type: curl's own default behavior for -d/--data is to
                    // treat the value as application/x-www-form-urlencoded name=value pairs, so
                    // match that here (same shape heuristic previously applied during extraction)
                    // rather than leaving it as an opaque raw string.
                    parse_str($data, $data);
                }
                // Any other Content-Type (e.g. XML, or a custom type), or a plain value with no
                // '=' and no Content-Type: leave $data as the raw string, unchanged.
            }
            $request->setData($data);
            if (isset($optionValues['-d'])) {
                unset($optionValues['-d']);
            } else {
                unset($optionValues['--data']);
            }
        }

        // Handle form data
        if (isset($optionValues['-F']) || isset($optionValues['--form'])) {
            $data     = [];
            $formData = ($optionValues['-F'] ?? $optionValues['--form']);
            if (is_array($formData)) {
                // Note: a repeated -F/--form flag with one valueless occurrence no longer
                // leaves a bogus placeholder in this array at all - extractCommandOptionValues()
                // now skips a valueless -F/--form occurrence entirely at the source, since a
                // downstream filter here can't reliably tell a placeholder apart from a
                // legitimate field (parse_str() casts a numeric-string key like "0" to a real
                // int key, and -F "foo=" is a legitimately empty-but-real value) - see the
                // comment in extractCommandOptionValues() for the full reasoning.
                foreach ($formData as $key => $formDatum) {
                    if (str_starts_with($formDatum, '@')) {
                        $data[$key] = [
                            'filename'    => getcwd() . DIRECTORY_SEPARATOR . substr($formDatum, 1),
                            'contentType' => Client\Data::getMimeTypeFromFilename(substr($formDatum, 1))
                        ];
                    } else {
                        $data[$key] = $formDatum;
                    }
                }
            }

            $request->setData($data)
                ->setRequestType(Request::MULTIPART);

            if (isset($optionValues['-F'])) {
                unset($optionValues['-F']);
            } else {
                unset($optionValues['--form']);
            }
        }

        // Handle all other options
        //
        // extractCommandOptionValues() only ever produces complete, exact CLI flags as keys
        // (a full '--long-flag', or exactly the first 2 characters of a short flag) - never an
        // abbreviated/partial one - so the option always matches a $commandOptions key exactly.
        // That makes Options::getCommandOption() (an O(1) hash lookup, already keyed by exact
        // flag) equivalent to - and far cheaper than - scanning every entry of the ~150+-entry
        // inverse getPhpOptions() map with substring matching. The substring matching also had
        // a latent false-positive risk (e.g. '--cookie' is a substring of '--cookie-jar').
        foreach ($optionValues as $option => $value) {
            // A repeated single-value flag (e.g. -A/--user-agent) collects every occurrence
            // into an array (see extractCommandOptionValues()), but curl's real semantics for
            // these generic options is "last occurrence wins" - normalize once here so both
            // trimQuotes() calls below always receive a string|null, never an array.
            $value = self::lastNonNullOptionValue($value);

            if (Options::isOmitOption($option)) {
                continue;
            }

            $phpConstants = Options::getCommandOption($option);
            if ($phpConstants === null) {
                continue;
            }

            // A $commandOptions entry mapping one flag to multiple constants (e.g.
            // --connect-timeout => [CURLOPT_CONNECTTIMEOUT, CURLOPT_TIMEOUT, CURLOPT_TIMEOUT_MS])
            // is ordered by preference - only the first-listed constant is the semantically
            // correct one to actually set here (see the inline comments in $commandOptions).
            $phpConstant = is_array($phpConstants) ? reset($phpConstants) : $phpConstants;

            $optionValue = (Options::isValueOption($option))
                ? (Options::getValueOption($option) ?? self::trimQuotes($value ?? ''))
                : true;

            $curl->setOption(constant($phpConstant), $optionValue);
        }

        return [$auth, $files];
    }

    /**
     * The application/x-www-form-urlencoded content type string, broken out
     * as its own method purely so it isn't a magic string duplicated inline.
     *
     * @return string
     */
    protected static function urlEncodedContentType(): string
    {
        return Request::URLENCODED;
    }

    /**
     * extractCommandOptionValues() collects every occurrence of a repeated CLI flag into
     * an array. Most single-value options follow curl's own "last occurrence wins"
     * semantics when repeated (e.g. -u/--user, -A/--user-agent) - this extracts the last
     * non-null string from such an array, a bare scalar value unchanged, or null if every
     * repeated occurrence was valueless. Cumulative flags (-H/--header, -d/--data,
     * -F/--form) have their own dedicated array-handling logic and don't use this helper.
     *
     * @param  mixed $value
     * @return string|null
     */
    protected static function lastNonNullOptionValue(mixed $value): ?string
    {
        if (!is_array($value)) {
            return $value;
        }
        $strings = array_filter($value, 'is_string');
        return !empty($strings) ? end($strings) : null;
    }

    /**
     * Trim quotes from value
     *
     * @param  string  $value
     * @return string
     */
    public static function trimQuotes(string $value): string
    {
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1);
            $value = substr($value, 0, -1);
        }

        return $value;
    }

    /**
     * Trim quotes from value
     *
     * @param  string $value
     * @param  string $quote
     * @return string
     */
    public static function addQuotes(string $value, string $quote = '"'): string
    {
        if (!str_starts_with($value, '"') && !str_ends_with($value, '"') && !str_starts_with($value, "'") && !str_ends_with($value, "'")) {
            $value = $quote . $value . $quote;
        }

        return $value;
    }

}
