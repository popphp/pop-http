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
 * HTTP client curl options class
 *
 * @category   Pop
 * @package    Pop\Http
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2024 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.popphp.org/license     New BSD License
 * @version    5.0.0
 */
class Options
{

    /**
     * Curl CLI-to-PHP options
     * @var array
     */
    protected static $cliOptions = [
        '--abstract-unix-socket'      => 'CURLOPT_ABSTRACT_UNIX_SOCKET',                                   // --abstract-unix-socket <path>
        '--alt-svc'                   => 'CURLOPT_ALTSVC',                                                 // --alt-svc <filename>
        '-a'                          => 'CURLOPT_APPEND',
        '--aws-sigv4'                 => 'CURLOPT_AWS_SIGV4',                                              // --aws-sigv4 <provider1[:provider2[:region[:service]]]>
        '--cacert'                    => 'CURLOPT_CAINFO',                                                 // --cacert <file>
        '--capath'                    => 'CURLOPT_CAPATH',                                                 // --capath <dir>
        '--connect-timeout'           => ['CURLOPT_TIMEOUT', 'CURLOPT_CONNECTTIMEOUT', 'CURLOPT_TIMEOUT'], // --connect-timeout <fractional seconds> (MS needs to be converted to seconds)
        '--connect-to'                => 'CURLOPT_CONNECT_TO',                                             // --connect-to <HOST1:PORT1:HOST2:PORT2>
        '-b'                          => 'CURLOPT_COOKIE',                                                 // -b, --cookie <data|filename>
        '--cookie'                    => 'CURLOPT_COOKIE',                                                 // -b, --cookie <data|filename>
        '-c'                          => 'CURLOPT_COOKIEJAR',                                              // -c, --cookie-jar <filename>
        '--cookie-jar'                => 'CURLOPT_COOKIEJAR',                                              // -c, --cookie-jar <filename>
        '--crlf'                      => 'CURLOPT_CRLF',
        '--crlfile'                   => 'CURLOPT_CRLFILE',                                                // --crlfile <file>
        '-X'                          => ['CURLOPT_POST', 'CURLOPT_PUT', 'CURLOPT_CUSTOMREQUEST'],         // -X, --request <method>
        '--request'                   => ['CURLOPT_POST', 'CURLOPT_PUT', 'CURLOPT_CUSTOMREQUEST'],         // -X, --request <method>
        '--disallow-username-in-url'  => 'CURLOPT_DISALLOW_USERNAME_IN_URL',
        '--dns-interface'             => 'CURLOPT_DNS_INTERFACE',                                          // --dns-interface <interface>
        '--dns-ipv4-addr'             => 'CURLOPT_DNS_LOCAL_IP4',                                          // --dns-ipv4-addr <address>
        '--dns-ipv6-addr'             => 'CURLOPT_DNS_LOCAL_IP6',                                          // --dns-ipv6-addr <address>
        '--dns-servers'               => 'CURLOPT_DNS_SERVERS',                                            // --dns-servers <addresses>
        '--doh-insecure'              => ['CURLOPT_DOH_SSL_VERIFYPEER', 'CURLOPT_DOH_SSL_VERIFYHOST'],     // Verify the DNS-over-HTTPS server's SSL certificate name fields against the host name and certificate. Available as of PHP 8.2.0 and cURL 7.76.0.
        '--no-doh-insecure'           => ['CURLOPT_DOH_SSL_VERIFYPEER', 'CURLOPT_DOH_SSL_VERIFYHOST'],     // Verify the DNS-over-HTTPS server's SSL certificate name fields against the host name and certificate. Available as of PHP 8.2.0 and cURL 7.76.0.
        '--doh-cert-status'           => 'CURLOPT_DOH_SSL_VERIFYSTATUS',                                   // Tell cURL to verify the status of the DNS-over-HTTPS server certificate using the "Certificate Status Request" TLS extension (OCSP stapling). Available as of PHP 8.2.0 and cURL 7.76.0.
        '--doh-url'                   => 'CURLOPT_DOH_URL',                                                // --doh-url <url> Provides the DNS-over-HTTPS URL. Available as of PHP 8.1.0 and cURL 7.62.0.
        '--expect100-timeout'         => 'CURLOPT_EXPECT_100_TIMEOUT_MS',                                  // --expect100-timeout <seconds>
        '-P'                          => 'CURLOPT_FTPPORT',                                                // -P, --ftp-port <address>
        '--ftp-port'                  => 'CURLOPT_FTPPORT',                                                // -P, --ftp-port <address>
        '--ftp-account'               => 'CURLOPT_FTP_ACCOUNT',                                            // --ftp-account <data>
        '--ftp-alternative-to-user'   => 'CURLOPT_FTP_ALTERNATIVE_TO_USER',                                // --ftp-alternative-to-user <command>
        '--ftp-create-dirs'           => 'CURLOPT_FTP_CREATE_MISSING_DIRS',
        '--ftp-method'                => 'CURLOPT_FTP_FILEMETHOD',                                         // --ftp-method <method>
        '--ftp-pasv'                  => 'CURLOPT_FTP_SKIP_PASV_IP',
        '--ftp-skip-pasv-ip'          => 'CURLOPT_FTP_SKIP_PASV_IP',
        '--ftp-ssl-ccc'               => 'CURLOPT_FTP_SSL_CCC',
        '--disable-eprt'              => 'CURLOPT_FTP_USE_EPRT',
        '--disable-epsv'              => 'CURLOPT_FTP_USE_EPSV',
        '--ftp-pret'                  => 'CURLOPT_FTP_USE_PRET',
        '--delegation'                => 'CURLOPT_GSSAPI_DELEGATION',                                      // --delegation <level>
        '--happy-eyeballs-timeout-ms' => 'CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS',                              // --happy-eyeballs-timeout-ms <milliseconds>
        '--haproxy-protocol'          => 'CURLOPT_HAPROXYPROTOCOL',
        '-i'                          => 'CURLOPT_HEADER',
        '--include'                   => 'CURLOPT_HEADER',
        '--hsts'                      => 'CURLOPT_HSTS',                                                   // --hsts <filename>
        '--http0.9'                   => 'CURLOPT_HTTP09_ALLOWED',
        '-H'                          => 'CURLOPT_HTTPHEADER',                                             // -H, --header <header/@file>
        '--header'                    => 'CURLOPT_HTTPHEADER',                                             // -H, --header <header/@file>
        '-p'                          => 'CURLOPT_HTTPPROXYTUNNEL',
        '--proxytunnel'               => 'CURLOPT_HTTPPROXYTUNNEL',
        '-0'                          => 'CURLOPT_HTTP_VERSION',
        '--http1.0'                   => 'CURLOPT_HTTP_VERSION',
        '--http1.1'                   => 'CURLOPT_HTTP_VERSION',
        '--http2'                     => 'CURLOPT_HTTP_VERSION',
        '--ignore-content-length'     => 'CURLOPT_IGNORE_CONTENT_LENGTH',
        '-T'                          => 'CURLOPT_INFILE',                                                 // -T, --upload-file <file> (Used with PUT)
        '--upload-file'               => 'CURLOPT_INFILE',                                                 // -T, --upload-file <file> (Used with PUT)
        '--interface'                 => 'CURLOPT_INTERFACE',                                              // --interface <name>
        '--krb'                       => 'CURLOPT_KRBLEVEL',                                               // --krb <level>
        '--local-port'                => ['CURLOPT_LOCALPORT', 'CURLOPT_LOCALPORTRANGE'],                  // --local-port <num/range>
        '--login-options'             => 'CURLOPT_LOGIN_OPTIONS',                                          // --login-options <options>
        '-Y'                          => 'CURLOPT_LOW_SPEED_LIMIT',                                        // -Y, --speed-limit <speed>
        '--speed-limit'               => 'CURLOPT_LOW_SPEED_LIMIT',                                        // -Y, --speed-limit <speed>
        '-y'                          => 'CURLOPT_LOW_SPEED_TIME',                                         // -y, --speed-time <seconds>
        '--speed-time'                => 'CURLOPT_LOW_SPEED_TIME',                                         // -y, --speed-time <seconds>
        '--mail-auth'                 => 'CURLOPT_MAIL_AUTH',                                              // --mail-auth <address>
        '--mail-from'                 => 'CURLOPT_MAIL_FROM',                                              // --mail-from <address>
        '--mail-rcpt'                 => 'CURLOPT_MAIL_RCPT',                                              // --mail-rcpt <address>
        '--mail-rcpt-allowfails'      => 'CURLOPT_MAIL_RCPT_ALLLOWFAILS',
        '--max-filesize'              => 'CURLOPT_MAXFILESIZE',                                            // --max-filesize <bytes>
        '-m'                          => 'CURLOPT_MAXLIFETIME_CONN',                                       // -m, --max-time <fractional seconds>
        '--max-time'                  => 'CURLOPT_MAXLIFETIME_CONN',                                       // -m, --max-time <fractional seconds>
        '--max-redirs'                => 'CURLOPT_MAXREDIRS',                                              // --max-redirs <num>
        '-n'                          => 'CURLOPT_NETRC',
        '--netrc'                     => 'CURLOPT_NETRC',
        '--netrc-file'                => 'CURLOPT_NETRC_FILE',                                             // --netrc-file <filename>
        '--no-progress-meter'         => 'CURLOPT_NOPROGRESS',
        '--noproxy'                   => 'CURLOPT_NOPROXY',                                                // --noproxy <no-proxy-list>
        '-u'                          => ['CURLOPT_USERPWD', 'CURLOPT_USERNAME', 'CURLOPT_PASSWORD'],      // -u, --user <user:password>
        '--user'                      => ['CURLOPT_USERPWD', 'CURLOPT_USERNAME', 'CURLOPT_PASSWORD'],      // -u, --user <user:password>
        '-d'                          => 'CURLOPT_POSTFIELDS',                                             // -d, --data <data>
        '-data'                       => 'CURLOPT_POSTFIELDS',                                             // -d, --data <data>
        '-x'                          => 'CURLOPT_PROXY',                                                  // -x, --proxy [protocol://]host[:port]
        '--proxy'                     => 'CURLOPT_PROXY',                                                  // -x, --proxy [protocol://]host[:port]
        '--proxy-basic'               => 'CURLOPT_PROXYAUTH',
        '--proxy-digest'              => 'CURLOPT_PROXYAUTH',
        '--proxy-header'              => 'CURLOPT_PROXYHEADER',                                            // --proxy-header <header/@file>
        '-U'                          => 'CURLOPT_PROXYUSERPWD',                                           // -U, --proxy-user <user:password>
        '--proxy-user'                => 'CURLOPT_PROXYUSERPWD',                                           // -U, --proxy-user <user:password>
        '--proxy-cacert'              => 'CURLOPT_PROXY_CAINFO',                                           // --proxy-cacert <file>
        '--proxy-capath'              => 'CURLOPT_PROXY_CAPATH',                                           // --proxy-capath <dir>
        '--proxy-crlfile'             => 'CURLOPT_PROXY_CRLFILE',                                          // --proxy-crlfile <file>
        '--proxy-pass'                => 'CURLOPT_PROXY_KEYPASSWD',                                        // --proxy-pass <phrase>
        '--proxy-pinnedpubkey'        => 'CURLOPT_PROXY_PINNEDPUBLICKEY',                                  // --proxy-pinnedpubkey <hashes>
        '--proxy-service-name'        => 'CURLOPT_PROXY_SERVICE_NAME',                                     // --proxy-service-name <name>
        '--proxy-cert'                => 'CURLOPT_PROXY_SSLCERT',                                          // --proxy-cert <cert[:passwd]>
        '--proxy-cert-type'           => 'CURLOPT_PROXY_SSLCERTTYPE',                                      // --proxy-cert-type <type>
        '--proxy-key'                 => 'CURLOPT_PROXY_SSLKEY',                                           // --proxy-key <key>
        '--proxy-key-type'            => 'CURLOPT_PROXY_SSLKEYTYPE',                                       // --proxy-key-type <type>
        '--proxy-ciphers'             => 'CURLOPT_PROXY_SSL_CIPHER_LIST',                                  // --proxy-ciphers <list>
        '--proxy-insecure'            => ['CURLOPT_PROXY_SSL_VERIFYHOST', 'CURLOPT_PROXY_SSL_VERIFYPEER'],
        '--proxy-tls13-ciphers'       => 'CURLOPT_PROXY_TLS13_CIPHERS',                                    // --proxy-tls13-ciphers <ciphersuite list>
        '--proxy-tlspassword'         => 'CURLOPT_PROXY_TLSAUTH_PASSWORD',                                 // --proxy-tlspassword <string>
        '--proxy-tlsauthtype'         => 'CURLOPT_PROXY_TLSAUTH_TYPE',                                     // --proxy-tlsauthtype <type>
        '--proxy-tlsuser'             => 'CURLOPT_PROXY_TLSAUTH_USERNAME',                                 // --proxy-tlsuser <name>
        '-Q'                          => 'CURLOPT_QUOTE',                                                  // -Q, --quote <command>
        '--quote'                     => 'CURLOPT_QUOTE',                                                  // -Q, --quote <command>
        '--random-file'               => 'CURLOPT_RANDOM_FILE',                                            // --random-file <file>
        '-r'                          => 'CURLOPT_RANGE',                                                  // -r, --range <range>
        '--range'                     => 'CURLOPT_RANGE',                                                  // -r, --range <range>
        '-e'                          => 'CURLOPT_REFERER',                                                // -e, --referer <url>
        '--referer'                   => 'CURLOPT_REFERER',                                                // -e, --referer <url>
        '--resolve'                   => 'CURLOPT_RESOLVE',                                                // --resolve <[+]host:port:addr[,addr]...>
        '--sasl-authzid'              => 'CURLOPT_SASL_AUTHZID',                                           // --sasl-authzid <identity>
        '--sasl-ir'                   => 'CURLOPT_SASL_IR',
        '--service-name'              => 'CURLOPT_SERVICE_NAME',                                           // --service-name <name>
        '--socks5-basic'              => 'CURLOPT_SOCKS5_AUTH',
        '--socks5-gssapi-nec'         => 'CURLOPT_SOCKS5_GSSAPI_NEC',
        '--socks5-gssapi-service'     => 'CURLOPT_SOCKS5_GSSAPI_SERVICE',                                  // --socks5-gssapi-service <name>
        '--compressed-ssh'            => 'CURLOPT_SSH_COMPRESSION',
        '--hostpubmd5'                => 'CURLOPT_SSH_HOST_PUBLIC_KEY_MD5',                                // --hostpubmd5 <md5>
        '--hostpubsha256'             => 'CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256',                             // --hostpubsha256 <sha256>
        '--pubkey'                    => 'CURLOPT_SSH_PUBLIC_KEYFILE',                                     // --pubkey <key>
        '-E'                          => 'CURLOPT_SSLCERT',                                                // -E, --cert <certificate[:password]>
        '--cert'                      => 'CURLOPT_SSLCERT',                                                // -E, --cert <certificate[:password]>
        '--cert-type'                 => 'CURLOPT_SSLCERTTYPE',                                            // --cert-type <type>
        '--engine'                    => 'CURLOPT_SSLENGINE',                                              // --engine <name>
        '--key'                       => 'CURLOPT_SSLKEY',                                                 // --key <key>
        '--pass'                      => 'CURLOPT_SSLKEYPASSWD',                                           // --pass <phrase>
        '--key-type'                  => 'CURLOPT_SSLKEYTYPE',                                             // --key-type <type>
        '-2'                          => 'CURLOPT_SSLVERSION',
        '--sslv2'                     => 'CURLOPT_SSLVERSION',
        '-3'                          => 'CURLOPT_SSLVERSION',
        '--sslv3'                     => 'CURLOPT_SSLVERSION',
        '--ciphers'                   => 'CURLOPT_SSL_CIPHER_LIST',                                        // --ciphers <list of ciphers> i.e. ECDHE-ECDSA-AES256-CCM8
        '--curves'                    => 'CURLOPT_SSL_EC_CURVES',                                          // --curves <algorithm list>
        '--alpn'                      => 'CURLOPT_SSL_ENABLE_ALPN',
        '--no-alpn'                   => 'CURLOPT_SSL_ENABLE_ALPN',
        '--npn'                       => 'CURLOPT_SSL_ENABLE_NPN',
        '--no-npn'                    => 'CURLOPT_SSL_ENABLE_NPN',
        '--false-start'               => 'CURLOPT_SSL_FALSESTART',
        '--no-sessionid'              => 'CURLOPT_SSL_SESSIONID_CACHE',
        '-k'                          => ['CURLOPT_SSL_VERIFYHOST', 'CURLOPT_SSL_VERIFYPEER'],
        '--insecure'                  => ['CURLOPT_SSL_VERIFYHOST', 'CURLOPT_SSL_VERIFYPEER'],
        '--cert-status'               => 'CURLOPT_SSL_VERIFYSTATUS',
        '--stderr'                    => 'CURLOPT_STDERR',                                                 // --stderr <file>
        '--suppress-connect-headers'  => 'CURLOPT_SUPPRESS_CONNECT_HEADERS',
        '--tcp-fastopen'              => 'CURLOPT_TCP_FASTOPEN',
        '--keepalive-time'            => 'CURLOPT_TCP_KEEPALIVE',                                          // --keepalive-time <seconds>
        '--tcp-nodelay'               => 'CURLOPT_TCP_NODELAY',
        '-t'                          => 'CURLOPT_TELNETOPTIONS',                                          // -t, --telnet-option <opt=val>
        '--telnet-option'             => 'CURLOPT_TELNETOPTIONS',                                          // -t, --telnet-option <opt=val>
        '--tftp-blksize'              => 'CURLOPT_TFTP_BLKSIZE',                                           // --tftp-blksize <value>
        '--tftp-no-options'           => 'CURLOPT_TFTP_NO_OPTIONS',
        '-z --time-cond'              => 'CURLOPT_TIMECONDITION',                                          // -z, --time-cond <time>
        '--tls13-ciphers'             => 'CURLOPT_TLS13_CIPHERS',                                          // --tls13-ciphers <ciphersuite list>
        '--tlspassword'               => 'CURLOPT_TLSAUTH_PASSWORD',                                       // --tlspassword <string>
        '--tlsauthtype'               => 'CURLOPT_TLSAUTH_TYPE',                                           // --tlsauthtype <type>
        '--tlsuser'                   => 'CURLOPT_TLSAUTH_USERNAME',                                       // --tlsuser <name>
        '--tr-encoding'               => 'CURLOPT_TRANSFER_ENCODING',
        '--no-tr-encoding'            => 'CURLOPT_TRANSFER_ENCODING',
        '--unix-socket'               => 'CURLOPT_UNIX_SOCKET_PATH',                                       // --unix-socket <path>
        '--url'                       => 'CURLOPT_URL',                                                    // --url <url> (Used for config files, unnecessary on the CLI)
        '-A'                          => 'CURLOPT_USERAGENT',                                              // -A, --user-agent <name>
        '--user-agent'                => 'CURLOPT_USERAGENT',                                              // -A, --user-agent <name>
        '--ssl'                       => 'CURLOPT_USE_SSL',                                                // Attempts to force server to use secure connection',
        '--ssl-reqd'                  => 'CURLOPT_USE_SSL',                                                // Attempts to force server to use secure connection',
        '-v'                          => 'CURLOPT_VERBOSE',
        '--verbose'                   => 'CURLOPT_VERBOSE',
        '--oauth2-bearer'             => 'CURLOPT_XOAUTH2_BEARER',                                         // --oauth2-bearer <token>
        '--ssl-allow-beast'           => 'CURLSSLOPT_ALLOW_BEAST',
        '--ssl-auto-client-cert'      => 'CURLSSLOPT_AUTO_CLIENT_CERT',
        '--ssl-no-revoke'             => 'CURLSSLOPT_NO_REVOKE',
        '--ssl-revoke-best-effort'    => 'CURLSSLOPT_REVOKE_BEST_EFFORT',
    ];

