<?php
/**
 * A very simple CAS proxy.
 * Pass the ticket to other service and proxy the validateService.
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2017 Ganlv
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 */

namespace PhpSimpleCas;

/**
 * Class PhpCasProxy
 * A very simple CAS proxy.
 * Pass the ticket to other service and proxy the validateService.
 *
 * @package PhpCas
 */
class PhpCasProxy extends PhpCas
{
    /**
     * @param callable|null $service_validate_func
     * @param string|null $proxyService
     * @param string $serviceKey
     * @param string $ticketKey
     * @param string $cookieName
     *
     * @return int HTTP response code
     */
    public function proxy($service_validate_func = null, $proxyService = null, $serviceKey = 'service', $ticketKey = 'ticket', $cookieName = 'service')
    {
        switch (self::getPathinfo()) {
            case 'logout':
                self::removeCookie($cookieName);
                $this->logout();
                break;
            case 'login':
                if (!isset($_GET[$serviceKey])) {
                    return 400;
                }
                if ($service_validate_func && !call_user_func($service_validate_func, $_GET[$serviceKey])) {
                    return 403;
                }
                setcookie($cookieName, $_GET[$serviceKey], time() + 300); // available in five minutes
                $this->login($proxyService);
                break;
            case 'serviceValidate':
                if (!isset($_GET[$serviceKey]) || !isset($_GET[$ticketKey])) {
                    return 400;
                }
                if ($service_validate_func && !call_user_func($service_validate_func, $_GET[$serviceKey])) {
                    return 403;
                }
                $this->getUser($proxyService, $_GET[$ticketKey]);
                echo $this->getTextResponse();
                return 200;
                break;
            default:
                if (!isset($_GET[$ticketKey])) {
                    return 404;
                }
                if (!isset($_COOKIE[$cookieName])) {
                    return 403;
                }
                $service = $_COOKIE[$cookieName];
                if ($service_validate_func && !call_user_func($service_validate_func, $service)) {
                    return 403;
                }
                self::removeCookie($cookieName);
                self::redirect($service . '?' . http_build_query(array(
                        $ticketKey => $_GET[$ticketKey],
                    )));
                break;
        }
    }

    public static function removeCookie($name)
    {
        setcookie($name, null, 1);
    }

    public static function getPathinfo()
    {
        return preg_replace('@^/*@', '', $_SERVER['PATH_INFO']);
    }
}
