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
     *
     * @return int HTTP response code
     */
    public function proxy($service_validate_func = null, $proxyService = null, $serviceKey = 'service', $ticketKey = 'ticket')
    {
        switch (self::getPathinfo()) {
            case 'logout':
                session_start();
                unset($_SESSION['service']);
                session_write_close();
                $this->logout();
                break;
            case 'login':
                if (!isset($_GET[$serviceKey])) {
                    return 400;
                }
                if ($service_validate_func && !call_user_func($service_validate_func, $_GET[$serviceKey])) {
                    return 403;
                }
                session_start();
                $_SESSION['service'] = $_GET[$serviceKey];
                session_write_close();
                $this->login();
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
                session_start();
                if (!isset($_SESSION['service'])) {
                    return 403;
                }
                $service = $_SESSION['service'];
                unset($_SESSION['service']);
                session_write_close();
                self::redirect($service . '?' . http_build_query(array(
                        'ticket' => $_GET[$ticketKey],
                    )));
                break;
        }
    }

    public static function getPathinfo()
    {
        return preg_replace('@^/*@', '', $_SERVER['PATH_INFO']);
    }
}