    /**
     *  Curl PHP-to-CLI options
     * @var array
     */
    protected static $phpOptions = [
        'CURLOPT_ABSTRACT_UNIX_SOCKET'       => '--abstract-unix-socket',                               // --abstract-unix-socket <path>
        'CURLOPT_ALTSVC'                     => '--alt-svc',                                            // --alt-svc <filename>
        'CURLOPT_APPEND'                     => '-a',
        'CURLOPT_AWS_SIGV4'                  => '--aws-sigv4',                                          // --aws-sigv4 <provider1[:provider2[:region[:service]]]>
        'CURLOPT_CAINFO'                     => '--cacert',                                             // --cacert <file>
        'CURLOPT_CAPATH'                     => '--capath',                                             // --capath <dir>
        'CURLOPT_CONNECTTIMEOUT'             => '--connect-timeout',                                    // --connect-timeout <fractional seconds>
        'CURLOPT_CONNECT_TO'                 => '--connect-to',                                         // --connect-to <HOST1:PORT1:HOST2:PORT2>
        'CURLOPT_COOKIE'                     => ['-b', '--cookie'],                                     // -b, --cookie <data|filename>
        'CURLOPT_COOKIEJAR'                  => ['-c', '--cookie-jar'],                                 // -c, --cookie-jar <filename>
        'CURLOPT_CRLF'                       => '--crlf',
        'CURLOPT_CRLFILE'                    => '--crlfile',                                            // --crlfile <file>
        'CURLOPT_CUSTOMREQUEST'              => ['-X', '--request'],                                    // -X, --request <method>
        'CURLOPT_DISALLOW_USERNAME_IN_URL'   => '--disallow-username-in-url',
        'CURLOPT_DNS_INTERFACE'              => '--dns-interface',                                      // --dns-interface <interface>
        'CURLOPT_DNS_LOCAL_IP4'              => '--dns-ipv4-addr',                                      // --dns-ipv4-addr <address>
        'CURLOPT_DNS_LOCAL_IP6'              => '--dns-ipv6-addr',                                      // --dns-ipv6-addr <address>
        'CURLOPT_DNS_SERVERS'                => '--dns-servers',                                        // --dns-servers <addresses>
        'CURLOPT_DOH_SSL_VERIFYHOST'         => ['--doh-insecure', '--no-doh-insecure'],                // Verify the DNS-over-HTTPS server's SSL certificate name fields against the host name. Available as of PHP 8.2.0 and cURL 7.76.0.
        'CURLOPT_DOH_SSL_VERIFYPEER'         => ['--doh-insecure', '--no-doh-insecure'],                // Verify the authenticity of the DNS-over-HTTPS server's SSL certificate. Available as of PHP 8.2.0 and cURL 7.76.0.
        'CURLOPT_DOH_SSL_VERIFYSTATUS'       => '--doh-cert-status',                                    // Tell cURL to verify the status of the DNS-over-HTTPS server certificate using the "Certificate Status Request" TLS extension (OCSP stapling). Available as of PHP 8.2.0 and cURL 7.76.0.
        'CURLOPT_DOH_URL'                    => '--doh-url',                                            // --doh-url <url> Provides the DNS-over-HTTPS URL. Available as of PHP 8.1.0 and cURL 7.62.0.
        'CURLOPT_EXPECT_100_TIMEOUT_MS'      => '--expect100-timeout',                                  // --expect100-timeout <seconds>
        'CURLOPT_FTPPORT'                    => ['-P', '--ftp-port'],                                   // -P, --ftp-port <address>
        'CURLOPT_FTP_ACCOUNT'                => '--ftp-account',                                        // --ftp-account <data>
        'CURLOPT_FTP_ALTERNATIVE_TO_USER'    => '--ftp-alternative-to-user',                            // --ftp-alternative-to-user <command>
        'CURLOPT_FTP_CREATE_MISSING_DIRS'    => '--ftp-create-dirs',
        'CURLOPT_FTP_FILEMETHOD'             => '--ftp-method',                                         // --ftp-method <method>
        'CURLOPT_FTP_SKIP_PASV_IP'           => ['--ftp-skip-pasv-ip', '--ftp-pasv'],
        'CURLOPT_FTP_SSL_CCC'                => '--ftp-ssl-ccc',
        'CURLOPT_FTP_USE_EPRT'               => '--disable-eprt',
        'CURLOPT_FTP_USE_EPSV'               => '--disable-epsv',
        'CURLOPT_FTP_USE_PRET'               => '--ftp-pret',
        'CURLOPT_GSSAPI_DELEGATION'          => '--delegation',                                         // --delegation <level>
        'CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS'  => '--happy-eyeballs-timeout-ms',                          // --happy-eyeballs-timeout-ms <milliseconds>
        'CURLOPT_HAPROXYPROTOCOL'            => '--haproxy-protocol',
        'CURLOPT_HEADER'                     => ['-i', '--include'],
        'CURLOPT_HSTS'                       => '--hsts',                                               // --hsts <filename>
        'CURLOPT_HTTP09_ALLOWED'             => '--http0.9',
        'CURLOPT_HTTPHEADER'                 => ['-H', '--header'],                                     // -H, --header <header/@file>
        'CURLOPT_HTTPPROXYTUNNEL'            => ['-p', '--proxytunnel'],
        'CURLOPT_HTTP_VERSION'               => ['-0', '--http1.0', '--http1.1', '--http2'],
        'CURLOPT_IGNORE_CONTENT_LENGTH'      => '--ignore-content-length',
        'CURLOPT_INFILE'                     => ['-T', '--upload-file'],                                // -T, --upload-file <file> (Used with PUT)
        'CURLOPT_INTERFACE'                  => '--interface',                                          // --interface <name>
        'CURLOPT_KRBLEVEL'                   => '--krb',                                                // --krb <level>
        'CURLOPT_LOCALPORT'                  => '--local-port',                                         // --local-port <num/range>
        'CURLOPT_LOCALPORTRANGE'             => '--local-port',                                         // --local-port <num/range>
        'CURLOPT_LOGIN_OPTIONS'              => '--login-options',                                      // --login-options <options>
        'CURLOPT_LOW_SPEED_LIMIT'            => ['-Y', '--speed-limit'],                                // -Y, --speed-limit <speed>
        'CURLOPT_LOW_SPEED_TIME'             => ['-y', '--speed-time'],                                 // -y, --speed-time <seconds>
        'CURLOPT_MAIL_AUTH'                  => '--mail-auth',                                          // --mail-auth <address>
        'CURLOPT_MAIL_FROM'                  => '--mail-from',                                          // --mail-from <address>
        'CURLOPT_MAIL_RCPT'                  => '--mail-rcpt',                                          // --mail-rcpt <address>
        'CURLOPT_MAIL_RCPT_ALLLOWFAILS'      => '--mail-rcpt-allowfails',
        'CURLOPT_MAXFILESIZE'                => '--max-filesize',                                       // --max-filesize <bytes>
        'CURLOPT_MAXLIFETIME_CONN'           => ['-m', '--max-time'],                                   // -m, --max-time <fractional seconds>
        'CURLOPT_MAXREDIRS'                  => '--max-redirs',                                         // --max-redirs <num>
        'CURLOPT_NETRC'                      => ['-n', '--netrc'],
        'CURLOPT_NETRC_FILE'                 => '--netrc-file',                                         // --netrc-file <filename>
        'CURLOPT_NOPROGRESS'                 => '--no-progress-meter',
        'CURLOPT_NOPROXY'                    => '--noproxy',                                            // --noproxy <no-proxy-list>
        'CURLOPT_PASSWORD'                   => ['-u', '--user'],                                       // -u, --user <user:password>
        'CURLOPT_POST'                       => ['-X', '--request'],                                    // -X, --request <method>
        'CURLOPT_POSTFIELDS'                 => ['-d', '-data'],                                        // -d, --data <data>
        'CURLOPT_PROXY'                      => ['-x', '--proxy'],                                      // -x, --proxy [protocol://]host[:port]
        'CURLOPT_PROXYAUTH'                  => ['--proxy-basic', '--proxy-digest'],
        'CURLOPT_PROXYHEADER'                => '--proxy-header',                                       // --proxy-header <header/@file>
        'CURLOPT_PROXYUSERPWD'               => ['-U', '--proxy-user'],                                 // -U, --proxy-user <user:password>
        'CURLOPT_PROXY_CAINFO'               => '--proxy-cacert',                                       // --proxy-cacert <file>
        'CURLOPT_PROXY_CAPATH'               => '--proxy-capath',                                       // --proxy-capath <dir>
        'CURLOPT_PROXY_CRLFILE'              => '--proxy-crlfile',                                      // --proxy-crlfile <file>
        'CURLOPT_PROXY_KEYPASSWD'            => '--proxy-pass',                                         // --proxy-pass <phrase>
        'CURLOPT_PROXY_PINNEDPUBLICKEY'      => '--proxy-pinnedpubkey',                                 // --proxy-pinnedpubkey <hashes>
        'CURLOPT_PROXY_SERVICE_NAME'         => '--proxy-service-name',                                 // --proxy-service-name <name>
        'CURLOPT_PROXY_SSLCERT'              => '--proxy-cert',                                         // --proxy-cert <cert[:passwd]>
        'CURLOPT_PROXY_SSLCERTTYPE'          => '--proxy-cert-type',                                    // --proxy-cert-type <type>
        'CURLOPT_PROXY_SSLKEY'               => '--proxy-key',                                          // --proxy-key <key>
        'CURLOPT_PROXY_SSLKEYTYPE'           => '--proxy-key-type',                                     // --proxy-key-type <type>
        'CURLOPT_PROXY_SSL_CIPHER_LIST'      => '--proxy-ciphers',                                      // --proxy-ciphers <list>
        'CURLOPT_PROXY_SSL_VERIFYHOST'       => '--proxy-insecure',
        'CURLOPT_PROXY_SSL_VERIFYPEER'       => '--proxy-insecure',
        'CURLOPT_PROXY_TLS13_CIPHERS'        => '--proxy-tls13-ciphers',                                // --proxy-tls13-ciphers <ciphersuite list>
        'CURLOPT_PROXY_TLSAUTH_PASSWORD'     => '--proxy-tlspassword',                                  // --proxy-tlspassword <string>
        'CURLOPT_PROXY_TLSAUTH_TYPE'         => '--proxy-tlsauthtype',                                  // --proxy-tlsauthtype <type>
        'CURLOPT_PROXY_TLSAUTH_USERNAME'     => '--proxy-tlsuser',                                      // --proxy-tlsuser <name>
        'CURLOPT_PUT'                        => ['-X', '--request'],                                    // -X, --request <method>
        'CURLOPT_QUOTE'                      => ['-Q', '--quote'],                                      // -Q, --quote <command>
        'CURLOPT_RANDOM_FILE'                => '--random-file',                                        // --random-file <file>
        'CURLOPT_RANGE'                      => '-r --range',                                           // -r, --range <range>
        'CURLOPT_REFERER'                    => '-e --referer',                                         // -e, --referer <url>
        'CURLOPT_RESOLVE'                    => '--resolve',                                            // --resolve <[+]host:port:addr[,addr]...>
        'CURLOPT_SASL_AUTHZID'               => '--sasl-authzid',                                       // --sasl-authzid <identity>
        'CURLOPT_SASL_IR'                    => '--sasl-ir',
        'CURLOPT_SERVICE_NAME'               => '--service-name',                                       // --service-name <name>
        'CURLOPT_SOCKS5_AUTH'                => '--socks5-basic',
        'CURLOPT_SOCKS5_GSSAPI_NEC'          => '--socks5-gssapi-nec',
        'CURLOPT_SOCKS5_GSSAPI_SERVICE'      => '--socks5-gssapi-service',                              // --socks5-gssapi-service <name>
        'CURLOPT_SSH_COMPRESSION'            => '--compressed-ssh',
        'CURLOPT_SSH_HOST_PUBLIC_KEY_MD5'    => '--hostpubmd5',                                         // --hostpubmd5 <md5>
        'CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256' => '--hostpubsha256',                                      // --hostpubsha256 <sha256>
        'CURLOPT_SSH_PUBLIC_KEYFILE'         => '--pubkey',                                             // --pubkey <key>
        'CURLOPT_SSLCERT'                    => ['-E', '--cert'],                                       // -E, --cert <certificate[:password]>
        'CURLOPT_SSLCERTTYPE'                => '--cert-type',                                          // --cert-type <type>
        'CURLOPT_SSLENGINE'                  => '--engine',                                             // --engine <name>
        'CURLOPT_SSLKEY'                     => '--key',                                                // --key <key>
        'CURLOPT_SSLKEYPASSWD'               => '--pass',                                               // --pass <phrase>
        'CURLOPT_SSLKEYTYPE'                 => '--key-type',                                           // --key-type <type>
        'CURLOPT_SSLVERSION'                 => ["-2", "--sslv2", "-3", "--sslv3"],
        'CURLOPT_SSL_CIPHER_LIST'            => '--ciphers',                                            // --ciphers <list of ciphers> i.e. ECDHE-ECDSA-AES256-CCM8
        'CURLOPT_SSL_EC_CURVES'              => '--curves',                                             // --curves <algorithm list>
        'CURLOPT_SSL_ENABLE_ALPN'            => ['--alpn', '--no-alpn'],
        'CURLOPT_SSL_ENABLE_NPN'             => ['--npn', '--no-npn'],
        'CURLOPT_SSL_FALSESTART'             => '--false-start',
        'CURLOPT_SSL_SESSIONID_CACHE'        => '--no-sessionid',
        'CURLOPT_SSL_VERIFYHOST'             => ['-k', '--insecure'],
        'CURLOPT_SSL_VERIFYPEER'             => ['-k', '--insecure'],
        'CURLOPT_SSL_VERIFYSTATUS'           => '--cert-status',
        'CURLOPT_STDERR'                     => '--stderr',                                             // --stderr <file>
        'CURLOPT_SUPPRESS_CONNECT_HEADERS'   => '--suppress-connect-headers',
        'CURLOPT_TCP_FASTOPEN'               => '--tcp-fastopen',
        'CURLOPT_TCP_KEEPALIVE'              => '--keepalive-time',                                     // --keepalive-time <seconds>
        'CURLOPT_TCP_NODELAY'                => '--tcp-nodelay',
        'CURLOPT_TELNETOPTIONS'              => ['-t', '--telnet-option'],                              // -t, --telnet-option <opt=val>
        'CURLOPT_TFTP_BLKSIZE'               => '--tftp-blksize',                                       // --tftp-blksize <value>
        'CURLOPT_TFTP_NO_OPTIONS'            => '--tftp-no-options',
        'CURLOPT_TIMECONDITION'              => '-z --time-cond',                                       // -z, --time-cond <time>
        'CURLOPT_TIMEOUT'                    => '--connect-timeout',                                    // --connect-timeout <fractional seconds>
        'CURLOPT_TIMEOUT_MS'                 => '--connect-timeout',                                    // --connect-timeout <fractional seconds> (MS needs to be converted to seconds),
        'CURLOPT_TLS13_CIPHERS'              => '--tls13-ciphers',                                      // --tls13-ciphers <ciphersuite list>
        'CURLOPT_TLSAUTH_PASSWORD'           => '--tlspassword',                                        // --tlspassword <string>
        'CURLOPT_TLSAUTH_TYPE'               => '--tlsauthtype',                                        // --tlsauthtype <type>
        'CURLOPT_TLSAUTH_USERNAME'           => '--tlsuser',                                            // --tlsuser <name>
        'CURLOPT_TRANSFER_ENCODING'          => ['--tr-encoding', '--no-tr-encoding'],
        'CURLOPT_UNIX_SOCKET_PATH'           => '--unix-socket',                                        // --unix-socket <path>
        'CURLOPT_URL'                        => '--url',                                                // --url <url> (Used for config files, unnecessary on the CLI)
        'CURLOPT_USERAGENT'                  => ['-A', '--user-agent'],                                 // -A, --user-agent <name>
        'CURLOPT_USERNAME'                   => ['-u', '--user'],                                       // -u, --user <user:password>
        'CURLOPT_USERPWD'                    => ['-u', '--user'],                                       // -u, --user <user:password>
        'CURLOPT_USE_SSL'                    => ['--ssl', '--ssl-reqd'],                                // Attempts to force server to use secure connection',
        'CURLOPT_VERBOSE'                    => ['-v', '--verbose'],
        'CURLOPT_XOAUTH2_BEARER'             => '--oauth2-bearer',                                      // --oauth2-bearer <token>
        'CURLSSLOPT_ALLOW_BEAST'             => '--ssl-allow-beast',
        'CURLSSLOPT_AUTO_CLIENT_CERT'        => '--ssl-auto-client-cert',
        'CURLSSLOPT_NO_REVOKE'               => '--ssl-no-revoke',
        'CURLSSLOPT_REVOKE_BEST_EFFORT'      => '--ssl-revoke-best-effort',
    ];

