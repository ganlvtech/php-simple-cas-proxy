# php-simple-cas-proxy

A very simple CAS proxy.
Pass the ticket to other service and proxy the validateService.

## Installation

```bash
composer require ganlvtech/php-simple-cas-proxy
```

## Usage

### Proxy server

```php
<?php
use PhpSimpleCas\PhpCasProxy;

require './vendor/autoload.php';

function service_filter($service_name)
{
    return 0 === strpos($service_name, 'http://cas_client.dev/');
}

$phpCasProxy = new PhpCasProxy('https://cas.dev/');
// $phpCasProxy = new PhpCasProxy('https://cas.dev/', 'service_filter');
// $phpCasProxy = new PhpCasProxy('https://cas.dev/', null, 'http://my_cas.dev/');
// $phpCasProxy = new PhpCasProxy('https://cas.dev/', 'service_filter', 'http://my_cas.dev/', '/cas');
$phpCasProxy->proxy();

// if runs to here, all CAS routes match failed.
```

### Client

`jasig/phpcas` partly compatible. Only `logout`, `forceAuthentication`, `checkAuthentication`, `getUser` available.

```bash
composer require jasig/phpcas
```

```php
<?php
require './vendor/autoload.php';
phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context);
phpCAS::setNoCasServerValidation();
if (isset($_REQUEST['logout'])) {
    phpCAS::logout();
}
if (isset($_REQUEST['login'])) {
    phpCAS::forceAuthentication();
}
$auth = phpCAS::checkAuthentication();
if ($auth) {
    echo phpCAS::getUser();
} else {
    echo 'Guest mode';
}
```

## About the proxy server

1. You must login within 5 min, or cookie will be expired, and response may be 403.

2. Proxy server must use SSL.

3. Server name and service name may be different.
    ```php
    $phpCasProxy = new PhpCasProxy('https://cas.dev/', null, 'http://my_cas.dev/');
    $phpCasProxy->proxy();
    ```
    must under `http://my_cas.dev/` and you can also add a domain `https://cas.mydomain.com/` resolved to the same IP.

## How to test

Add your service domain to `hosts`
```text
127.0.0.1 service.example.com
``` 

* You can't use this if you php-cgi is single thread (Try php-fpm or Nginx).

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
