pop-http
========

[![Build Status](https://github.com/popphp/pop-http/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-http/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-http)](http://cc.popphp.org/pop-http/)

* [Overview](#overview)
* [Install](#install)
* [Client](#client)
  - [Quickstart](#quickstart)
  - [Auth](#auth)
  - [Options](#options)
  - [Requests](#requests)
  - [Responses](#responses)
  - [Handlers](#handlers)
* [Promises](#promises)
  - [Wait](#wait)
  - [Then](#then)
  - [Forwarding](#forwarding)
  - [Nesting](#nesting)
* [CLI Conversion](#cli-conversions)
* [Server](#server)
* [Uploads](#Uploads)

Overview
--------

`pop-http` is the main HTTP component for the Pop PHP Framework. It provides a robust
set of features to manage the many aspects of HTTP connections. It provides functionality
for the following:

- **HTTP Client Transactions**
  - Create and manage outbound HTTP client requests and their responses
    - Full control over request & response headers
    - Manage authorization
    - Manage and parse different request & response data types
    - Use the request handler of your choice: curl, stream or curl-multi (defaults to curl)
    - Send sync or async requests
    - Support for promises
    - Client to Curl CLI command conversions
- **HTTP Server Transactions**
  - Manage inbound HTTP server requests, headers and data
  - Easily handle file uploads and apply server-side settings and restrictions

`pop-http` is a component of the [Pop PHP Framework](http://www.popphp.org/).

Install
-------

Install `pop-http` using Composer.

    composer require popphp/pop-http

Or, require it in your composer.json file

    "require": {
        "popphp/pop-http" : "5.0.*"
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

$response = Client::post('http://localhost/auth', Auth::createKey('MY_API_KEY')));
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

### Options

The client object supports an `$options` array to pass in general configuration details and data for the request.
Supported keys in the options array are:

- `base_uri` - the base URI for re-submitting many requests with the same client to different endpoints on the same domain
- `method` - the request method (GET, POST, PUT, PATCH, DELETE, etc.)
- `headers` - an array of request headers
- `user_agent` - the user agent string
- `data` - an array of request data
- `files` - an array of files on disk to be sent with the request
- `type` - set the request type (URL-form, JSON, XML or multipart/form)
  + `Request::URLFORM` (`application/x-www-form-urlencoded`)
  + `Request::JSON` (`application/json`)
  + `Request::XML` (`application/xml`)
  + `Request::MULTIPART` (`multipart/form-data`)
- `auto` - trigger automatic content negotiation and return the parsed content, if possible (boolean)
- `async` - trigger an asynchronous request (boolean)
- `verify_peer` - enforce or disallow verifying the host for SSL connections (boolean)
- `allow_self_signed` - allow or disallow the use of self-signed certificates for SSL connections (boolean)
- `force_custom_method` - for Curl only. Forces the use of `CURLOPT_CUSTOMREQUEST` (boolean)

Here is an example using a `base_uri`:

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$client    = new Client(['base_uri' => 'http://localhost']);
$response1 = $client->get('/page1'); // Will request http://localhost/page1
$response2 = $client->get('/page2'); // Will request http://localhost/page2
$response2 = $client->get('/page3'); // Will request http://localhost/page3
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

**Automatic Content Negotiation**

In the above examples, the `$response` returned is a full response object. If you want to get the actual response
content, as mentioned above, you would call:

```php
$content = $response->getParsedResponse();
```

If you would like to skip this step and have the client attempt content negotiation automatically and return the
parsed content, you can set the `auto` option to true.

```php
use Pop\Http\Client;
use Pop\Http\Client\Request;

$client = new Client('http://localhost/post', [
    'data'   => [
        'foo' => 'bar',
        'baz' => 123
    ],
    'type' => Request::JSON // "application/json"
    'auto' => true
]);

$response = $client->post();
```

If the server in the above example returns a JSON payload, the response will now be a PHP array representation of
that JSON data (instead of a full response object.) If you still need to access the full response object, you can
do so by calling:

```php
$clientResponse = $client->getResponse();
```

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
$response = $cleint->send();
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
var_dump($response->getHeaders());              // An array of HTTP header objects
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

To determine if the return response was a success or an error, the following methods can be used:

```php
var_dump($response->isSuccess());     // Boolean on 100-, 200- or 300-level responses
var_dump($response->isError());       // Boolean on 400- or 500-level responses
var_dump($response->isContinue());    // Boolean on 100-level response
var_dump($response->isOk());          // Boolean on 200-level response
var_dump($response->isRedirect());    // Boolean on 300-level response
var_dump($response->isClientError()); // Boolean on 400-level response
var_dump($response->isServerError()); // Boolean on 500-level response
```

### Handlers

You can choose to use a different handler with the client object. The available handlers are:

- `Pop\Http\Client\Handler\Curl` - uses the PHP curl extension (default)
- `Pop\Http\Client\Handler\Stream` - uses PHP stream
- `Pop\Http\Client\Handler\CurlMulti` - reserved for multiple parallel/concurrent requests at the same time

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
$client->getHandler()->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
```

##### Curl

The handlers allow you to further customize the request by interfacing with each respective handler's settings.
For `Curl`, that mainly includes setting additional Curl options needed for the request. (**Please Note:** Many
of the required Curl options, such as `CURLOPT_POST`, `CURLOPT_URL` and `CURLOPT_HTTPHEADER` are automatically
set based on the initial configuration of the client and request objects.) 

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

##### Stream

For `Stream`, that includes setting context options and parameters needed for the request. (**Please Note:**
Many of the required Stream context options, such as `http`, `http[method]` and `http[header]` are automatically
set based on the initial configuration of the client and request objects.)

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

##### Curl Multi-Handler

The Curl Multi-Handler is a special use-case handler that allows for multiple parallel/concurrent requests
to be made at the same time. Each request will get its own `Client` object, which will be registered with
the multi-handler object.

```php
use Pop\Http\Client;
use Pop\Http\Client\Handler\CurlMulti;

$multiHandler = new CurlMulti();
$client1      = new Client('http://localhost/test1.php', $multiHandler);
$client2      = new Client('http://localhost/test2.php', $multiHandler);
$client3      = new Client('http://localhost/test3.php', $multiHandler);

$running = null;

do {
    $multiHandler->send($running);
} while ($running);

$responses = $multiHandler->getAllResponses();
```

The `$multiHandler->getAllResponses()` method will return an array of all of the response objects returned
from each of the requests.

A more abbreviated way to initialize a multi-handler would be:

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

[Top](#pop-http)

Promises
--------

Promises allow you to stage asynchronous requests and call them at a later time in the application. When you
initialize a client object and call it asynchronously, it will return a promise object. There are few different
ways to achieve this:

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
})->catch(function(Response $response)) {
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

[Top](#pop-http)

Uploads
-------

##### Basic file upload

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

##### File upload names and overwrites

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