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

use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Handler\Curl;

/**
 * HTTP client curl command class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
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
        $command = 'curl';
        $client->prepare();

        if (!($client->getHandler() instanceof Curl)) {
            throw new Exception('Error: The client object must use a Curl handler.');
        }

        if ($client->getHandler()->isReturnHeader()) {
            $command .= ' -i';
        }

        $method   = $client->getRequest()->getMethod();
        $command .= ' -X ' . $method;

        if ($client->getRequest()->hasHeaders()) {
            foreach ($client->getRequest()->getHeaders() as $header) {
                $command .= ' --header "' . $header  . '"';
            }
        }

        if ($client->getRequest()->hasData()) {
            if ($client->getRequest()->isMultipartForm()) {
                $data = $client->getRequest()->getData(true);
                foreach ($data as $key => $value) {
                    $command .= (isset($value['filename']) && file_exists($value['filename'])) ?
                        ' -F "' . $key . '=@' . $value['filename'] . '"' :
                        ' -F "' . http_build_query([$key => $value]) . '"';
                }
            /**
             * TO-DO: Handle data from @file
             */
            } else if ($client->getRequest()->isJson()) {
                $command .= " --data '" . json_encode($client->getRequest()->getData(true)) . "'";
            } else if (($client->getRequest()->getMethod() == 'GET') || ($client->getRequest()->isUrlEncodedForm()) ||
                !($client->getRequest()->hasRequestType())) {
                $command .= ' --data "' . $client->getRequest()->getData()->prepareQueryString()  . '"';
            }
        }

        /**
         * TO-DO: handle other options
         */

        $command .= ' ' . self::addQuotes($client->getRequest()->getFullUriAsString());

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

        if (!empty($options)) {
            self::convertCommandOptions($options, $curl, $request);
        }

        return new Client($request, $curl);
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

            if ((($opt == '-d') || ($opt == '--data') || ($opt == '-F') || ($opt == '--form')) && str_contains($val, '=')) {
                parse_str(self::trimQuotes($val), $val);
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
     * @return void
     */
    public static function convertCommandOptions(array $options, Curl $curl, Request $request): void
    {
        $optionValues = self::extractCommandOptionValues($options);

        if (isset($optionValues['-X']) || isset($optionValues['--request'])) {
            $request->setMethod(($optionValues['-X'] ?? $optionValues['--request']));
            if (isset($optionValues['-X'])) {
                unset($optionValues['-X']);
            } else {
                unset($optionValues['--request']);
            }
        }
        /**
         * TO-DO: Handle --data from @file
         */
        if (isset($optionValues['-d']) || isset($optionValues['--data'])) {
            $request->setData(($optionValues['-d'] ?? $optionValues['--data']));
            if (isset($optionValues['-d'])) {
                unset($optionValues['-d']);
            } else {
                unset($optionValues['--data']);
            }
        }
        if (isset($optionValues['-F']) || isset($optionValues['--form'])) {
            $data     = [];
            $formData = ($optionValues['-F'] ?? $optionValues['--form']);
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

            $request->setData($data)
                ->setRequestType(Request::MULTIPART);

            if (isset($optionValues['-F'])) {
                unset($optionValues['-F']);
            } else {
                unset($optionValues['--form']);
            }
        }
        if (isset($optionValues['-H']) || isset($optionValues['--header'])) {
            $headers = array_map(function ($value) {
                return Command::trimQuotes($value);
            }, ($optionValues['-H'] ?? $optionValues['--header']));

            $request->addHeaders($headers);
            if (isset($optionValues['-H'])) {
                unset($optionValues['-H']);
            } else {
                unset($optionValues['--header']);
            }
        }

        foreach ($optionValues as $option => $value) {
            foreach (Options::getPhpOptions() as $phpOption => $curlOption) {
                if (is_array($curlOption)) {
                    foreach ($curlOption as $cOpt) {
                        if ((str_starts_with($option, '--') && str_contains($cOpt, $option)) || str_starts_with($cOpt, $option)) {
                            if (Options::isValueOption($option)) {
                                $optionValue = Options::getValueOption($option) ?? $value;
                            } else {
                                $optionValue = true;
                            }
                            $curl->setOption(constant($phpOption), $optionValue);
                            break;
                        }
                    }
                } else if ((str_starts_with($option, '--') && str_contains($curlOption, $option)) || str_starts_with($curlOption, $option)) {
                    if (Options::isValueOption($option)) {
                        $optionValue = Options::getValueOption($option) ?? $value;
                    } else {
                        $optionValue = true;
                    }
                    $curl->setOption(constant($phpOption), $optionValue);
                    break;
                }
            }
        }
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
