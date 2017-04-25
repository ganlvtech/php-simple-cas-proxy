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
 * @package PhpSimpleCas
 */
class PhpCasProxy
{
    /**
     * PhpCas constructor.
     *
     * @param string|array $server CAS server url or array of [login_url, logout_url, service_validate_url]
     * @param callable|null $service_filter a function that has one parameter, url, that will be validated
     * @param string|null $my_service Current server url
     * @param string $my_cas_context CAS context, such as '/cas' (must start with slash, and must not end with slash)
     *                               empty string '' for root of the server
     */
    public function __construct($server, $service_filter = null, $my_service = null, $my_cas_context = '')
    {
        if (is_array($server)) {
            $this->server = $server;
        } elseif (is_string($server)) {
            $this->server = array(
                'login_url' => $server . 'login',
                'logout_url' => $server . 'logout',
                'service_validate_url' => $server . 'serviceValidate',
            );
        } else {
            throw new \RuntimeException('CAS server must be array or string');
        }
        if (!is_null($service_filter) && !is_callable($service_filter)) {
            throw new \RuntimeException('Service validate function must be callable');
        } else {
            $this->service_filter = $service_filter;
        }
        if (is_null($my_service)) {
            $this->my_service = self::getDefaultMyService();
        } elseif (is_string($my_service)) {
            $this->my_service = $my_service;
        } else {
            throw new \RuntimeException('My service must be a string');
        }
        $this->my_service = rtrim($this->my_service, '/') . '/';
        $this->my_cas_context = trim($my_cas_context, '/');
        $this->my_service = $this->my_service . (empty($this->my_cas_context) ? '' : ($this->my_cas_context . '/'));
    }

    /**
     * Validate if the proxied service is authenticated
     *
     * @return bool
     */
    protected function filterService()
    {
        if (empty($_GET['service'])) {
            return false;
        }
        if (is_null($this->service_filter)) {
            return true;
        }
        return (true == call_user_func($this->service_filter, $_GET['service']));
    }

    /**
     * Auto proxy the following several routes (according to $_SERVER['PATH_INFO'] and $_GET)
     * If matched, redirect. Otherwise, return false and nothing happened.
     *     /login?service=                    client -> proxy server
     *     /login?service=&gateway=           client -> proxy server
     *     /?service=&ticket=                 CAS server -> proxy server
     *     /?service=                         CAS server -> proxy server
     *     /serviceValidate?service=&ticket=  client -> proxy server
     *     /logout                            client -> proxy server
     *
     * @return int HTTP response code
     */
    public function proxy()
    {
        $path_info = trim(self::getPathInfo(), '/');
        if (!empty($this->my_cas_context)) {
            if (strpos($path_info, $this->my_cas_context) !== 0) {
                return false;
            }
            $path_info = substr($path_info, strlen($this->my_cas_context));
        }
        $path_info = trim($path_info, '/');
        switch ($path_info) {
            case 'logout':
                return $this->logout();
                break;
            case 'login':
                if (!$this->filterService()) {
                    return false;
                }
                return $this->login($_GET['service'], !isset($_GET['gateway']) ? false : $_GET['gateway']);
                break;
            case '':
                if (!$this->filterService()) {
                    return false;
                }
                return $this->back($_GET['service'], empty($_GET['ticket']) ? '' : $_GET['ticket']);
                break;
            case 'serviceValidate':
                if (!$this->filterService()) {
                    return false;
                }
                return $this->serviceValidate($_GET['service'], $_GET['ticket']);
                break;
        }
        return false;
    }

    /**
     * From proxied service to CAS server
     *
     * @param string $service
     * @param string|bool $gateway
     *
     * @return bool always true, but never reach.
     */
    public function login($service, $gateway = false)
    {
        $query = array(
            'service' => $this->my_service . '?' . http_build_query(array(
                    'service' => $service,
                )),
        );
        if ($gateway) {
            $query['gateway'] = $gateway;
        }
        self::http_redirect($this->server['login_url'] . '?' . http_build_query($query));
        // never reach
        return true;
    }

    /**
     * From CAS server back to proxied service
     *
     * @param string $service proxied service
     * @param string $ticket
     *
     * @return bool always true, but never reach.
     */
    public function back($service, $ticket = '')
    {
        $parts = parse_url($service);
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        } else {
            $query = array();
        }
        if (!empty($ticket)) {
            $query['ticket'] = $ticket;
        }
        $parts['query'] = http_build_query($query);
        self::http_redirect(self::build_url($parts));
        // never reach
        return true;
    }

    /**
     * Pass service validate http response
     *
     * @param string $service proxied service
     * @param string $ticket CAS ticket
     * @param int $timeout
     *
     * @return bool always true, but never reach.
     */
    public function serviceValidate($service, $ticket, $timeout = 10)
    {
        header('Content-Type: text/xml; charset=UTF-8');
        exit(self::file_get_contents($this->server['service_validate_url'] . '?' . http_build_query(array(
                'service' => $this->my_service . '?' . http_build_query(array(
                        'service' => $service,
                    )),
                'ticket' => $ticket,
            )), $timeout));
        // never reach
        return true;
    }

    /**
     * Redirect to CAS logout url
     *
     * @return bool always true, but never reach.
     */
    public function logout()
    {
        self::http_redirect($this->server['logout_url']);
        // never reach
        return true;
    }

    /**
     * Get url path info
     *
     * @return string
     */
    protected static function getPathInfo()
    {
        return $_SERVER['PATH_INFO'];
    }

    /**
     * output 302 redirect header and exit
     *
     * @param string $url url
     */
    protected static function http_redirect($url)
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Send GET request
     *
     * @param string $url URL
     * @param int $timeout Timeout(seconds)
     *
     * @return string Text response
     */
    protected static function file_get_contents($url, $timeout)
    {
        $opts = array(
            'http' => array(
                'timeout' => $timeout,
            ),
        );
        return file_get_contents($url, false, stream_context_create($opts));
    }

    /**
     * reverse parse_url
     *
     * @link http://stackoverflow.com/questions/4354904/php-parse-url-reverse-parsed-url/4355011#4355011
     *
     * @param array $parts
     *
     * @return string
     */
    protected static function build_url($parts)
    {
        return (!empty($parts['scheme']) ? "{$parts['scheme']}:" : '') .
            ((!empty($parts['user']) || !empty($parts['host'])) ? '//' : '') .
            (!empty($parts['user']) ? $parts['user'] : '') .
            (!empty($parts['pass']) ? ":{$parts['pass']}" : '') .
            (!empty($parts['user']) ? '@' : '') .
            (!empty($parts['host']) ? $parts['host'] : '') .
            (!empty($parts['port']) ? ":{$parts['port']}" : '') .
            (!empty($parts['path']) ? $parts['path'] : '') .
            (!empty($parts['query']) ? "?{$parts['query']}" : '') .
            (!empty($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }

    /**
     * Get the service name of this request
     *
     * @return string default service name
     */
    protected static function getDefaultMyService()
    {
        return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . '/';
    }
}