    /**
     * Curl special options
     * @var array
     */
    protected static $specialOptions = [
        '--basic',          //                             Use basic auth
        '--digest',         //                             Use digest auth
        '--data-ascii',     // --data-ascii <data>         This is just an alias for -d, --data.
        '--data-binary',    // --data-binary <data>        Transmit binary data with no processing
        '--data-raw',       // --data-raw <data>           This posts data similarly to -d, --data but without the special interpretation of the @ character.
        '--data-urlencode', // --data-urlencode <data>     This posts data, similar to the other -d, --data options with the exception that this performs URL-encoding.
        '--form-escape',    //                             Tells curl to pass on names of multipart form fields and files using backslash-escaping instead of percent-encoding.
        '--form-string',    // --form-string <name=string> Similar to -F, --form except that the value string for the named parameter is used literally.
        '-F',               // -F, --form <name=content>   Fets curl emulate a filled-in form in which a user has pressed the submit button.
        '--form',           // -F, --form <name=content>   Fets curl emulate a filled-in form in which a user has pressed the submit button.
        '-G',               // -G, --get                   Force GET request
        '--get',            // -G, --get                   Force GET request
        '-I',               // -I, --head                  Force HEAD request
        '--head',           // -I, --head                  Force HEAD request
        '--json',           // --json <data>               Shortcut for --data <data> --header "Content-Type: application/json" --header "Accept: application/json"
        '-L',               // -L, --location              Make curl redo the same request on the redirected location
        '--location',       // -L, --location              Make curl redo the same request on the redirected location
        '--raw',            //                             Disable all internal HTTP decoding
    ];
    /**
     * Curl options that require a value
     * @var array
     */
    protected static $valueOptions = [
        '--abstract-unix-socket'             => null, // --abstract-unix-socket <path>
        '--alt-svc'                          => null, // --alt-svc <filename>
        '--aws-sigv4'                        => null, // --aws-sigv4 <provider1[:provider2[:region[:service]]]>[
        '--cacert'                           => null, // --cacert <file>
        '--capath'                           => null, // --capath <dir>
        '--connect-timeout'                  => null, // --connect-timeout <fractional seconds> (MS needs to be converted to seconds)
        '--connect-to'                       => null, // --connect-to <HOST1:PORT1:HOST2:PORT2>
        '-b'                                 => null, // -b, --cookie <data|filename>
        '--cookie'                           => null, // -b, --cookie <data|filename>
        '-c'                                 => null, // -c, --cookie-jar <filename>
        '--cookie-jar'                       => null, // -c, --cookie-jar <filename>
        '--crlfile'                          => null, // --crlfile <file>
        '-X'                                 => null, // -X, --request <method>
        '--request'                          => null, // -X, --request <method>
        '--dns-interface'                    => null, // --dns-interface <interface>
        '--dns-ipv4-addr'                    => null, // --dns-ipv4-addr <address>
        '--dns-ipv6-addr'                    => null, // --dns-ipv6-addr <address>
        '--dns-servers'                      => null, // --dns-servers <addresses>
        '--doh-insecure'                     => null, // Verify the DNS-over-HTTPS server's SSL certificate name fields against the host name and certificate. Available as of PHP 8.2.0 and cURL 7.76.0.
        '--no-doh-insecure'                  => null, // Verify the DNS-over-HTTPS server's SSL certificate name fields against the host name and certificate. Available as of PHP 8.2.0 and cURL 7.76.0.
        '--doh-cert-status'                  => null, // Tell cURL to verify the status of the DNS-over-HTTPS server certificate using the "Certificate Status Request" TLS extension (OCSP stapling). Available as of PHP 8.2.0 and cURL 7.76.0.
        '--doh-url'                          => null, // --doh-url <url> Provides the DNS-over-HTTPS URL. Available as of PHP 8.1.0 and cURL 7.62.0.
        '--expect100-timeout'                => null, // --expect100-timeout <seconds>
        '-P'                                 => null, // -P, --ftp-port <address>
        '--ftp-port'                         => null, // -P, --ftp-port <address>
        '--ftp-account'                      => null, // --ftp-account <data>
        '--ftp-alternative-to-user'          => null, // --ftp-alternative-to-user <command>
        '--ftp-method'                       => null, // --ftp-method <method>
        '--delegation'                       => null, // --delegation <level>
        '--happy-eyeballs-timeout-ms'        => null, // --happy-eyeballs-timeout-ms <milliseconds>
        '--hsts'                             => null, // --hsts <filename>
        '-H'                                 => null, // -H, --header <header/@file>
        '--header'                           => null, // -H, --header <header/@file>
        '-0'                                 => CURL_HTTP_VERSION_1_0, // CURL HTTP Version constant
        '--http1.0'                          => CURL_HTTP_VERSION_1_0, // CURL HTTP Version constant
        '--http1.1'                          => CURL_HTTP_VERSION_1_1, // CURL HTTP Version constant
        '--http2'                            => CURL_HTTP_VERSION_2_0, // CURL HTTP Version constant
        '-T'                                 => null, // -T, --upload-file <file> (Used with PUT)
        '--upload-file'                      => null, // -T, --upload-file <file> (Used with PUT)
        '--interface'                        => null, // --interface <name>
        '--krb'                              => null, // --krb <level>
        '--local-port'                       => null, // --local-port <num/range>
        '--login-options'                    => null, // --login-options <options>
        '-Y'                                 => null, // -Y, --speed-limit <speed>
        '--speed-limit'                      => null, // -Y, --speed-limit <speed>
        '-y'                                 => null, // -y, --speed-time <seconds>
        '--speed-time'                       => null, // -y, --speed-time <seconds>
        '--mail-auth'                        => null, // --mail-auth <address>
        '--mail-from'                        => null, // --mail-from <address>
        '--mail-rcpt'                        => null, // --mail-rcpt <address>
        '--max-filesize'                     => null, // --max-filesize <bytes>
        '-m'                                 => null, // -m, --max-time <fractional seconds>
        '--max-time'                         => null, // -m, --max-time <fractional seconds>
        '--max-redirs'                       => null, // --max-redirs <num>
        '--netrc-file'                       => null, // --netrc-file <filename>
        '--noproxy'                          => null, // --noproxy <no-proxy-list>
        '-u'                                 => null, // -u, --user <user:password>
        '--user'                             => null, // -u, --user <user:password>
        '-d'                                 => null, // -d, --data <data>
        '-data'                              => null, // -d, --data <data>
        '-x'                                 => null, // -x, --proxy [protocol://]host[:port]
        '--proxy'                            => null, // -x, --proxy [protocol://]host[:port]
        '--proxy-basic'                      => CURLAUTH_BASIC,  // CURL Auth constant
        '--proxy-digest'                     => CURLAUTH_DIGEST, // CURL Auth constant
        '--proxy-header'                     => null, // --proxy-header <header/@file>
        '-U'                                 => null, // -U, --proxy-user <user:password>
        '--proxy-user'                       => null, // -U, --proxy-user <user:password>
        '--proxy-cacert'                     => null, // --proxy-cacert <file>
        '--proxy-capath'                     => null, // --proxy-capath <dir>
        '--proxy-crlfile'                    => null, // --proxy-crlfile <file>
        '--proxy-pass'                       => null, // --proxy-pass <phrase>
        '--proxy-pinnedpubkey'               => null, // --proxy-pinnedpubkey <hashes>
        '--proxy-service-name'               => null, // --proxy-service-name <name>
        '--proxy-cert'                       => null, // --proxy-cert <cert[:passwd]>
        '--proxy-cert-type'                  => null, // --proxy-cert-type <type>
        '--proxy-key'                        => null, // --proxy-key <key>
        '--proxy-key-type'                   => null, // --proxy-key-type <type>
        '--proxy-ciphers'                    => null, // --proxy-ciphers <list>
        '--proxy-tls13-ciphers'              => null, // --proxy-tls13-ciphers <ciphersuite list>
        '--proxy-tlspassword'                => null, // --proxy-tlspassword <string>
        '--proxy-tlsauthtype'                => null, // --proxy-tlsauthtype <type>
        '--proxy-tlsuser'                    => null, // --proxy-tlsuser <name>
        '-Q'                                 => null, // -Q, --quote <command>
        '--quote'                            => null, // -Q, --quote <command>
        '--random-file'                      => null, // --random-file <file>
        '-r'                                 => null, // -r, --range <range>
        '--range'                            => null, // -r, --range <range>
        '-e'                                 => null, // -e, --referer <url>
        '--referer'                          => null, // -e, --referer <url>
        '--resolve'                          => null, // --resolve <[+]host:port:addr[,addr]...>
        '--sasl-authzid'                     => null, // --sasl-authzid <identity>
        '--service-name'                     => null, // --service-name <name>
        '--socks5-gssapi-service'            => null, // --socks5-gssapi-service <name>
        '--hostpubmd5'                       => null, // --hostpubmd5 <md5>
        '--hostpubsha256'                    => null, // --hostpubsha256 <sha256>
        '--pubkey'                           => null, // --pubkey <key>
        '-E'                                 => null, // -E, --cert <certificate[:password]>
        '--cert'                             => null, // -E, --cert <certificate[:password]>
        '--cert-type'                        => null, // --cert-type <type>
        '--engine'                           => null, // --engine <name>
        '--key'                              => null, // --key <key>
        '--pass'                             => null, // --pass <phrase>
        '--key-type'                         => null, // --key-type <type>
        '-2'                                 => CURL_SSLVERSION_SSLv2, // CURL SSL Version contstant
        '--sslv2'                            => CURL_SSLVERSION_SSLv2, // CURL SSL Version contstant
        '-3'                                 => CURL_SSLVERSION_SSLv3, // CURL SSL Version contstant
        '--sslv3'                            => CURL_SSLVERSION_SSLv3, // CURL SSL Version contstant
        '--ciphers'                          => null, // --ciphers <list of ciphers> i.e. ECDHE-ECDSA-AES256-CCM8
        '--curves'                           => null, // --curves <algorithm list>
        '--stderr'                           => null, // --stderr <file>
        '--keepalive-time'                   => null, // --keepalive-time <seconds>
        '-t'                                 => null, // -t, --telnet-option <opt=val>
        '--telnet-option'                    => null, // -t, --telnet-option <opt=val>
        '--tftp-blksize'                     => null, // --tftp-blksize <value>
        '-z --time-cond'                     => null, // -z, --time-cond <time>
        '--tls13-ciphers'                    => null, // --tls13-ciphers <ciphersuite list>
        '--tlspassword'                      => null, // --tlspassword <string>
        '--tlsauthtype'                      => null, // --tlsauthtype <type>
        '--tlsuser'                          => null, // --tlsuser <name>
        '--unix-socket'                      => null, // --unix-socket <path>
        '--url'                              => null, // --url <url> (Used for config files, unnecessary on the CLI)
        '-A'                                 => null, // -A, --user-agent <name>
        '--user-agent'                       => null, // -A, --user-agent <name>
        '--oauth2-bearer'                    => null, // --oauth2-bearer <token>
        'CURLOPT_ABSTRACT_UNIX_SOCKET'       => null, // --abstract-unix-socket <path>
        'CURLOPT_ALTSVC'                     => null, // --alt-svc <filename>
        'CURLOPT_AWS_SIGV4'                  => null, // --aws-sigv4 <provider1[:provider2[:region[:service]]]>
        'CURLOPT_CAINFO'                     => null, // --cacert <file>
        'CURLOPT_CAPATH'                     => null, // --capath <dir>
        'CURLOPT_CONNECTTIMEOUT'             => null, // --connect-timeout <fractional seconds>
        'CURLOPT_CONNECT_TO'                 => null, // --connect-to <HOST1:PORT1:HOST2:PORT2>
        'CURLOPT_COOKIE'                     => null, // -b, --cookie <data|filename>
        'CURLOPT_COOKIEJAR'                  => null, // -c, --cookie-jar <filename>
        'CURLOPT_CRLFILE'                    => null, // --crlfile <file>
        'CURLOPT_CUSTOMREQUEST'              => null, // -X, --request <method>
        'CURLOPT_DNS_INTERFACE'              => null, // --dns-interface <interface>
        'CURLOPT_DNS_LOCAL_IP4'              => null, // --dns-ipv4-addr <address>
        'CURLOPT_DNS_LOCAL_IP6'              => null, // --dns-ipv6-addr <address>
        'CURLOPT_DNS_SERVERS'                => null, // --dns-servers <addresses>
        'CURLOPT_DOH_URL'                    => null, // --doh-url <url> Provides the DNS-over-HTTPS URL. Available as of PHP 8.1.0 and cURL 7.62.0.
        'CURLOPT_EXPECT_100_TIMEOUT_MS'      => null, // --expect100-timeout <seconds>
        'CURLOPT_FTPPORT'                    => null, // -P, --ftp-port <address>
        'CURLOPT_FTP_ACCOUNT'                => null, // --ftp-account <data>
        'CURLOPT_FTP_ALTERNATIVE_TO_USER'    => null, // --ftp-alternative-to-user <command>
        'CURLOPT_FTP_FILEMETHOD'             => null, // --ftp-method <method>
        'CURLOPT_GSSAPI_DELEGATION'          => null, // --delegation <level>
        'CURLOPT_HAPPY_EYEBALLS_TIMEOUT_MS'  => null, // --happy-eyeballs-timeout-ms <milliseconds>
        'CURLOPT_HSTS'                       => null, // --hsts <filename>
        'CURLOPT_HTTPHEADER'                 => null, // -H, --header <header/@file>
        'CURLOPT_HTTP_VERSION'               => [CURL_HTTP_VERSION_1_0, CURL_HTTP_VERSION_1_0, CURL_HTTP_VERSION_1_1, CURL_HTTP_VERSION_2_0], // CURL HTTP version constant
        'CURLOPT_INFILE'                     => null, // -T, --upload-file <file> (Used with PUT)
        'CURLOPT_INTERFACE'                  => null, // --interface <name>
        'CURLOPT_KRBLEVEL'                   => null, // --krb <level>
        'CURLOPT_LOCALPORT'                  => null, // --local-port <num/range>
        'CURLOPT_LOCALPORTRANGE'             => null, // --local-port <num/range>
        'CURLOPT_LOGIN_OPTIONS'              => null, // --login-options <options>
        'CURLOPT_LOW_SPEED_LIMIT'            => null, // -Y, --speed-limit <speed>
        'CURLOPT_LOW_SPEED_TIME'             => null, // -y, --speed-time <seconds>
        'CURLOPT_MAIL_AUTH'                  => null, // --mail-auth <address>
        'CURLOPT_MAIL_FROM'                  => null, // --mail-from <address>
        'CURLOPT_MAIL_RCPT'                  => null, // --mail-rcpt <address>
        'CURLOPT_MAXFILESIZE'                => null, // --max-filesize <bytes>
        'CURLOPT_MAXLIFETIME_CONN'           => null, // -m, --max-time <fractional seconds>
        'CURLOPT_MAXREDIRS'                  => null, // --max-redirs <num>
        'CURLOPT_NETRC_FILE'                 => null, // --netrc-file <filename>
        'CURLOPT_NOPROXY'                    => null, // --noproxy <no-proxy-list>
        'CURLOPT_PASSWORD'                   => null, // -u, --user <user:password>
        'CURLOPT_POST'                       => null, // -X, --request <method>
        'CURLOPT_POSTFIELDS'                 => null, // -d, --data <data>
        'CURLOPT_PROXY'                      => null, // -x, --proxy [protocol://]host[:port]
        'CURLOPT_PROXYAUTH'                  => [CURLAUTH_BASIC, CURLAUTH_DIGEST], // CURL Auth Constant
        'CURLOPT_PROXYHEADER'                => null, // --proxy-header <header/@file>
        'CURLOPT_PROXYUSERPWD'               => null, // -U, --proxy-user <user:password>
        'CURLOPT_PROXY_CAINFO'               => null, // --proxy-cacert <file>
        'CURLOPT_PROXY_CAPATH'               => null, // --proxy-capath <dir>
        'CURLOPT_PROXY_CRLFILE'              => null, // --proxy-crlfile <file>
        'CURLOPT_PROXY_KEYPASSWD'            => null, // --proxy-pass <phrase>
        'CURLOPT_PROXY_PINNEDPUBLICKEY'      => null, // --proxy-pinnedpubkey <hashes>
        'CURLOPT_PROXY_SERVICE_NAME'         => null, // --proxy-service-name <name>
        'CURLOPT_PROXY_SSLCERT'              => null, // --proxy-cert <cert[:passwd]>
        'CURLOPT_PROXY_SSLCERTTYPE'          => null, // --proxy-cert-type <type>
        'CURLOPT_PROXY_SSLKEY'               => null, // --proxy-key <key>
        'CURLOPT_PROXY_SSLKEYTYPE'           => null, // --proxy-key-type <type>
        'CURLOPT_PROXY_SSL_CIPHER_LIST'      => null, // --proxy-ciphers <list>
        'CURLOPT_PROXY_TLS13_CIPHERS'        => null, // --proxy-tls13-ciphers <ciphersuite list>
        'CURLOPT_PROXY_TLSAUTH_PASSWORD'     => null, // --proxy-tlspassword <string>
        'CURLOPT_PROXY_TLSAUTH_TYPE'         => null, // --proxy-tlsauthtype <type>
        'CURLOPT_PROXY_TLSAUTH_USERNAME'     => null, // --proxy-tlsuser <name>
        'CURLOPT_PUT'                        => null, // -X, --request <method>
        'CURLOPT_QUOTE'                      => null, // -Q, --quote <command>
        'CURLOPT_RANDOM_FILE'                => null, // --random-file <file>
        'CURLOPT_RANGE'                      => null, // -r, --range <range>
        'CURLOPT_REFERER'                    => null, // -e, --referer <url>
        'CURLOPT_RESOLVE'                    => null, // --resolve <[+]host:port:addr[,addr]...>
        'CURLOPT_SASL_AUTHZID'               => null, // --sasl-authzid <identity>
        'CURLOPT_SERVICE_NAME'               => null, // --service-name <name>
        'CURLOPT_SOCKS5_GSSAPI_SERVICE'      => null, // --socks5-gssapi-service <name>
        'CURLOPT_SSH_HOST_PUBLIC_KEY_MD5'    => null, // --hostpubmd5 <md5>
        'CURLOPT_SSH_HOST_PUBLIC_KEY_SHA256' => null, // --hostpubsha256 <sha256>
        'CURLOPT_SSH_PUBLIC_KEYFILE'         => null, // --pubkey <key>
        'CURLOPT_SSLCERT'                    => null, // -E, --cert <certificate[:password]>
        'CURLOPT_SSLCERTTYPE'                => null, // --cert-type <type>
        'CURLOPT_SSLENGINE'                  => null, // --engine <name>
        'CURLOPT_SSLKEY'                     => null, // --key <key>
        'CURLOPT_SSLKEYPASSWD'               => null, // --pass <phrase>
        'CURLOPT_SSLKEYTYPE'                 => null, // --key-type <type>
        'CURLOPT_SSLVERSION'                 => [CURL_SSLVERSION_SSLv2, CURL_SSLVERSION_SSLv2, CURL_SSLVERSION_SSLv3, CURL_SSLVERSION_SSLv3], // CURL SSL Version constant
        'CURLOPT_SSL_CIPHER_LIST'            => null, // --ciphers <list of ciphers> i.e. ECDHE-ECDSA-AES256-CCM8
        'CURLOPT_SSL_EC_CURVES'              => null, // --curves <algorithm list>
        'CURLOPT_STDERR'                     => null, // --stderr <file>
        'CURLOPT_TCP_KEEPALIVE'              => null, // --keepalive-time <seconds>
        'CURLOPT_TELNETOPTIONS'              => null, // -t, --telnet-option <opt=val>
        'CURLOPT_TFTP_BLKSIZE'               => null, // --tftp-blksize <value>
        'CURLOPT_TIMECONDITION'              => null, // -z, --time-cond <time>
        'CURLOPT_TIMEOUT'                    => null, // --connect-timeout <fractional seconds>
        'CURLOPT_TIMEOUT_MS'                 => null, // --connect-timeout <fractional seconds> (MS needs to be converted to seconds),
        'CURLOPT_TLS13_CIPHERS'              => null, // --tls13-ciphers <ciphersuite list>
        'CURLOPT_TLSAUTH_PASSWORD'           => null, // --tlspassword <string>
        'CURLOPT_TLSAUTH_TYPE'               => null, // --tlsauthtype <type>
        'CURLOPT_TLSAUTH_USERNAME'           => null, // --tlsuser <name>
        'CURLOPT_UNIX_SOCKET_PATH'           => null, // --unix-socket <path>
        'CURLOPT_URL'                        => null, // --url <url> (Used for config files, unnecessary on the CLI)
        'CURLOPT_USERAGENT'                  => null, // -A, --user-agent <name>
        'CURLOPT_USERNAME'                   => null, // -u, --user <user:password>
        'CURLOPT_USERPWD'                    => null, // -u, --user <user:password>
        'CURLOPT_XOAUTH2_BEARER'             => null, // --oauth2-bearer <token>
    ];

