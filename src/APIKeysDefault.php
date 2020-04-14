<?php

namespace RTNatePHP\Mouser;

/**
 * Default implementation of APIKeysInterface which uses
 * phpdotenv and retrieves API keys from the .env file.
 * 
 * Uses MOUSER_SEARCH_API_KEY for the search api and
 * uses MOUSER_ORDER_API_KEY for the orders api.
 */
class APIKeysDefault implements APIKeysInterface
{
    static public function search(): string
    {
        return getenv('MOUSER_SEARCH_API_KEY');
    }

    static public function orders():string
    {
        return getenv('MOUSER_ORDER_API_KEY');
    }
}