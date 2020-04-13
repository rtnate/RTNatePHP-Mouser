<?php

namespace RTNatePHP\Mouser;

class MouserKeys
{
    static public function search()
    {
        return env('MOUSER_SEARCH_API_KEY', '');
    }

    static public function orders()
    {
        return env('MOUSER_ORDER_API_KEY', '');
    }

}