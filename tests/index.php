<?php
use PhpSimpleCas\PhpCasProxy;

require '../vendor/autoload.php';

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

?><a href="https://github.com/ganlvtech/php-simple-cas-proxy">Fork me on GitHub</a>
