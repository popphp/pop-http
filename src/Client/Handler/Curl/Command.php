<?php
/**
 * Pop PHP Framework (http://www.popphp.org/)
 *
 * @link       https://github.com/popphp/popphp-framework
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
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
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.2.0
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

        // Set return header
        if ($client->getHandler()->isReturnHeader()) {
            $command .= ' -i';
        }

        // Set method
        $method   = $client->getRequest()->getMethod();
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
            if ($client->getRequest()->hasHeader('Authorization')) {
                $client->getRequest()->removeHeader('Authorization');
            }
        }

        // Handle headers
        if ($client->getRequest()->hasHeaders()) {
            foreach ($client->getRequest()->getHeaders() as $header) {
                if ((!str_contains($header->getValueAsString(), 'multipart/form-data')) && ($header->getName() != 'Content-Length')) {
                    $command .= ' --header "' . $header  . '"';
                }
            }
        }

        // Handle data
        if ($client->getRequest()->hasData()) {
            // Multipart form data
            if ($client->getRequest()->isMultipart()) {
                $data = $client->getRequest()->getData()->toArray();
                foreach ($data as $key => $value) {
                    $command .= (isset($value['filename']) && file_exists($value['filename'])) ?
                        ' -F "' . $key . '=@' . $value['filename'] . '"' :
                        ' -F "' . http_build_query([$key => $value]) . '"';
                }
            // JSON data
            } else if ($client->getRequest()->isJson()) {
                $data = $client->getRequest()->getData()->toArray();
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
            } else if ($client->getRequest()->isXml()) {
                $data = $client->getRequest()->getData()->toArray();
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
            } else if (($client->getRequest()->getMethod() == 'GET') || ($client->getRequest()->isUrlEncoded()) ||
                !($client->getRequest()->hasRequestType())) {
                $command .= ' --data "' . $client->getRequest()->getData()->prepare()->getDataContent()  . '"';
            }
        }

        // Add body content as data
        if ($client->getRequest()->hasBody()) {
            $body = $client->getRequest()->getBodyContent();
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
                    $command .= ' ' . self::addQuotes($curlOptionValue);
                }
            }
        }

        $command .= ' ' . self::addQuotes($client->getRequest()->getUriAsString());

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
     * Parse the CLI command options string
     *
     * @param  string $optionString
     * @return array
     */
    public static function parseCommandOptions(string $optionString): array
    {
        $options = [];
        $matches = [];

        preg_match_all('/\s[\-]{1,2}/', $optionString, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[0]) && isset($matches[0][0])) {
            foreach ($matches[0] as $i => $match) {
                if (isset($matches[0][$i + 1])) {
                    $length    = ($matches[0][$i + 1][1]) - $match[1] - 1;
                    $options[] = substr($optionString, $match[1] + 1, $length);
                } else {
                    $options[] = substr($optionString, $match[1] + 1);
                }
            }
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
                $val = self::trimQuotes($val);
                if (str_contains($val, '=') && !str_contains($val, '<?xml')) {
                    parse_str(self::trimQuotes($val), $val);
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
            $request->setMethod(($optionValues['-X'] ?? $optionValues['--request']));
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
                $headers = array_map(function ($value) {
                    return Command::trimQuotes($value);
                }, $headerOpts);
            } else {
                $headers = [Command::trimQuotes($headerOpts)];
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
            $userData = ($optionValues['-u'] ?? $optionValues['--user']);
            if (str_contains($userData, ':')) {
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
            $data = ($optionValues['-d'] ?? $optionValues['--data']);

            if ($request->hasHeader('Content-Type') && is_string($data)) {
                if (str_starts_with($data, '@')) {
                    //&& file_exists(getcwd() . DIRECTORY_SEPARATOR . substr($data, 1))) {
                    $file = substr($data, 1);
                    if (!str_starts_with($file, '/')) {
                        $file = getcwd() . DIRECTORY_SEPARATOR . substr($data, 1);
                    }
                    if (file_exists($file)) {
                        $files[] = $file;
                    }
                } else {
                    $contentType = $request->getHeaderValueAsString('Content-Type');
                    if ($contentType ==  Request::JSON) {
                        $data = json_decode($data, true);
                    } else if ($contentType == Request::URLENCODED) {
                        parse_str($data, $data);
                    }
                }
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
        foreach ($optionValues as $option => $value) {
            if (!Options::isOmitOption($option)) {
                foreach (Options::getPhpOptions() as $phpOption => $curlOption) {
                    if (is_array($curlOption)) {
                        foreach ($curlOption as $cOpt) {
                            if ((str_starts_with($option, '--') && str_contains($cOpt, $option)) || str_starts_with($cOpt, $option)) {
                                if (Options::isValueOption($option)) {
                                    $optionValue = Options::getValueOption($option) ?? self::trimQuotes($value);
                                } else {
                                    $optionValue = true;
                                }
                                $curl->setOption(constant($phpOption), $optionValue);
                                break;
                            }
                        }
                    } else if ((str_starts_with($option, '--') && str_contains($curlOption, $option)) || str_starts_with($curlOption, $option)) {
                        if (Options::isValueOption($option)) {
                            $optionValue = Options::getValueOption($option) ?? self::trimQuotes($value);
                        } else {
                            $optionValue = true;
                        }
                        $curl->setOption(constant($phpOption), $optionValue);
                        break;
                    }
                }
            }
        }

        return [$auth, $files];
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
