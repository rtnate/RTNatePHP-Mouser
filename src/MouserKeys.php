<?php

namespace RTNatePHP\Mouser;

/**
 * Class which is called statically to retrieve Mouser api keys.
 * 
 * Default implementation: @see APIKeysDefault.
 * Use setClass() to programmatically override the default implementation to use your class.
 */
class MouserKeys implements APIKeysInterface
{
    /**
     * The class that provides the API Keys
     */
    static protected $keysClass = APIKeysDefault::class;

    /**
     * Programmatically set the class which provides the API Keys
     * 
     * @param string $newKeysClass A class name which implements APIKeysInterface
     */
    static public function setClass(string $newKeysClass)
    {
        if (is_subclass_of(APIKeysInterface::class, $newKeysClass))
        {
            self::$keysClass = $newKeysClass;
        }
    }

    static public function search(): string
    {
        return self::$keysClass::search();
    }

    static public function orders():string
    {
        return self::$keysClass::orders();
    }


}