    /**
     * Unresolved Curl options
     * @var array
     */
    protected static $unresolvedOptions = [
        'CURLOPT_ACCEPTTIMEOUT_MS',
        'CURLOPT_ACCEPT_ENCODING',
        'CURLOPT_ADDRESS_SCOPE',
        'CURLOPT_ALTSVC_CTRL',
        'CURLOPT_AUTOREFERER',
        'CURLOPT_BINARYTRANSFER',
        'CURLOPT_BUFFERSIZE',
        'CURLOPT_CAINFO_BLOB',
        'CURLOPT_CERTINFO',
        'CURLOPT_CONNECTTIMEOUT_MS',
        'CURLOPT_CONNECT_ONLY',
        'CURLOPT_COOKIEFILE',
        'CURLOPT_COOKIELIST',
        'CURLOPT_COOKIESESSION',
        'CURLOPT_DEFAULT_PROTOCOL',
        'CURLOPT_DIRLISTONLY',
        'CURLOPT_DNS_CACHE_TIMEOUT',
        'CURLOPT_DNS_SHUFFLE_ADDRESSES',
        'CURLOPT_DNS_USE_GLOBAL_CACHE',
        'CURLOPT_EGDSOCKET',
        'CURLOPT_ENCODING',
        'CURLOPT_FAILONERROR',
        'CURLOPT_FILE',
        'CURLOPT_FILETIME',
        'CURLOPT_FNMATCH_FUNCTION',
        'CURLOPT_FOLLOWLOCATION', // This constant is not available when open_basedir is enabled.
        'CURLOPT_FORBID_REUSE',
        'CURLOPT_FRESH_CONNECT',
        'CURLOPT_FTPAPPEND',
        'CURLOPT_FTPLISTONLY',
        'CURLOPT_FTPSSLAUTH',
        'CURLOPT_FTP_RESPONSE_TIMEOUT',
        'CURLOPT_FTP_SSL',
        'CURLOPT_HEADERFUNCTION',
        'CURLOPT_HEADEROPT',
        'CURLOPT_HSTS_CTRL',
        'CURLOPT_HTTP200ALIASES',
        'CURLOPT_HTTPAUTH',
        'CURLOPT_HTTPGET',
        'CURLOPT_HTTP_CONTENT_DECODING',
        'CURLOPT_HTTP_TRANSFER_DECODING',
        'CURLOPT_INFILESIZE',
        'CURLOPT_IPRESOLVE',
        'CURLOPT_ISSUERCERT',
        'CURLOPT_ISSUERCERT_BLOB', // Issuer SSL certificate from memory blob. Available as of PHP 8.1.0 and cURL 7.71.0.
        'CURLOPT_KEEP_SENDING_ON_ERROR',
        'CURLOPT_KEYPASSWD',
        'CURLOPT_KRB4LEVEL',
        'CURLOPT_MAXAGE_CONN',
        'CURLOPT_MAXCONNECTS',
        'CURLOPT_MAXFILESIZE_LARGE',
        'CURLOPT_MAX_RECV_SPEED_LARGE',
        'CURLOPT_MAX_SEND_SPEED_LARGE',
        'CURLOPT_NEW_DIRECTORY_PERMS',
        'CURLOPT_NEW_FILE_PERMS',
        'CURLOPT_NOBODY',
        'CURLOPT_NOSIGNAL',
        'CURLOPT_PATH_AS_IS',
        'CURLOPT_PINNEDPUBLICKEY',
        'CURLOPT_PIPEWAIT',
        'CURLOPT_PORT',
        'CURLOPT_POSTQUOTE',
        'CURLOPT_POSTREDIR',
        'CURLOPT_PREQUOTE',
        'CURLOPT_PRE_PROXY',
        'CURLOPT_PRIVATE',
        'CURLOPT_PROGRESSFUNCTION',
        'CURLOPT_PROTOCOLS',
        'CURLOPT_PROXYPASSWORD',
        'CURLOPT_PROXYPORT',
        'CURLOPT_PROXYTYPE',
        'CURLOPT_PROXYUSERNAME',
        'CURLOPT_PROXY_SSLVERSION',
        'CURLOPT_PROXY_CAINFO_BLOB',
        'CURLOPT_PROXY_ISSUERCERT', // Proxy issuer SSL certificate filename. Available as of PHP 8.1.0 and cURL 7.71.0.
        'CURLOPT_PROXY_ISSUERCERT_BLOB', // Proxy issuer SSL certificate from memory blob. Available as of PHP 8.1.0 and cURL 7.71.0.
        'CURLOPT_PROXY_SSLCERT_BLOB', // SSL proxy client certificate from memory blob. Available as of PHP 8.1.0 and cURL 7.71.0.
        'CURLOPT_PROXY_SSLKEY_BLOB', // Private key for proxy cert from memory blob. Available as of PHP 8.1.0 and cURL 7.71.0.
        'CURLOPT_PROXY_SSL_OPTIONS',
        'CURLOPT_PROXY_TRANSFER_MODE',
        'CURLOPT_READDATA',
        'CURLOPT_READFUNCTION',
        'CURLOPT_REDIR_PROTOCOLS',
        'CURLOPT_REQUEST_TARGET',
        'CURLOPT_RESUME_FROM',
        'CURLOPT_RETURNTRANSFER',
        'CURLOPT_RTSP_CLIENT_CSEQ',
        'CURLOPT_RTSP_REQUEST',
        'CURLOPT_RTSP_SERVER_CSEQ',
        'CURLOPT_RTSP_SESSION_ID',
        'CURLOPT_RTSP_STREAM_URI',
        'CURLOPT_RTSP_TRANSPORT',
        'CURLOPT_SAFE_UPLOAD',
        'CURLOPT_SHARE',
        'CURLOPT_SSH_AUTH_TYPES',
        'CURLOPT_SSH_KNOWNHOSTS',
        'CURLOPT_SSH_PRIVATE_KEYFILE',
        'CURLOPT_SSLCERTPASSWD',
        'CURLOPT_SSLCERT_BLOB', // SSL client certificate from memory blob. Available as of PHP 8.1.0 and cURL 7.71.0.
        'CURLOPT_SSLENGINE_DEFAULT',
        'CURLOPT_SSLKEY_BLOB', // Private key for client cert from memory blob. Available as of PHP 8.1.0 and cURL 7.71.0.
        'CURLOPT_SSL_OPTIONS',
        'CURLOPT_STREAM_WEIGHT',
        'CURLOPT_TCP_KEEPIDLE',
        'CURLOPT_TCP_KEEPINTVL',
        'CURLOPT_TIMEVALUE',
        'CURLOPT_TIMEVALUE_LARGE',
        'CURLOPT_TRANSFERTEXT',
        'CURLOPT_UNRESTRICTED_AUTH',
        'CURLOPT_UPKEEP_INTERVAL_MS',
        'CURLOPT_UPLOAD',
        'CURLOPT_UPLOAD_BUFFERSIZE',
        'CURLOPT_WILDCARDMATCH',
        'CURLOPT_WRITEFUNCTION',
        'CURLOPT_WRITEHEADER',
        'CURLOPT_XFERINFOFUNCTION',
        'CURLSSLOPT_NATIVE_CA',
        'CURLSSLOPT_NO_PARTIALCHAIN',
    ];

