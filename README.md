# php-simple-cas-proxy

A very simple CAS proxy.
Pass the ticket to other service and proxy the validateService.

## Installation

```bash
composer require ganlvtech/php-simple-cas-proxy
```

## Usage

```php
<?php
use PhpSimpleCas\PhpCasProxy;

require './vendor/autoload.php';

$phpCasProxy = new PhpCasProxy('https://cas.xjtu.edu.cn/');
http_response_code($phpCasProxy->proxy());
```

## About the proxy server

You must login within 5 min, or cookie will be expired, and response may be 403.

## How to test

Add your service domain to `hosts`
```text
127.0.0.1 service.example.com
``` 

* You can't use this if you php-cgi is single thread.

## LICENSE

    The MIT License (MIT)
    
    Copyright (c) 2017 Ganlv
    
    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:
    
    The above copyright notice and this permission notice shall be included in all
    copies or substantial portions of the Software.
