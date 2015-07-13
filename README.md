pop-http
========

[![Build Status](https://travis-ci.org/popphp/pop-http.svg?branch=master)](https://travis-ci.org/popphp/pop-http)

OVERVIEW
--------
`pop-http` is the main HTTP component for the Pop PHP Framework. It provides the ability
to manage and parse request and response objects, as well as client transactions via
cURL and streams.

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

// Get the value of $_POST['id']
if ($request->isPost() {
    $id = $request->getPost('id');
}
```

### Using a base path

In this example, the application exists in a folder '/home'
and the URL is '/home/hello/world'

```php
$request = new Pop\Http\Request(null, '/home');

// /home
$basePath = $request->getBasePath();

// /hello/world
$uri = $request->getRequestUri();

// /home/hello/world
$fullUri = $request->getFullRequestUri();
```