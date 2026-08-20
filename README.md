pop-http
========

[![Build Status](https://github.com/popphp/pop-http/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-http/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-http)](http://cc.popphp.org/pop-http/)

[![Join the chat at https://discord.gg/TZjgT74U7E](https://media.popphp.org/img/discord.svg)](https://discord.gg/TZjgT74U7E)

* [Overview](#overview)
* [Install](#install)
* [Client](#client)
  - [Quickstart](#quickstart)
  - [Auth](#auth)
  - [Options](#options)
  - [Automatic Content Negotiation](#automatic-content-negotiation)
  - [Requests](#requests)
  - [Rendering Requests](#rendering-requests)
  - [Responses](#responses)
  - [PSR-7 / PSR-17 / PSR-18 Conformance](#psr-7--psr-17--psr-18-conformance)
  - [Handlers](#handlers)
  - [Middleware](#middleware)
  - [Retry Middleware](#retry-middleware)
  - [Logging Middleware](#logging-middleware)
* [Promises](#promises)
  - [Wait](#wait)
  - [Then](#then)
  - [Forwarding](#forwarding)
  - [Nesting](#nesting)
* [CLI Conversion](#cli-conversions)
* [Server](#server)
  - [Request Headers & Data](#request-headers--data)
  - [Accept Header Negotiation](#accept-header-negotiation)
  - [Filters](#filters)
  - [Redirects & Forwards](#redirects--forwards)
  - [Rendering Responses](#rendering-responses)
  - [Uploads](#uploads)

Overview
--------

`pop-http` is the main HTTP component for the Pop PHP Framework. It provides a robust
set of features to manage the many aspects of HTTP connections. It provides functionality
for the following:

- **HTTP Client Transactions**
  - Create and manage outbound HTTP client requests and their responses
  - Full control over request & response headers and data
  - Manage authorization
  - Manage and parse different request & response data types
  - Automatic content negotiation of response data, where possible
  - Use the request handler of your choice: curl, stream, curl-multi or a mock handler for testing (defaults to curl)
  - Send sync or async requests
  - Support for promises
  - Render client requests out to a raw string
  - 2-way client to curl CLI command conversions
  - Full PSR-7, PSR-17 and PSR-18 conformance, for interoperability with other PSR-compatible libraries
  - A composable middleware pipeline for intercepting, retrying or logging outbound requests
- **HTTP Server Transactions**
  - Manage inbound HTTP server requests, headers and data
  - Create and manage outbound HTTP server responses, headers and data
  - Automatic content negotiation of request data, where possible
  - Render server responses out to a raw string
  - Easily handle file uploads and apply server-side settings and restrictions

`pop-http` is a component of the [Pop PHP Framework](https://www.popphp.org/).

Install
-------

Install `pop-http` using Composer.

    composer require popphp/pop-http

Or, require it in your composer.json file

    "require": {
        "popphp/pop-http" : "^6.0.0"
    }

[Top](#pop-http)

Client
------

At its core, the client object works with a request object, a handler object and a response object to successfully
execute an HTTP request. The request object can have request data. Both the request and response objects can have
headers and a body. The response object will have a response code and response message, along with other helper
functions to determine if the request yielded a successful response or an error.

**NOTE:** The constructor of the `Pop\Http\Client` class is flexible and can take any of the following
parameters in any order:

- A URI string
- A `Pop\Http\Client\Request` object (which contains the URI)
- A `Pop\Http\Client\Response` object (not common, as the response object is typically auto-populated)
- A `Pop\Http\Auth` object (to assist with authorization)
- A handler object that is an instance of `Pop\Http\Client\Handler\HandlerInterface`
- An `$options` array 

### Quickstart

The most basic way to wire up a simple `GET` request would be:

```php
use Pop\Http\Client;

$response = Client::get('http://localhost/');
```

which is also the equivalent to:

```php
use Pop\Http\Client;

$client   = new Client('http://localhost/');
$response = $client->get();
```

**or**

```php
use Pop\Http\Client;

$client   = new Client('http://localhost/', ['method' => 'GET']);
$response = $client->send();
```

In the examples above, the `$response` object returned is a full response object, complete with all of the headers,
data, messaging and content that comes with an HTTP response. If you want to simply access the pertinent content of
the response object, this method can be used:

```php
$content = $response->getParsedResponse();
```

That method will attempt to auto-negotiate using the `Content-Type` header and give an appropriate data response
or object. For example, if the content type of the response was `application/json`, then the data returned will
be a PHP array representation of that JSON data.

**POST Example**

A `POST` request can be given some data in the `$options` array to send along with the request:

```php
use Pop\Http\Client;

$response = Client::post('http://localhost/post', [
    'data' => [
        'foo' => 'bar',
        'baz' => 123
    ]
]);
```

which is also the equivalent to:

```php
use Pop\Http\Client;

$client = new Client('http://localhost/post', [
    'data' => [
        'foo' => 'bar',
        'baz' => 123
    ]
]);
$response = $client->post();
```

**or**

```php
use Pop\Http\Client;

$client = new Client('http://localhost/post', [
    'method' => 'POST',
    'data'   => [
        'foo' => 'bar',
        'baz' => 123
    ]
]);
$response = $client->send();
```

All of the standard HTTP request methods are accessible in the manner outlined above. For example:

```php
use Pop\Http\Client;

$responsePut    = Client::put('http://localhost/put', ['data' => ['foo' => 'bar']]);
$responsePatch  = Client::patch('http://localhost/patch', ['data' => ['foo' => 'bar']]);
$responseDelete = Client::delete('http://localhost/delete', ['data' => ['foo' => 'bar']]);
```

[Top](#pop-http)

### Auth

There is an auth header class to assist in wiring up different types of standard authorization headers:

- Basic
- Bearer Token
- API Key
- Digest

**Basic**

```php
use Pop\Http\Auth;
use Pop\Http\Client;

$response = Client::post('http://localhost/auth', Auth::createBasic('username', 'password'));
```

**Bearer Token**

```php
use Pop\Http\Auth;
use Pop\Http\Client;

$response = Client::post('http://localhost/auth', Auth::createBearer('MY_AUTH_TOKEN'));
```

**API Key**

```php
use Pop\Http\Auth;
use Pop\Http\Client;

$response = Client::post('http://localhost/auth', Auth::createKey('MY_API_KEY'));
```

**Digest**

Digest authorization can be complex and require a number of different parameters. This is a basic example:

```php
use Pop\Http\Auth;
use Pop\Http\Client;

$response = Client::post(
    'http://localhost/auth',
    Auth::createDigest(
        new Auth\Digest('realm', 'username', 'password', '/uri', 'SERVER_NONCE')
    )
);
```

The digest auth header can be created from a `WWW-Authenticate` header provided by the initial server response:

```php
use Pop\Http\Auth;
use Pop\Http\Client;

$response = Client::post(
    'http://localhost/auth',
    Auth::createDigest(
        Auth\Digest::createFromWwwAuth($wwwAuthHeader, 'username', 'password', '/uri')
    )
);
```

[Top](#pop-http)

### Options

The client object supports an `$options` array to pass in general configuration details and data for the request.
Supported keys in the options array are:

- `base_uri` - the base URI for re-submitting many requests with the same client to different endpoints on the same domain
- `method` - the request method (GET, POST, PUT, PATCH, DELETE, etc.)
- `headers` - an array of request headers
- `user_agent` - the user agent string
- `query` - an array of request query data - reserved for only a URL-encoded query string
- `data` - an array of request data - can be any request data
- `files` - an array of files on disk to be sent with the request
- `type` - set the request type (URL-form, JSON, XML or multipart/form)
  + `Request::URLENCODED` (`application/x-www-form-urlencoded`)
  + `Request::JSON` (`application/json`)
  + `Request::XML` (`application/xml`)
  + `Request::MULTIPART` (`multipart/form-data`)
- `auto` - trigger automatic content negotiation and return the parsed content, if possible (boolean)
- `async` - trigger an asynchronous request (boolean)
- `verify_peer` - enforce or disallow verifying the host for SSL connections (boolean)
- `allow_self_signed` - allow or disallow the use of self-signed certificates for SSL connections (boolean)
- `no_content_length` - omit content length header
- `raw_data` - use raw data contents
- `force_custom_method` - for Curl only. Forces the use of `CURLOPT_CUSTOMREQUEST` (boolean)

Here is an example using a `base_uri`:

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$client    = new Client(['base_uri' => 'http://localhost']);
$response1 = $client->get('/page1'); // Will request http://localhost/page1
$response2 = $client->get('/page2'); // Will request http://localhost/page2
$response3 = $client->get('/page3'); // Will request http://localhost/page3
```

Here is an example to send some JSON data:

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$client = new Client('http://localhost/post', [
    'data'   => [
        'foo' => 'bar',
        'baz' => 123
    ],
    'type' => Request::JSON // "application/json"
]);

$response = $client->post();
```

Here is an example to send some files:

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$client = new Client('http://localhost/post', [
    'method' => 'POST',
    'files' => [
        '/path/to/file/image1.jpg',
        '/path/to/file/image2.jpg',    
    ],
    'type' => Request::MULTIPART // "multipart/form-data"
]);

$response = $client->send();
```

[Top](#pop-http)

### Automatic Content Negotiation

In the above examples, the `$response` returned is a full response object. If you want to get the actual response
content, as mentioned above, you would call:

```php
$content = $response->getParsedResponse();
```

There are three ways to attempt content negotiation automatically and return the parsed content:

1. If you would like to attempt to parse the response content as JSON, regardless of any `Content-Type` header value
   or absence thereof, you can call the `json()` method to obtain a PHP array representation of the data:

```php
use Pop\Http\Client;

// Returns an array
$data = Client::get('http://localhost/')->json();
```

2. Similarly, if you would prefer to have a `Collection` object populated with the data content returned,
   you can call the `collect()` method. Internally, this will attempt the `json()` method as well:

```php
use Pop\Http\Client;

// Returns an instance of Pop\Utils\Collection
$data = Client::get('http://localhost/')->collect();
```

3. You can set the `auto` option to true, which is contingent on the server response having the correct
   `Content-Type` header. This will return a PHP array representation of the data:

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$client = new Client('http://localhost/', ['auto' => true]);
$data   = $client->get(); // Returns an array
```

If you still need to access the full response object, you can access it by calling:

```php
$clientResponse = $client->getResponse();
```

[Top](#pop-http)

### Requests

You can have granular control over the configuration of the request object by interacting with it directly.

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$request = new Request('http://localhost/', 'POST');
$request->createAsJson();
$request->addHeaders([
    'X-Custom-Header: Custom-Value',
]);
$request->setData([
    'foo' => 'bar',
    'baz' => 123
]);

$client = new Client($request);
$response = $client->send();
```

There are four ways to configure the request for four different common data types:

- JSON
- XML
- URL-encoded form
- Multipart form

```php
use Pop\Http\Client\Request;

$requestJson  = Request::createJson('http://localhost/', 'POST', $data);
$requestXml   = Request::createXml('http://localhost/', 'POST', $data);
$requestUrl   = Request::createUrlForm('http://localhost/', 'POST', $data);
$requestMulti = Request::createMultipart('http://localhost/', 'POST', $data);
```

**or**

```php
$request->createAsJson();
$request->createAsXml();
$request->createAsUrlEncoded();
$request->createAsMultipart();
```

Each way effectively sets the appropriate `Content-Type` header and properly formats the data for that data type.

[Top](#pop-http)

### Rendering Requests

Client requests can be rendered out to a raw string:

```php
use Pop\Http\Client;

$client = new Client('http://localhost:8000/files.php', [
    'method' => 'POST',
    'data'  => [
        'foo' => 'bar'
    ],
    'headers' => [
        'Authorization' => 'Bearer 123456789',
    ],
    'type' => Request::URLENCODED
]);
echo $client->render();
```

Which would produce a string like this:

```text
POST /files.php HTTP/1.1
Host: localhost:8000
Authorization: Bearer 123456789
Accept: application/json
Content-Type: application/x-www-form-urlencoded
Content-Length: 7

foo=bar
```

[Top](#pop-http)

### Responses

Upon sending a request, the response object is automatically created and populated with the content from the raw response.

```php
use Pop\Http\Client;

$response = Client::post('http://localhost/post', [
    'data' => [
        'foo' => 'bar',
        'baz' => 123
    ]
]);

echo $response->getCode();                      // 200
echo $response->getMessage();                   // OK
var_dump($response->getHeaders());              // An array of header name => array of string values
var_dump($response->hasHeader('Content-Type')); // Boolean result
var_dump($response->getBody());                 // A body object than contains the response content
```

The header and body entities of both requests and responses are actually objects that store all their pertinent data.
To access the actual data content, you would have to use methods such as these:

```php
// i.e., 'application/json'
var_dump($response->getHeaderValueAsString('Content-Type'));
// Get actual content of the body object
var_dump($response->getBodyContent());
```

As mentioned above, using the following method will get the parsed content based on `Content-Type`:

```php
var_dump($response->getParsedResponse());
```

There are a number of helper methods to determine the response's status:

```php
$response->isSuccess();              // Bool on 100/200/300-level responses
$response->isError();                // Bool on 400/500-level responses
$response->isContinue();             // Bool on 100-level response
$response->isOk();                   // Bool on 200 response
$response->isCreated();              // Bool on 201 response
$response->isAccepted();             // Bool on 202 response
$response->isNoContent();            // Bool on 204 response
$response->isRedirect();             // Bool on 300-level response
$response->isMovedPermanently();     // Bool on 301 response
$response->isFound();                // Bool on 302 response
$response->isClientError();          // Bool on 400-level response
$response->isBadRequest();           // Bool on 400 response
$response->isUnauthorized();         // Bool on 401 response
$response->isForbidden();            // Bool on 403 response
$response->isNotFound();             // Bool on 404 response
$response->isMethodNotAllowed();     // Bool on 405 response
$response->isNotAcceptable();        // Bool on 406 response
$response->isRequestTimeout();       // Bool on 408 response
$response->isConflict();             // Bool on 409 response
$response->isLengthRequired();       // Bool on 411 response
$response->isUnsupportedMediaType(); // Bool on 415 response
$response->isUnprocessableEntity();  // Bool on 422 response
$response->isTooManyRequests();      // Bool on 429 response
$response->isServerError();          // Bool on 500-level response
$response->isInternalServerError();  // Bool on 500 response
$response->isBadGateway();           // Bool on 502 response
$response->isServiceUnavailable();   // Bool on 503 response
```

[Top](#pop-http)

### PSR-7 / PSR-17 / PSR-18 Conformance

`pop-http`'s request, response, URI and body objects implement the standard PSR-7 HTTP message interfaces
(`psr/http-message`), and `Client` implements the PSR-18 HTTP client interface (`psr/http-client`). A set of
PSR-17 factories (`psr/http-factory`) is also available under `Pop\Http\Factory`. This means `pop-http` objects
can be passed to, or accepted from, any other library that speaks PSR-7/17/18, without any adapter layer.

| pop-http class | PSR interface |
|---|---|
| `Client\Request` | `Psr\Http\Message\RequestInterface` |
| `Client\Response`, `Server\Response` | `Psr\Http\Message\ResponseInterface` |
| `Server\Request` | `Psr\Http\Message\ServerRequestInterface` |
| `Uri` | `Psr\Http\Message\UriInterface` |
| `Body` | `Psr\Http\Message\StreamInterface` |
| `Server\UploadedFile` | `Psr\Http\Message\UploadedFileInterface` |
| `Client` (via `sendRequest()`) | `Psr\Http\Client\ClientInterface` |

Alongside `pop-http`'s existing mutable API (`addHeader()`, `setBody()`, etc.), every request/response/URI/body
object also supports the PSR-7 immutable `with*()`/`without*()` methods, which return a new instance rather than
modifying the original:

```php
use Pop\Http\Client\Request;

$request     = new Request('http://localhost/');
$withHeader  = $request->withHeader('X-Custom-Header', 'Custom-Value'); // A new instance
$withoutData = $withHeader->withoutHeader('X-Custom-Header');           // Another new instance
```

`Client::sendRequest()` implements PSR-18, so a `Client` can be handed to any code written against
`Psr\Http\Client\ClientInterface`:

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$client   = new Client();
$request  = new Request('http://localhost/');
$response = $client->sendRequest($request); // Returns a Psr\Http\Message\ResponseInterface
```

The PSR-17 factories build the corresponding native `pop-http` objects, for code that only knows the factory
interfaces:

```php
use Pop\Http\Factory\RequestFactory;
use Pop\Http\Factory\UriFactory;
use Pop\Http\Factory\StreamFactory;

$requestFactory = new RequestFactory();
$uriFactory     = new UriFactory();
$streamFactory  = new StreamFactory();

$request = $requestFactory->createRequest('POST', 'http://localhost/');
$uri     = $uriFactory->createUri('http://localhost/');
$body    = $streamFactory->createStream('some content');
```

The other available factories are `ResponseFactory`, `ServerRequestFactory` and `UploadedFileFactory`.

**Backward-compatibility note:** PSR-7 reserves the method names `getHeader()` and `getHeaders()` for returning
plain string values, which collided with this library's pre-existing methods of the same name that returned
`Pop\Mime\Part\Header` object(s). To satisfy both contracts without breaking either, the old object-returning
methods were renamed to **`getHeaderObject(string $name)`** and **`getHeaderObjects()`**. If you were relying on
`getHeader()`/`getHeaders()` returning `Header` objects prior to this change, switch to `getHeaderObject()`/
`getHeaderObjects()`; `getHeader()`/`getHeaders()` now return plain string arrays, per PSR-7.

[Top](#pop-http)

### Handlers

You can choose to use a different handler with the client object. The available handlers are:

- `Pop\Http\Client\Handler\Curl` - uses the PHP curl extension (default)
- `Pop\Http\Client\Handler\Stream` - uses PHP stream functionality
- `Pop\Http\Client\Handler\CurlMulti` - reserved for multiple parallel/concurrent requests at the same time
- `Pop\Http\Client\Handler\Mock` - queues canned responses (or throwables) for tests, with no real network I/O

You can inject the handler into the client's constructor:

```php
use Pop\Http\Client;
use Pop\Http\Client\Handler\Stream;

$client = new Client('http://localhost/', new Stream());
```

or through the `setHandler()` method:

```php
use Pop\Http\Client;
use Pop\Http\Client\Handler\Stream;

$client = new Client('http://localhost/');
$client->setHandler(new Stream());
```

And then you can interact with the handler using the `getHandler()` method:

```php
// Example with a CURL handler
$client->getHandler()->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
```

[Top](#pop-http)

#### Curl

The handlers allow you to further customize the request by interfacing with each respective handler's settings.
For `Curl`, that mainly includes setting additional Curl options needed for the request. (**Please Note:** Many
of the required Curl options, such as `CURLOPT_URL`  and `CURLOPT_HTTPHEADER` are automatically set based on
the initial configuration of the client and request objects.) 

```php
use Pop\Http\Client;
use Pop\Http\Client\Handler\Curl;

$curl = new Curl();
$curl->setOptions([
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0
]);

$client = new Client('http://localhost/');
$client->setHandler($curl);
```

[Top](#pop-http)

#### Stream

For `Stream`, that includes setting context options and parameters needed for the request. (**Please Note:**
Many of the required Stream context options, such as `['http']`, `['http']['method']` and `['http']['header']`
are automatically set based on the initial configuration of the client and request objects.)

```php
use Pop\Http\Client;
use Pop\Http\Client\Handler\Stream;

$stream = new Stream();
$stream->setContextOptions([
    'http' => [
        'protocol_version' => '1.0'
    ]
]);

$client = new Client('http://localhost/');
$client->setHandler($stream);
```

[Top](#pop-http)

#### Curl Multi-Handler

The Curl Multi-Handler is a special use-case handler that allows for multiple parallel/concurrent requests
to be made at the same time. Each request will get its own `Client` object, which will be registered with
the multi-handler object. The simplest way to configure a multi-handler object would be:

```php
use Pop\Http\Client;

// Three GET requests
$multiHandler = Client::createMulti([
    'http://localhost/test1.php',
    'http://localhost/test2.php',
    'http://localhost/test3.php'
]);
```

**or**

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

// Three POST requests
$multiHandler = Client::createMulti([
    new Request('http://localhost/test1.php', 'POST'),
    new Request('http://localhost/test2.php', 'POST'),
    new Request('http://localhost/test3.php', 'POST')
]);
```

From there, the multi-handler object can send the requests:

```php
$running = null;

do {
    $multiHandler->send($running);
} while ($running);

$responses = $multiHandler->getAllResponses();
```

The `$multiHandler->getAllResponses()` method will return an array of all the response objects returned
from each of the requests.

Here is a more verbose way to configure a multi-handler object:

```php
use Pop\Http\Client;
use Pop\Http\Client\Handler\CurlMulti;

$multiHandler = new CurlMulti();
$client1      = new Client('http://localhost/test1.php', $multiHandler);
$client2      = new Client('http://localhost/test2.php', $multiHandler);
$client3      = new Client('http://localhost/test3.php', $multiHandler);
```

[Top](#pop-http)

#### Mock

The `Mock` handler is a drop-in replacement for `Curl`/`Stream` that never sends real network traffic - useful for
both testing this library itself and testing your own application code against a `Client`. It supports two ways
to configure canned results:

- `queue()` - a FIFO queue of responses (or exceptions) to return, one per request
- `when()` - a callback matcher, checked before the queue, for returning a specific response based on the request

```php
use Pop\Http\Client;
use Pop\Http\Client\Handler\Mock;
use Pop\Http\Client\Response;

$mock = new Mock();
$mock->queue(new Response(['code' => 200, 'body' => '{"result":"ok"}']));
$mock->queue(new Response(['code' => 404]));

$client = new Client('http://localhost/', $mock);

$response = $client->send(); // Returns the first queued response (200)
$response = $client->send(); // Returns the second queued response (404)
```

`when()` takes a callback of the shape `function (Client\Request $request): bool` and is checked first, in
registration order - the first matching callback wins, falling back to the queue if none match:

```php
$mock->when(
    fn($request) => $request->getMethod() === 'POST',
    new Response(['code' => 201])
);
```

You can also queue a `\Throwable` to simulate a network failure:

```php
use Pop\Http\Client\Handler\Exception;

$mock->queue(new Exception('Simulated connection failure.'));
```

Every request actually dispatched through the mock handler is recorded and available for assertions:

```php
$mock->getRequests();    // An array of every Client\Request dispatched, in order
$mock->getLastRequest(); // The most recently dispatched Client\Request, or null
```

[Top](#pop-http)

### Middleware

`Client` supports a composable middleware pipeline around its outbound dispatch - registered via
`addMiddleware()`, applied uniformly to `send()`, `sendAsync()` and `sendRequest()`. It follows the familiar
onion/interceptor pattern (in the style of PSR-15, though typed for `Client`'s outbound flow rather than a
literal PSR-15 implementation): registration order is wrap order, so the first-registered middleware runs first
on the way in and last on the way out.

```php
use Pop\Http\Client;

$client = new Client('http://localhost/');

$client->addMiddleware(function ($request, $handler) {
    // Before: inspect or modify $request
    $response = $handler->handle($request);
    // After: inspect or modify $response
    return $response;
});

$response = $client->send();
```

A plain closure of the shape `function ($request, $handler)` is accepted directly, or you can implement
`Pop\Http\Client\Middleware\MiddlewareInterface` for a reusable class-based middleware. A middleware can also
short-circuit the chain entirely by simply not calling `$handler->handle($request)` - useful for things like
returning a cached response without ever touching the network:

```php
$client->addMiddleware(function ($request, $handler) {
    if ($cached = getFromCache($request)) {
        return $cached; // $handler->handle() is never called
    }
    return $handler->handle($request);
});
```

**Note:** middleware does not apply to a `Client` driven via `Client::createMulti()`/`CurlMulti` - the
multi-handler runs its own request loop independently of the middleware pipeline.

[Top](#pop-http)

### Retry Middleware

`Pop\Http\Client\Middleware\RetryMiddleware` is a ready-made middleware that retries a request on transient
network failures and/or specific response status codes, with exponential backoff and jitter between attempts.

```php
use Pop\Http\Client;
use Pop\Http\Client\Middleware\RetryMiddleware;

$client = new Client('http://localhost/');
$client->addMiddleware(new RetryMiddleware(3)); // Up to 3 retries

$response = $client->send();
```

With no further configuration, it retries up to 3 times on network exceptions or a `429`/`502`/`503`/`504`
response, and only for idempotent-safe methods (`GET`, `HEAD`, `PUT`, `DELETE`, `OPTIONS`) - `POST`/`PATCH`
requests are never retried by default, since retrying a non-idempotent request risks duplicating a side effect
(e.g. double-charging, double-creating a resource). Every aspect of this is configurable:

```php
$retry = new RetryMiddleware();
$retry->setMaxRetries(5)
    ->setRetryableMethods(['GET', 'HEAD', 'PUT', 'DELETE', 'OPTIONS', 'POST'])
    ->setRetryableStatusCodes([429, 500, 502, 503, 504])
    ->setBaseDelay(0.1)   // Base delay, in seconds, for the exponential backoff
    ->setMaxDelay(10.0)   // Maximum delay, in seconds, before jitter is applied
    ->setOnRetry(function ($attempt, $request, $response, $exception, $delaySeconds) {
        // Fires once per retry attempt, before that attempt's sleep - useful for
        // logging or metrics (see the LoggingMiddleware section below)
    });

$client->addMiddleware($retry);
```

If the server responds with a `Retry-After` header, that value is honored over the computed backoff delay for
that attempt (capped at `setMaxDelay()`). If the request has a body, it must be seekable for a retry to happen at
all - a non-seekable body (e.g. streamed from a non-rewindable source) causes the retry to be skipped entirely,
since resending a partially-consumed stream would corrupt the payload.

[Top](#pop-http)

### Logging Middleware

`Pop\Http\Client\Middleware\LoggingMiddleware` is a ready-made middleware that logs one line per dispatch attempt
to any [PSR-3](https://www.php-fig.org/psr/psr-3/) `Psr\Log\LoggerInterface`, at a level derived from the outcome:
`info` for a successful response, `warning` for a `4xx`, `error` for a `5xx` or a thrown exception.

```php
use Pop\Http\Client;
use Pop\Http\Client\Middleware\LoggingMiddleware;

$client = new Client('http://localhost/');
$client->addMiddleware(new LoggingMiddleware($logger)); // Any PSR-3 logger

$response = $client->send();
```

Known-sensitive request headers (`Authorization`, `Cookie`, `Set-Cookie`, `X-Api-Key`, `Proxy-Authorization`) are
redacted to `[REDACTED]` in the logged context by default - configurable via `setRedactedHeaders()`/
`addRedactedHeader()`. Only the request's headers are ever logged, never the response's. Request/response bodies
are excluded from the log entirely unless explicitly opted in:

```php
$logging = new LoggingMiddleware($logger);
$logging->setIncludeBody(true)
    ->setMaxBodyLength(500); // Truncate logged body content to 500 characters
```

`LoggingMiddleware` has no special coupling with `RetryMiddleware` - the two compose purely through registration
order. Register `LoggingMiddleware` *after* `RetryMiddleware` (closer to the actual dispatch) to log every
individual retry attempt; register it *before* to log only the final outcome:

```php
$client->addMiddleware(new RetryMiddleware(3))
    ->addMiddleware(new LoggingMiddleware($logger)); // Logs every attempt
```

For a structured "why did this retry happen" log line with the failure reason and computed delay,
`LoggingMiddleware::logRetriesTo()` adapts a logger onto `RetryMiddleware`'s `setOnRetry()` hook:

```php
$client->addMiddleware(
    (new RetryMiddleware(3))->setOnRetry(LoggingMiddleware::logRetriesTo($logger))
)->addMiddleware(new LoggingMiddleware($logger));
```

[Top](#pop-http)

Promises
--------

Promises allow you to stage asynchronous requests within the application. When you initialize a client object
and call it asynchronously, it will return a promise object. There are few different ways to achieve this:

```php
use Pop\Http\Client;

$promise = Client::postAsync('http://localhost/');
```

which is equivalent to:

```php
use Pop\Http\Client;

$client  = new Client('http://localhost/');
$promise = $client->postAsync();
```

**or**

```php
use Pop\Http\Client;

$client  = new Client('http://localhost/', ['method' => 'POST']);
$promise = $client->sendAsync();
```

**or**

```php
use Pop\Http\Client;

$client  = new Client('http://localhost/', ['method' => 'POST', 'async' => true]);
$promise = $client->send();
```

The multi-handler supports asynchronous requests as well and will return a promise object:

```php
use Pop\Http\Client;

$multiHandler = Client::createMulti([
    'http://localhost/test1.php',
    'http://localhost/test2.php',
    'http://localhost/test3.php'
]);

$promise = $multiHandler->sendAsync();
```

[Top](#pop-http)

### Wait

Once you have a promise object, the most basic way to interact with it is to call `wait()`, which simply
triggers the request and waits until the request is finished before allowing the application to continue.
Upon completion, the promise will return a response object. Otherwise, it will throw an exception, so it
is best to wrap the call in a `try/catch` block:

```php
try {
    $response = $promise->wait();
    print_r($response->getParsedResponse());
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}
```

If you need something that degrades a little more gracefully and need to suppress the thrown exception,
you can pass `false` as the `$unwrap` parameter into the `wait()` method to prevent the exception from
being thrown:

```php
$response = $promise->wait(false);
if ($response instanceof Response) {
    print_r($response->getParsedResponse());
}
```

[Top](#pop-http)

### Then

You can use the `then()` method, along with `catch()` and `finally()` to assign callbacks to handle 
each specific scenario:

- `then()` - on success callback
- `catch()` - on failure callback
- `finally()` - callback to run at the end no matter what the result is

Additionally, a cancel callback can be set with the `setCancel()` method and will be triggered at any
time the promise is cancelled. Once the promise is configured, the `resolve()` method needs to be called
to finish the request.

```php
use Pop\Http\Promise;
use Pop\Http\Client\Response;

$promise->setCancel(function(Promise $promise) {
    // Do something upon cancellation
});

$promise->then(function(Response $response) {
    // Do something on success
})->catch(function(Response $response) {
    // Do something on failure
})->finally(function(Promise $promise) {
    // Do something at the end
});

$promise->resolve();
```

As a convenience for a simple `then()` call, you can pass a `$resolve` flag as `true` to force the promise
to resolve without having to call the `resolve()` method:

```php
use Pop\Http\Client\Response;

// Force resolve
$promise->then(function(Response $response) {
    // Do something on success
}, true);
```

The `catch()` and `finally()` methods also have the same `$resolve` force flag.

[Top](#pop-http)

### Forwarding

You can chain multiple `then()` method calls together, which is sometimes called "forwarding" a promise.
The return of the first `then()` call needs to be another promise object.

```php
use Pop\Http\Client;
use Pop\Http\Client\Response;

$promise1 = Client::getAsync('http://localhost/test1.php');
$promise2 = Client::getAsync('http://localhost/test2.php');

$promise1->then(function(Response $response) use ($promise2) {
    // Do something with the first promise response
    return $promise2;
})->then(function(Response $response) {
    // Do something with the second promise response
});

$promise1->resolve();
```

[Top](#pop-http)

### Nesting

Promises can be "nested" together as well, whereas one resolved promise creates and triggers another promise:

```php
use Pop\Http\Client;
use Pop\Http\Client\Response;

$promise = Client::getAsync('http://localhost/test1.php');

$promise->then(function(Response $response) {
    $data1   = $response->getParsedResponse();
    $promise = Client::getAsync('http://localhost/test2.php')
        ->then(function(Response $response) use ($data1) {
            $data2 = $response->getParsedResponse();
            // Do something with both the data results from promise 1 and 2.
        }, true);
}, true);
```

**Automatic Content Negotiation**

Promises generated by client objects set for automatic content negotiation will return the parsed response content
instead of a full response object.

```php
use Pop\Http\Client;

$promise  = Client::postAsync('http://localhost/', ['auto' => true]);
$response = $promise->wait(false); // The response will be the parsed content response
```

```php
use Pop\Http\Client;

$promise  = Client::postAsync('http://localhost/', ['auto' => true]);
$promise->then(function($response) {
    // The response will be the parsed content response
}, true);
```

[Top](#pop-http)

CLI Conversions
---------------

The CLI conversion feature allows you to convert client request objects into valid `curl` commands to be used on
the CLI. It also supports converting valid `curl` commands into client request objects to be used in a PHP application.

**Curl Command to Client Object**

```php
use Pop\Http\Client;

$client = Client::fromCurlCommand('curl -i -X POST -d"foo=bar&baz=123" http://localhost/post.php');
$client->send();
```

**Client Object to Curl Command**

```php
use Pop\Http\Client;

$client = new Client('http://localhost/post.php', [
    'method' => 'POST',
    'data'   => [
        'foo' => 'bar',
        'baz' => 123
    ]
]);

echo $client->toCurlCommand();
```

```bash
curl -i -X POST --data "foo=bar&baz=123" "http://localhost/post.php"
```

[Top](#pop-http)

Server
------

The server object and its components provide convenient and robust functionality to manage inbound server requests
and outbound responses. At its core, and like the client object, the server object is comprised of a request object
and a response object. However, opposite to the client object, the server object's request is typically auto-populated
from the incoming request headers and data, while the response object is available to be configured as required to
produce and send a response to the calling client.

Within an application, creating a server object will automatically take in the global request data that would come
in from an inbound client request. This includes:

- Request data (`$_GET`, `$_POST`, etc)
- Request headers
- Request body

[Top](#pop-http)

### Request Headers & Data

**Headers**

```php
$headers = $request->getHeaders();
if ($request->hasHeader('Content-Type')) {
    $contentType = $request->getHeaderObject('Content-Type');   // Header object
    var_dump($request->getHeaderValueAsString('Content-Type')); // Header value as string
}
```

**Body**

```php
$body = $request->getBody();           // Body object
var_dump($response->getBodyContent()); // Get actual content of the body object
```

**Method**

```php
$request->isGet();   
$request->isPost();
$request->isPut();
$request->isPatch();
$request->isDelete();
$request->hasFiles();
```

**Data**

```php
$queryData  = $request->getQuery(); // GET Request
$postData   = $request->getPost();
$putData    = $request->getPut();
$patchData  = $request->getPatch();
$deleteData = $request->getDelete();
$filesData  = $request->getFiles();
$serverData = $request->getServer();
$envData    = $request->getEnv();
```

```php
$foo = $request->getQuery('foo'); // GET Request
$foo = $request->getPost('foo');
$foo = $request->getPut('foo');
$foo = $request->getPatch('foo');
$foo = $request->getDelete('foo');
$foo = $request->getFiles('foo');
$foo = $request->getServer('foo');
$foo = $request->getEnv('foo');
```

If there is general data that has been parsed or raw data, that can be accessed via:

```php
$parsedData = $request->getParsedData();
$rawData    = $request->getRawData();
```

As an example, this `curl` command pointing at the following URL with a PHP script can be executed:

```bash
curl -i -X POST --header "Authorization: Bearer 1234567890" \
  --data "foo=bar&baz=123" "http://localhost/post.php"
```

with the contents of `post.php` being:

```php
use Pop\Http\Server;

$server = new Server();

echo $server->request->getHeaderObject('Authorization')->getValue();
if ($server->request->isPost()) {
    print_r($server->request->getPost());
}
```

Automatically, the server object's request object will be populated with the incoming request data. The example
script above will produce:

```text
Bearer 1234567890
Array
(
    [foo] => bar
    [baz] => 123
)
```

From an incoming request, you can populate an appropriate response:

```php
$server->response->setCode(200)
    ->setMessage('OK')
    ->setVersion('1.1')
    ->addHeader('Content-Type', 'text/plain')
    ->setBody('This is the response');

$server->send();
```

which will produce:

```text
HTTP/1.1 200 OK
Content-Type: text/plain

This is the response
```

By default, the server object constructor will instantiate new request and response objects, but you can inject
your own:

```php
use Pop\Http\Server;
use Pop\Http\Server\Request;
use Pop\Http\Server\Response;

$myRequest  = new Request();
$myResponse = new Response();
$server     = new Server($myRequest, $myResponse);
```

[Top](#pop-http)

### Accept Header Negotiation

The request object can parse and negotiate the incoming `Accept` header to help determine what content type
to respond with:

```php
if ($server->request->acceptsHtml()) {
    // render an HTML view
} else if ($server->request->acceptsJson()) {
    // render a JSON response
}
```

`acceptsHtml()`, `acceptsJson()` and `acceptsXml()` are convenience wrappers around the more general `accepts()`
and `getPreferredType()` methods:

```php
$request->accepts('application/json');                             // bool
$request->accepts(['application/xml', 'text/xml']);                // bool, true if either matches
$request->getPreferredType(['text/html', 'application/json']);     // string|null - the best match given what you can respond with
```

Negotiation is RFC 7231 compliant: it understands wildcards (`*/*`, `text/*`), quality values (`q=`), and
`type/subtype` specificity (an exact match outranks `type/*`, which outranks `*/*`, regardless of the order
they appear in the header). A missing `Accept` header is treated the same as `*/*` — per the spec, no header
means the client will accept anything.

**A note on `*/*`:** a bare `*/*` is RFC-correct but doesn't express a real preference — it's what non-browser
clients send by default (`curl` with no `-H`, a bare `fetch()`), whereas real browser navigation always sends
an explicit `text/html` first. Because of this, `acceptsHtml()`, `acceptsJson()` and `acceptsXml()` default to
requiring at least a `type/*` match — a bare `*/*` alone does *not* satisfy them, so a plain `curl` request
against a JSON API falls through to your JSON branch instead of being mistaken for a browser wanting HTML.
Pass an `AcceptSpecificity` to change that behavior:

```php
use Pop\Http\Server\AcceptSpecificity;

$request->acceptsHtml(AcceptSpecificity::Any);   // true even for a bare "Accept: */*" - opt back into the old, permissive check
$request->acceptsHtml(AcceptSpecificity::Exact); // true only if "text/html" was explicitly listed - "text/*" is not enough
```

- `AcceptSpecificity::Any` — a bare `*/*` satisfies the check (this is the default for `accepts()` and
  `getPreferredType()`, which are general-purpose negotiation and leave the call site in control)
- `AcceptSpecificity::Loose` *(the default for `acceptsHtml()`, `acceptsJson()` and `acceptsXml()`)* —
  `type/*` or an exact match satisfies; a bare `*/*` does not
- `AcceptSpecificity::Exact` — only a literal `type/subtype` match satisfies

`$specificity` is available on every method above: `accepts()`, `getPreferredType()`, `acceptsHtml()`,
`acceptsJson()`, and `acceptsXml()`.

[Top](#pop-http)

### Filters

As an extra layer of protection, you can add filters to the request object to filter incoming data:

```php
use Pop\Http\Server;
use Pop\Http\Server\Request;

$filters = ['strip_tags', 'addslashes'];
$server  = new Server(new Request(null, $filters));
```

And with the following curl command with data that contains tags and a single quote:

```bash
curl -i -X POST --data "foo=<script>bad's script</script>" "http://localhost:8000/post.php"
```

```php
if ($server->request->isPost()) {
    print_r($server->request->getPost());
}
```

the data will be filtered:

```text
Array
(
    [foo] => bad\'s script

)
```

[Top](#pop-http)

### Redirects & Forwards

You can redirect to another URL by calling the following method:

```php
use Pop\Http\Server\Response;

Response::redirect('http://www.newlocation.com/');
```

You can forward a client object's response as the server's response:

```php
use Pop\Http\Server\Response;

Response::forward($clientResponse);
```

[Top](#pop-http)

### Rendering Responses

Server responses can be rendered out to a raw string:

```php
use Pop\Http\Server;

$server = new Server();
$server->response->setCode(200)
    ->setMessage('OK')
    ->setVersion('1.1')
    ->addHeader('Content-Type', 'text/plain')
    ->setBody('This is the response');

echo $server;
```

Which would produce a string like this:

```text
HTTP/1.1 200 OK
Content-Type: text/plain

This is the response
```

[Top](#pop-http)

### Uploads

#### Basic file upload

```php
use Pop\Http\Server\Upload;

$upload = new Upload('/path/to/uploads');
$upload->setDefaults();

$upload->upload($_FILES['file_upload']);

// Do something with the newly uploaded file
if ($upload->isSuccess()) {
    $file = $upload->getUploadedFile();
} else {
    echo $upload->getErrorMessage();
}
```

The above code creates the upload object, sets the upload path and sets the basic defaults,
which includes a max file size of 10MBs, and an array of allowed common file types as well
as an array of common disallowed file types.

#### File upload names and overwrites

By default, the file upload object will not overwrite a file of the same name. In the above
example, if `$_FILES['file_upload']['name']` is set to 'my_document.docx' and that file
already exists in the upload path, it will be renamed to 'my_document_1.docx'.

If you want to enable file overwrites, you can simply do this:

```php
$upload->overwrite(true);
```

Also, you can give the file a direct name on upload like this:

```php
$upload->upload($_FILES['file_upload'], 'my-custom-filename.docx');
```

And if you need to check for a duplicate filename first, you can use the `checkFilename`
method. If the filename exists, it will append a '\_1' to the end of the filename, or loop
through until it finds a number that doesn't exist yet (\_#). If the filename doesn't
exist yet, it returns the original name.

```php
$filename = $upload->checkFilename('my-custom-filename.docx');

// $filename is set to 'my-custom-filename_1.docx'
$upload->upload($_FILES['file_upload'], $filename);
```

[Top](#pop-http)
