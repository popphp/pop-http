pop-http
========

[![Build Status](https://travis-ci.org/popphp/pop-http.svg?branch=master)](https://travis-ci.org/popphp/pop-http)
[![Coverage Status](http://www.popphp.org/cc/coverage.php?comp=pop-http)](http://www.popphp.org/cc/pop-http/)

OVERVIEW
--------
`pop-http` is the main HTTP component for the Pop PHP Framework. It provides the ability
to manage and parse request and response objects. It also provides support for HTTP
client transactions via cURL and streams.

`pop-http` is a component of the [Pop PHP Framework](http://www.popphp.org/).

INSTALL
-------

Install `pop-http` using Composer.

    composer require popphp/pop-http

BASIC USAGE
-----------

### The request object, GET example

Let's use a GET request with the URL '/hello/world?var=123'

```php
$request = new Pop\Http\Request();

// /hello/world
$uri = $request->getRequestUri();

// 123
$var = $request->getQuery('var');
```

The request object also allows you to trace down the different segments
of the request URI like this:

```php
if ($request->getPath(0) == 'hello') { } // Returns true
if ($request->getPath(1) == 'world') { } // Returns true
```

### The request object, POST example

Let's use a POST request with the URL '/users/edit'
 
```php
$request = new Pop\Http\Request();

// /users/edit
$uri = $request->getRequestUri();

// Get the value of $_POST['id']
if ($request->isPost()) {
    $id = $request->getPost('id');
}
```

### Using a base path

In this example, the application exists in a folder '/home'
and the full URL is '/home/hello/world'

```php
$request = new Pop\Http\Request(null, '/home');

// /home
$basePath = $request->getBasePath();

// /hello/world
$uri = $request->getRequestUri();

// /home/hello/world
$fullUri = $request->getFullRequestUri();
```

### Creating a response object

```php
$options = [
    'code'   => 200,
    'headers => [
        'Content-Type' => 'text/plain'
    ]
];

$response = new Pop\Http\Response($options);
$response->setBody('This is a plain text file.');

$response->send();
```

The above script will output something like this when requested:

    HTTP/1.1 200 OK
    Content-Type: text/html
    
    This is a plain text file.

### Simple response redirect

```php
Pop\Http\Response::redirect('http://www.newlocation.com/');
```

### Parsing a response

```php
$response = Pop\Http\Response::parse('http://www.mydomain.com/');

if ($response->isSuccess()) { } // Returns true
if ($response->isError())   { } // Returns false

// 200
echo $response->getCode();

// OK
echo $response->getMessage();

// text/html
echo $response->getHeader('Content-Type');

// Display the body of the response
echo $response->getBody();
```

### Using the cURL client

```php
$client = new Pop\Http\Client\Curl('http://www.mydomain.com/');
$client->setReturnHeader(true)
       ->setReturnTransfer(true)
       ->setPost(true);

$client->setFields([
    'id'    => 1001,
    'name'  => 'Test Person',
    'email' => 'test@test.com' 
]);

$client->send();

// 200
echo $client->getCode();

// Display the body of the returned response
echo $client->getBody();
```

### Using the Stream client

```php
$client = new Pop\Http\Client\Stream('http://www.mydomain.com/');
$client->setPost(true);

$client->setFields([
    'id'    => 1001,
    'name'  => 'Test Person',
    'email' => 'test@test.com' 
]);

$client->send();

// 200
echo $client->getCode();

// Display the body of the returned response
echo $client->getBody();
```
