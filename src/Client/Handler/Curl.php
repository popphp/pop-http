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
namespace Pop\Http\Client\Handler;

use Pop\Http\Auth;
use Pop\Http\Parser;
use Pop\Http\Client;
use Pop\Http\Client\Request;
use Pop\Http\Client\Response;
use Pop\Mime\Message;

/**
 * HTTP client curl handler class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Curl extends AbstractCurl
{

    /**
     * Curl CLI command options map for curl CLI translation
     * @var array
     */
    protected static $cliOptionsMap = [
        '--anyauth'        => '', //                             Use any auth, tell curl to figure it out
        '--basic'          => '', //                             Use basic auth
        '--digest'         => '', //                             Use digest auth
        '--data-ascii'     => '', // --data-ascii <data>         This is just an alias for -d, --data.
        '--data-binary'    => '', // --data-binary <data>        Transmit binary data with no processing
        '--data-raw'       => '', // --data-raw <data>           This posts data similarly to -d, --data but without the special interpretation of the @ character.
        '--data-urlencode' => '', // --data-urlencode <data>     This posts data, similar to the other -d, --data options with the exception that this performs URL-encoding.
        '-q|--disable'     => '', //                             If used as the first parameter on the command line, the curlrc config file will not be read and used.
        '--form-escape'    => '', //                             Tells curl to pass on names of multipart form fields and files using backslash-escaping instead of percent-encoding.
        '--form-string'    => '', // --form-string <name=string> Similar to -F, --form except that the value string for the named parameter is used literally.
        '-F|--form'        => '', // -F, --form <name=content>   Fets curl emulate a filled-in form in which a user has pressed the submit button.
        '-G|--get'         => '', //                             Force GET request
        '-I|--head'        => '', //                             Force HEAD request
        '--json'           => '', // --json <data>               Shortcut for --data <data> --header "Content-Type: application/json" --header "Accept: application/json"
        '-L|--location'    => '', //                             Make curl redo the request on the redirected location
        '--raw'            => '', //                             Disable all internal HTTP decoding
    ];

    /**
     * Curl PHP options map for curl CLI translation
     * @var array
     */
    protected static $phpOptionsMap = [
        'CURLOPT_ABSTRACT_UNIX_SOCKET'       => '--abstract-unix-socket', // --abstract-unix-socket <path>
        'CURLOPT_ALTSVC'                     => '--alt-svc', // --alt-svc <filename>
        'CURLOPT_APPEND'                     => '-a',
        'CURLOPT_AWS_SIGV4'                  => '--aws-sigv4', // --aws-sigv4 <provider1[:provider2[:region[:service]]]>
        'CURLOPT_CAINFO'                     => '--cacert', // --cacert <file>
        'CURLOPT_CAPATH'                     => '--capath', // --capath <dir>
        'CURLOPT_CONNECTTIMEOUT'             => '--connect-timeout', // --connect-timeout <fractional seconds>
        'CURLOPT_CONNECT_TO'                 => '--connect-to', // --connect-to <HOST1:PORT1:HOST2:PORT2>
        'CURLOPT_COOKIE'                     => '-b|--cookie', // -b, --cookie <data|filename>
        'CURLOPT_COOKIEJAR'                  => '-c|--cookie-jar', // -c, --cookie-jar <filename>
        'CURLOPT_CRLF'                       => '--crlf',
        'CURLOPT_CRLFILE'                    => '--crlfile', // --crlfile <file>
        'CURLOPT_CUSTOMREQUEST'              => '-X|--request', // -X, --request <method>
        'CURLOPT_DISALLOW_USERNAME_IN_URL'   => '--disallow-username-in-url',
        'CURLOPT_DNS_INTERFACE'              => '--dns-interface', // --dns-interface <interface>
        'CURLOPT_DNS_LOCAL_IP4'              => '--dns-ipv4-addr', // --dns-ipv4-addr <address>
        'CURLOPT_DNS_LOCAL_IP6'              => '--dns-ipv6-addr', // --dns-ipv6-addr <address>
        'CURLOPT_DNS_SERVERS'                => '--dns-servers', // --dns-servers <addresses>
        'CURLOPT_DOH_SSL_VERIFYHOST'         => '--doh-insecure|--no-doh-insecure', // Verify the DNS-over-HTTPS server's SSL certificate name fields against the host name. Available as of PHP 8.2.0 and cURL 7.76.0.
        'CURLOPT_DOH_SSL_VERIFYPEER'         => '--doh-insecure|--no-doh-insecure', // Verify the authenticity of the DNS-over-HTTPS server's SSL certificate. Available as of PHP 8.2.0 and cURL 7.76.0.
        'CURLOPT_DOH_SSL_VERIFYSTATUS'       => '--doh-cert-status', // Tell cURL to verify the status of the DNS-over-HTTPS server certificate using the "Certificate Status Request" TLS extension (OCSP stapling). Available as of PHP 8.2.0 and cURL 7.76.0.
        'CURLOPT_DOH_URL'                    => '--doh-url <url>',   // --doh-url <url> Provides the DNS-over-HTTPS URL. Available as of PHP 8.1.0 and cURL 7.62.0.
        'CURLOPT_EXPECT_100_TIMEOUT_MS'      => '--expect100-timeout', // --expect100-timeout <seconds>
        'CURLOPT_FTPPORT'                    => '-P|--ftp-port', // -P, --ftp-port <address>
        'CURLOPT_FTP_ACCOUNT'                => '--ftp-account', // --ftp-account <data>
        'CURLOPT_FTP_ALTERNATIVE_TO_USER'    => '--ftp-alternative-to-user', // --ftp-alternative-to-user <command>
        'CURLOPT_FTP_CREATE_MISSING_DIRS'    => '--ftp-create-dirs',
        'CURLOPT_FTP_FILEMETHOD'             => '--ftp-method', // --ftp-method <method>
        'CURLOPT_FTP_SKIP_PASV_IP'           => '--ftp-skip-pasv-ip|--ftp-pasv',
        'CURLOPT_FTP_SSL_CCC'                => '--ftp-ssl-ccc',
        'CURLOPT_FTP_USE_EPRT'               => '--disable-eprt',
        'CURLOPT_FTP_USE_EPSV'               => '--disable-epsv',
        'CURLOPT_FTP_USE_PRET'               => '--ftp-pret',
        'CURLOPT_GSSAPI_DELEGATION'          => '--delegation', // --delegation <level>
        'CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS'  => '--happy-eyeballs-timeout-ms', // --happy-eyeballs-timeout-ms <milliseconds>
        'CURLOPT_HAPROXYPROTOCOL'            => '--haproxy-protocol',
        'CURLOPT_HEADER'                     => '-i|--include',
        'CURLOPT_HSTS'                       => '--hsts', // --hsts <filename>
        'CURLOPT_HTTP09_ALLOWED'             => '--http0.9',
        'CURLOPT_HTTPHEADER'                 => '-H|--header', // -H, --header <header/@file>
        'CURLOPT_HTTPPROXYTUNNEL'            => '-p|--proxytunnel',
        'CURLOPT_HTTP_VERSION'               => '-0|--http1.0|--http1.1|--http2',
        'CURLOPT_IGNORE_CONTENT_LENGTH'      => '--ignore-content-length',
        'CURLOPT_INFILE'                     => '-T|--upload-file',  // -T, --upload-file <file> (Used with PUT)
        'CURLOPT_INTERFACE'                  => '--interface', // --interface <name>
        'CURLOPT_KRBLEVEL'                   => '--krb', // --krb <level>
        'CURLOPT_LOCALPORT'                  => '--local-port', // --local-port <num/range>
        'CURLOPT_LOCALPORTRANGE'             => '--local-port', // --local-port <num/range>
        'CURLOPT_LOGIN_OPTIONS'              => '--login-options', // --login-options <options>
        'CURLOPT_LOW_SPEED_LIMIT'            => '-Y|--speed-limit', // -Y, --speed-limit <speed>
        'CURLOPT_LOW_SPEED_TIME'             => '-y|--speed-time', // -y, --speed-time <seconds>
        'CURLOPT_MAIL_AUTH'                  => '--mail-auth', // --mail-auth <address>
        'CURLOPT_MAIL_FROM'                  => '--mail-from', // --mail-from <address>
        'CURLOPT_MAIL_RCPT'                  => '--mail-rcpt', // --mail-rcpt <address>
        'CURLOPT_MAIL_RCPT_ALLLOWFAILS'      => '--mail-rcpt-allowfails',
        'CURLOPT_MAXFILESIZE'                => '--max-filesize', // --max-filesize <bytes>
        'CURLOPT_MAXLIFETIME_CONN'           => '-m|--max-time', // -m, --max-time <fractional seconds>
        'CURLOPT_MAXREDIRS'                  => '--max-redirs', // --max-redirs <num>
        'CURLOPT_NETRC'                      => '-n|--netrc',
        'CURLOPT_NETRC_FILE'                 => '--netrc-file', // --netrc-file <filename>
        'CURLOPT_NOPROGRESS'                 => '--no-progress-meter',
        'CURLOPT_NOPROXY'                    => '--noproxy', // --noproxy <no-proxy-list>
        'CURLOPT_PASSWORD'                   => '-u|--user', // -u, --user <user:password>
        'CURLOPT_POST'                       => '-X|--request', // -X, --request <method>',
        'CURLOPT_POSTFIELDS'                 => '-d|-data', // -d, --data <data>
        'CURLOPT_PROXY'                      => '-x|--proxy', // -x, --proxy [protocol://]host[:port]
        'CURLOPT_PROXYAUTH'                  => '--proxy-basic|--proxy-digest|--proxy-anyauth',
        'CURLOPT_PROXYHEADER'                => '--proxy-header', // --proxy-header <header/@file>
        'CURLOPT_PROXYUSERPWD'               => '-U|--proxy-user', // -U, --proxy-user <user:password>
        'CURLOPT_PROXY_CAINFO'               => '--proxy-cacert', // --proxy-cacert <file>
        'CURLOPT_PROXY_CAPATH'               => '--proxy-capath', // --proxy-capath <dir>
        'CURLOPT_PROXY_CRLFILE'              => '--proxy-crlfile', // --proxy-crlfile <file>
        'CURLOPT_PROXY_KEYPASSWD'            => '--proxy-pass', // --proxy-pass <phrase>
        'CURLOPT_PROXY_PINNEDPUBLICKEY'      => '--proxy-pinnedpubkey', // --proxy-pinnedpubkey <hashes>
        'CURLOPT_PROXY_SERVICE_NAME'         => '--proxy-service-name', // --proxy-service-name <name>
        'CURLOPT_PROXY_SSLCERT'              => '--proxy-cert', // --proxy-cert <cert[:passwd]>
        'CURLOPT_PROXY_SSLCERTTYPE'          => '--proxy-cert-type', // --proxy-cert-type <type>
        'CURLOPT_PROXY_SSLKEY'               => '--proxy-key', // --proxy-key <key>
        'CURLOPT_PROXY_SSLKEYTYPE'           => '--proxy-key-type', // --proxy-key-type <type>
        'CURLOPT_PROXY_SSLVERSION'           => '-2|--sslv2|-3|--sslv3',
        'CURLOPT_PROXY_SSL_CIPHER_LIST'      => '--proxy-ciphers', // --proxy-ciphers <list>
        'CURLOPT_PROXY_SSL_VERIFYHOST'       => '--proxy-insecure',
        'CURLOPT_PROXY_SSL_VERIFYPEER'       => '--proxy-insecure',
        'CURLOPT_PROXY_TLS13_CIPHERS'        => '--proxy-tls13-ciphers', // --proxy-tls13-ciphers <ciphersuite list>
        'CURLOPT_PROXY_TLSAUTH_PASSWORD'     => '--proxy-tlspassword', // --proxy-tlspassword <string>
        'CURLOPT_PROXY_TLSAUTH_TYPE'         => '--proxy-tlsauthtype', // --proxy-tlsauthtype <type>
        'CURLOPT_PROXY_TLSAUTH_USERNAME'     => '--proxy-tlsuser', // --proxy-tlsuser <name>
        'CURLOPT_PUT'                        => '-X|--request', // -X, --request <method>',
        'CURLOPT_QUOTE'                      => '-Q|--quote', // -Q, --quote <command>
        'CURLOPT_RANDOM_FILE'                => '--random-file', // --random-file <file>
        'CURLOPT_RANGE'                      => '-r, --range', // -r, --range <range>
        'CURLOPT_REFERER'                    => '-e, --referer', // -e, --referer <url>
        'CURLOPT_RESOLVE'                    => '--resolve', // --resolve <[+]host:port:addr[,addr]...>
        'CURLOPT_SASL_AUTHZID'               => '--sasl-authzid', // --sasl-authzid <identity>
        'CURLOPT_SASL_IR'                    => '--sasl-ir',
        'CURLOPT_SERVICE_NAME'               => '--service-name', // --service-name <name>
        'CURLOPT_SOCKS5_AUTH'                => '--socks5-basic',
        'CURLOPT_SOCKS5_GSSAPI_NEC'          => '--socks5-gssapi-nec',
        'CURLOPT_SOCKS5_GSSAPI_SERVICE'      => '--socks5-gssapi-service', // --socks5-gssapi-service <name>
        'CURLOPT_SSH_COMPRESSION'            => '--compressed-ssh',
        'CURLOPT_SSH_HOST_PUBLIC_KEY_MD5'    => '--hostpubmd5', // --hostpubmd5 <md5>
        'CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256' => '--hostpubsha256', // --hostpubsha256 <sha256>
        'CURLOPT_SSH_PUBLIC_KEYFILE'         => '--pubkey', // --pubkey <key>
        'CURLOPT_SSLCERT'                    => '-E|--cert', // -E, --cert <certificate[:password]>
        'CURLOPT_SSLCERTTYPE'                => '--cert-type', // --cert-type <type>
        'CURLOPT_SSLENGINE'                  => '--engine', // --engine <name>
        'CURLOPT_SSLKEY'                     => '--key', // --key <key>
        'CURLOPT_SSLKEYPASSWD'               => '--pass', // --pass <phrase>
        'CURLOPT_SSLKEYTYPE'                 => '--key-type', // --key-type <type>
        'CURLOPT_SSL_CIPHER_LIST'            => '--ciphers', // --ciphers <list of ciphers> i.e. ECDHE-ECDSA-AES256-CCM8
        'CURLOPT_SSL_EC_CURVES'              => '--curves', // --curves <algorithm list>
        'CURLOPT_SSL_ENABLE_ALPN'            => '--alpn|--no-alpn',
        'CURLOPT_SSL_ENABLE_NPN'             => '--npn|--no-npn',
        'CURLOPT_SSL_FALSESTART'             => '--false-start',
        'CURLOPT_SSL_SESSIONID_CACHE'        => '--no-sessionid',
        'CURLOPT_SSL_VERIFYHOST'             => '-k|--insecure',
        'CURLOPT_SSL_VERIFYPEER'             => '-k|--insecure',
        'CURLOPT_SSL_VERIFYSTATUS'           => '--cert-status',
        'CURLOPT_STDERR'                     => '--stderr', // --stderr <file>
        'CURLOPT_SUPPRESS_CONNECT_HEADERS'   => '--suppress-connect-headers',
        'CURLOPT_TCP_FASTOPEN'               => '--tcp-fastopen',
        'CURLOPT_TCP_KEEPALIVE'              => '--keepalive-time', // --keepalive-time <seconds>
        'CURLOPT_TCP_NODELAY'                => '--tcp-nodelay',
        'CURLOPT_TELNETOPTIONS'              => '-t|--telnet-option', // -t, --telnet-option <opt=val>
        'CURLOPT_TFTP_BLKSIZE'               => '--tftp-blksize', // --tftp-blksize <value>
        'CURLOPT_TFTP_NO_OPTIONS'            => '--tftp-no-options',
        'CURLOPT_TIMECONDITION'              => '-z, --time-cond', // -z, --time-cond <time>
        'CURLOPT_TIMEOUT'                    => '--connect-timeout', // --connect-timeout <fractional seconds>
        'CURLOPT_TIMEOUT_MS'                 => '--connect-timeout', // --connect-timeout <fractional seconds> (MS needs to be converted to seconds),
        'CURLOPT_TLS13_CIPHERS'              => '--tls13-ciphers', // --tls13-ciphers <ciphersuite list>
        'CURLOPT_TLSAUTH_PASSWORD'           => '--tlspassword', // --tlspassword <string>
        'CURLOPT_TLSAUTH_TYPE'               => '--tlsauthtype', // --tlsauthtype <type>
        'CURLOPT_TLSAUTH_USERNAME'           => '--tlsuser', // --tlsuser <name>
        'CURLOPT_TRANSFER_ENCODING'          => '--tr-encoding|--no-tr-encoding',
        'CURLOPT_UNIX_SOCKET_PATH'           => '--unix-socket', // --unix-socket <path>
        'CURLOPT_URL'                        => '--url', // --url <url> (Used for config files, unnecessary on the CLI)
        'CURLOPT_USERAGENT'                  => '-A|--user-agent', // -A, --user-agent <name>
        'CURLOPT_USERNAME'                   => '-u|--user', // -u, --user <user:password>
        'CURLOPT_USERPWD'                    => '-u|--user', // -u, --user <user:password>
        'CURLOPT_USE_SSL'                    => '--ssl|--ssl-reqd', // Attempts to force server to use secure connection',
        'CURLOPT_VERBOSE'                    => '-v|--verbose',
        'CURLOPT_XOAUTH2_BEARER'             => '--oauth2-bearer', // --oauth2-bearer <token>
        'CURLSSLOPT_ALLOW_BEAST'             => '--ssl-allow-beast',
        'CURLSSLOPT_AUTO_CLIENT_CERT'        => '--ssl-auto-client-cert',
        'CURLSSLOPT_NO_REVOKE'               => '--ssl-no-revoke',
        'CURLSSLOPT_REVOKE_BEST_EFFORT'      => '--ssl-revoke-best-effort',
    ];

    /**
     * Set Curl option to return the transfer (set to true by default)
     *
     * @param  bool $transfer
     * @return Curl
     */
    public function setReturnTransfer(bool $transfer = true): Curl
    {
        $this->setOption(CURLOPT_RETURNTRANSFER, (bool)$transfer);
        return $this;
    }

    /**
     * Set Curl option to return the headers (set to true by default)
     *
     * @param  bool $header
     * @return Curl
     */
    public function setReturnHeader(bool $header = true): Curl
    {
        $this->setOption(CURLOPT_HEADER, (bool)$header);
        return $this;
    }

    /**
     * Set Curl option to set verify peer (verifies the domain's SSL cert)
     *
     * @param  bool $verify
     * @return Curl
     */
    public function setVerifyPeer(bool $verify = true): Curl
    {
        $this->setOption(CURLOPT_SSL_VERIFYPEER, (bool)$verify);
        return $this;
    }

    /**
     * Set Curl option to set to allow self-signed certs
     *
     * @param  bool $allow
     * @return Curl
     */
    public function allowSelfSigned(bool $allow = true): Curl
    {
        $this->setOption(CURLOPT_SSL_VERIFYHOST, (bool)$allow);
        return $this;
    }

    /**
     * Check if Curl is set to return transfer
     *
     * @return bool
     */
    public function isReturnTransfer(): bool
    {
        return (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER] == true));
    }

    /**
     * Check if Curl is set to return header
     *
     * @return bool
     */
    public function isReturnHeader(): bool
    {
        return (isset($this->options[CURLOPT_HEADER]) && ($this->options[CURLOPT_HEADER] == true));
    }

    /**
     * Check if Curl is set to verify peer
     *
     * @return bool
     */
    public function isVerifyPeer(): bool
    {
        return (isset($this->options[CURLOPT_SSL_VERIFYPEER]) && ($this->options[CURLOPT_SSL_VERIFYPEER] == true));
    }

    /**
     * Check if Curl is set to allow self-signed certs
     *
     * @return bool
     */
    public function isAllowSelfSigned(): bool
    {
        return (isset($this->options[CURLOPT_SSL_VERIFYHOST]) && ($this->options[CURLOPT_SSL_VERIFYHOST] == true));
    }

    /**
     * Return the Curl last info
     *
     * @param  ?int $opt
     * @return array|string
     */
    public function getInfo(?int $opt = null): array|string
    {
        return ($opt !== null) ? curl_getinfo($this->resource, $opt) : curl_getinfo($this->resource);
    }

    /**
     * Method to prepare the handler
     *
     * @param Request $request
     * @param  ?Auth $auth
     * @throws Exception|\Pop\Http\Exception
     * @return Curl
     */
    public function prepare(Request $request, ?Auth $auth = null): Curl
    {
        $uri = $request->getUriAsString();

        // Add auth header
        if ($auth !== null) {
            $request->addHeader($auth->createAuthHeader());
        }

        // If request has data
        if ($request->hasData()) {
            // Append GET query string to URL
            if (($request->isGet()) && ((!$request->hasHeader('Content-Type')) ||
                    ($request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded'))) {
                $uri .= $request->getData()->prepareQueryString(true);
            // Else, prepare request data for transmission
            } else {
                // If request is JSON
                if ($request->getHeaderValue('Content-Type') == 'application/json') {
                    $content = json_encode($request->getData(true), JSON_PRETTY_PRINT);
                    $request->addHeader('Content-Length', strlen($content));
                    $this->setOption(CURLOPT_POSTFIELDS, $content);
                // If request is a URL-encoded form
                } else if ($request->getHeaderValue('Content-Type') == 'application/x-www-form-urlencoded') {
                    $request->addHeader('Content-Length', $request->getData()->getQueryStringLength());
                    $this->setOption(CURLOPT_POSTFIELDS, $request->getData()->prepareQueryString());
                // Else, if request is a multipart form
                } else if ($request->isMultipartForm()) {
                    $formMessage = Message::createForm($request->getData(true));
                    $header      = $formMessage->getHeader('Content-Type');
                    $content     = $formMessage->render(false);
                    $formMessage->removeHeader('Content-Type');
                    $request->addHeader($header)
                        ->addHeader('Content-Length', strlen($content));
                    $this->setOption(CURLOPT_POSTFIELDS, $content);
                // Else, basic request with data
                } else {
                    $this->setOption(CURLOPT_POSTFIELDS, $request->getData(true));
                    if (!$request->hasHeader('Content-Type')) {
                        $request->addHeader('Content-Type', 'application/x-www-form-urlencoded');
                    }
                }
            }
        // Else, if request has raw body content
        } else if ($request->hasBodyContent()) {
            $request->addHeader('Content-Length', strlen($request->getBodyContent()));
            $this->setOption(CURLOPT_POSTFIELDS, $request->getBodyContent());
        }

        if ($request->hasHeaders()) {
            $headers = [];
            foreach ($request->getHeaders() as $header) {
                $headers[] = $header->render();
            }
            $this->setOption(CURLOPT_HTTPHEADER, $headers);
        }

        $this->setOption(CURLOPT_URL, $uri);

        return $this;
    }

    /**
     * Method to send the request
     *
     * @throws Exception
     * @return Response
     */
    public function send(): Response
    {
        $this->response = curl_exec($this->resource);

        if ($this->response === false) {
            throw new Exception('Error: ' . curl_errno($this->resource) . ' => ' . curl_error($this->resource) . '.');
        }

        return $this->parseResponse();
    }

    /**
     * Parse the response
     *
     * @return Response
     */
    public function parseResponse(): Response
    {
        $response = new Response();

        // If the CURLOPT_RETURNTRANSFER option is set, get the response body and parse the headers.
        if (isset($this->options[CURLOPT_RETURNTRANSFER]) && ($this->options[CURLOPT_RETURNTRANSFER])) {
            $headerSize = $this->getInfo(CURLINFO_HEADER_SIZE);
            if ($this->options[CURLOPT_HEADER]) {
                $parsedHeaders = Parser::parseHeaders(substr($this->response, 0, $headerSize));
                $response->setVersion($parsedHeaders['version']);
                $response->setCode($parsedHeaders['code']);
                $response->setMessage($parsedHeaders['message']);
                $response->addHeaders($parsedHeaders['headers']);
                $response->setBody(substr($this->response, $headerSize));
            } else {
                $response->setBody($this->response);
            }
        }

        if ($response->hasHeader('Content-Encoding')) {
            $response->decodeBodyContent();
        }

        return $response;
    }

    /**
     * Method to reset the handler
     *
     * @return Curl
     */
    public function reset(): Curl
    {
        $this->response = null;
        return $this;
    }

    /**
     * Close the handler connection
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->hasResource()) {
            curl_close($this->resource);
            $this->resource = null;
            $this->response = null;
            $this->options  = [];
        }
    }


    /**
     * Create a compatible string to execute with the curl CLI application
     *
     * @param  Client $client
     * @return string
     */
    public static function clientToCli(Client $client): string
    {
        $command = 'curl';

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

        if (($method !== 'GET') && ($client->getRequest()->hasData())) {
            $command .= ' --data "' . $client->getRequest()->getData()->prepareQueryString()  . '"';
        }

        $command .= ' "' . $client->getRequest()->getFullUriAsString()  . '"';

        return $command;
    }

    /**
     * Create a client object from a command string from the Curl CLI application
     *
     * @param  string $command
     * @return Client
     */
    public static function cliToClient(string $command): Client
    {
        if (!str_starts_with($command, 'curl')) {
            throw new Exception("Error: The command isn't a valid cURL command.");
        }

        $command      = substr($command, 4);
        $optionString = null;
        $requestUri   = null;
        $options      = [];

        if (!str_contains($command, '-')) {
            $requestUri = trim($command);
        } else {
            $optionString = substr($command, 0, strrpos($command, ' '));
            $requestUri   = substr($command, (strrpos($command, ' ') + 1));
            $matches      = [];

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
        }

        $request = new Request($requestUri);
        $curl    = new self();

        if (!empty($options)) {
            self::convertCliOptions($options, $curl, $request);
        }

        $client = new Client($request, $curl);

        return $client;
    }

    /**
     * Convert CLI options to usable values for the Curl handler and request
     *
     * @param  array   $options
     * @param  Curl    $curl
     * @param  Request $request
     * @return void
     */
    public static function convertCliOptions(array $options, Curl $curl, Request $request): void
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

            if (($opt == '-d') || ($opt == '--data') && str_contains($val, '=')) {
                parse_str($val, $val);
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

        if (isset($optionValues['-X']) || isset($optionValues['--request'])) {
            $request->setMethod(($optionValues['-X'] ?? $optionValues['--request']));
            if (isset($optionValues['-X'])) {
                unset($optionValues['-X']);
            } else {
                unset($optionValues['--request']);
            }
        }
        if (isset($optionValues['-d']) || isset($optionValues['--data'])) {
            $request->setData(($optionValues['-d'] ?? $optionValues['--data']));
            if (isset($optionValues['-d'])) {
                unset($optionValues['-d']);
            } else {
                unset($optionValues['--data']);
            }
        }
        if (isset($optionValues['-H']) || isset($optionValues['--header'])) {
            $headers = array_map(function ($value) {
                if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1);
                    $value = substr($value, 0, -1);
                }
                return $value;
            }, ($optionValues['-H'] ?? $optionValues['--header']));

            $request->addHeaders($headers);
            if (isset($optionValues['-H'])) {
                unset($optionValues['-H']);
            } else {
                unset($optionValues['--header']);
            }
        }

        foreach ($optionValues as $option => $value) {
            foreach (self::$phpOptionsMap as $phpOption => $curlOption) {
                if ((str_starts_with($option, '--') && str_contains($curlOption, $option)) || str_starts_with($curlOption, $option)) {
                    $optionValue = ($value !== null) ? $value : true;
                    $curl->setOption(constant($phpOption), $optionValue);
                    break;
                }
            }
        }
    }

}
