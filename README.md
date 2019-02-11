pop-http
========

[![Build Status](https://travis-ci.org/popphp/pop-http.svg?branch=master)](https://travis-ci.org/popphp/pop-http)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-http)](http://cc.popphp.org/pop-http/)

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
if ($request->getSegment(0) == 'hello') { } // Returns true
if ($request->getSegment(1) == 'world') { } // Returns true
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
$config = [
    'code'    => 200,
    'headers' => [
        'Content-Type' => 'text/plain'
    ]
];

$response = new Pop\Http\Response($config);
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
$response = Pop\Http\Response\Parser::parseFromUri('http://www.mydomain.com/');

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
$client = new Pop\Http\Client\Curl('http://www.mydomain.com/', 'POST');
$client->setReturnHeader(true)
       ->setReturnTransfer(true);

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
$client = new Pop\Http\Client\Stream('http://www.mydomain.com/', 'POST');

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
### File uploads

##### Basic file upload

```php
use Pop\Http\Upload;

$upload = new Upload('/path/to/uploads');
$upload->useDefaults();

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