    /**
     * Check if the option is valid
     *
     * @param  string $option
     * @return bool
     */
    public static function isValidOption(string $option): bool
    {
        return (array_key_exists($option, self::$cliOptions) || array_key_exists($option, self::$phpOptions) ||
            array_key_exists($option, self::$specialOptions));
    }

    /**
     * Check if the option is a valid CLI option
     *
     * @param  string $option
     * @return bool
     */
    public static function isCliOption(string $option): bool
    {
        return array_key_exists($option, self::$cliOptions);
    }

    /**
     * Check if the option is a valid PHP option
     *
     * @param  string $option
     * @return bool
     */
    public static function isPhpOption(string $option): bool
    {
        return array_key_exists($option, self::$phpOptions);
    }

    /**
     * Check if the option is a valid special option
     *
     * @param  string $option
     * @return bool
     */
    public static function isSpecialOption(string $option): bool
    {
        return in_array($option, self::$specialOptions);
    }

    /**
     * Check if the option requires a value
     *
     * @param  string $option
     * @return bool
     */
    public static function isValueOption(string $option): bool
    {
        return array_key_exists($option, self::$valueOptions);
    }

    /**
     * Check if the option is a boolean option
     *
     * @param  string $option
     * @return bool
     */
    public static function isBooleanOption(string $option): bool
    {
        return !array_key_exists($option, self::$valueOptions);
    }

