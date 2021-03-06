<?php

namespace RTNatePHP\Mouser;

class MouserRoutes{

    const API = "https://api.mouser.com/api";
    const HOME = "https://www.mouser.com";

    public static $routes = [
        'part_search' => '/search/partnumber',
        'order_history' => '/orderhistory/ByDateRange',
        'order_details' => '/order',
    ];

    protected static function makeAPIUrl($route, $apiVersion)
    {
        return self::API."/{$apiVersion}".$route;
    }

    public static function get($name = '', $apiVersion = "v1")
    {
        if(array_key_exists($name, self::$routes))
        {
            $route = self::$routes[$name];
            return self::makeAPIUrl($route, $apiVersion);
        }
        else return self::HOME;
    }
}