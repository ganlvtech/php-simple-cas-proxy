<?php
use PhpSimpleCas\PhpCasProxy;

require '../vendor/autoload.php';

$phpCasProxy = new PhpCasProxy('https://cas.xjtu.edu.cn/');
http_response_code($phpCasProxy->proxy());