    /**
     * Parse the CLI command options string
     *
     * @param  string $optionString
     * @return array
     */
    public static function parseCliOptions(string $optionString): array
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
     * Extract CLI option values
     *
     * @param  array $options
     * @return array
     */
    public static function extractCliOptionValues(array $options): array
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

        return $optionValues;
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

        /**
         * TO-DO: Need to handle files
         */
        if ($client->getRequest()->hasData()) {
            if ($client->getRequest()->isMultipartForm()) {
                $data = $client->getRequest()->getData(true);
                foreach ($data as $key => $value) {
                    $command .= ' -F ' . http_build_query([$key => $value]);
                }
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
            $options      = self::parseCliOptions($optionString);
        }

        $request = new Request($requestUri);
        $curl    = new Curl();

        if (!empty($options)) {
            self::convertCliOptions($options, $curl, $request);
        }

        return new Client($request, $curl);
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
        $optionValues = self::extractCliOptionValues($options);

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
            foreach (self::$phpOptions as $phpOption => $curlOption) {
                if (is_array($curlOption)) {
                    foreach ($curlOption as $cOpt) {
                        if ((str_starts_with($option, '--') && str_contains($cOpt, $option)) || str_starts_with($cOpt, $option)) {
                            if (self::isValueOption($option)) {
                                $optionValue = self::$valueOptions[$option] ?? $value;
                            } else {
                                $optionValue = true;
                            }
                            $curl->setOption(constant($phpOption), $optionValue);
                            break;
                        }
                    }
                } else if ((str_starts_with($option, '--') && str_contains($curlOption, $option)) || str_starts_with($curlOption, $option)) {
                    if (self::isValueOption($option)) {
                        $optionValue = self::$valueOptions[$option] ?? $value;
                    } else {
                        $optionValue = true;
                    }
                    $curl->setOption(constant($phpOption), $optionValue);
                    break;
                }
            }
        }
    }

}
