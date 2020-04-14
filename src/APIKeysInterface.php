<?php

namespace RTNatePHP\Mouser;

/**
 * Static interface for retrieving Mouser api keys
 */
interface APIKeysInterface
{
    /**
     * Returns the search api key
     * @return string
     */
    static public function search(): string;

    /**
     * Returns the order api key
     * @return string
     */
    static public function orders(): string;
}