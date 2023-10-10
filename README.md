pop-http
========

[![Build Status](https://github.com/popphp/pop-http/workflows/phpunit/badge.svg)](https://github.com/popphp/pop-http/actions)
[![Coverage Status](http://cc.popphp.org/coverage.php?comp=pop-http)](http://cc.popphp.org/pop-http/)

* [Overview](#overview)
* [Install](#install)
* [Client](#client)
* [Promises](#promises)
* [Server](#server)
* [Uploads](#Uploads)

Overview
--------

`pop-http` is the main HTTP component for the Pop PHP Framework. It provides a robust
set of features to manage many aspects of HTTP connections. It provides functionality
for the following:

- **HTTP Client Transactions**
  - Create and manage outbound HTTP client requests and their responses
    - Full control over request headers and different request and response data types
    - Streams
    - Curl (Single & Multi)
    - Sync or Async Requests
    - Promises
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

[Top](#pop-http)

Promises
--------

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