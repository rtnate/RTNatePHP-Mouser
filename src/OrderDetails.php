<?php

namespace RTNatePHP\Mouser;

class OrderDetails
{
    use Traits\ChecksResponseForErrors;
    /**
     * The Mouser API Version to use (v1)
     * @var string
     */
    static protected $apiVersion = 'v1';

    /**
     * The MouserRequest object
     * @var MouserRequest
     */
    protected $mouserRequest;

    /**
     * The Url for an OrderHistory request
     * @var string
     */
    protected $url;

     /**
     * Constructs a new OrderHistory object
     */
    public function __construct()
    {
        $this->url = MouserRoutes::get('order_details', static::$apiVersion);
        $this->mouserRequest = new MouserRequest($this->url, MouserKeys::orders());
    }

    /**
     * Gets the order details for the supplied order number.  Will return an empty array if not found
     * 
     * @param string|int the Mouser Web Order number
     */
    public function get($orderNumber)
    {
        try{
            $results = $this->getOrFail($orderNumber);
            return $results;
        }
        catch(\Throwable $e)
        {
            return [];
        }
    }

    /**
     * Gets the order details for the supplied order number.  Throws an exception on any erros.
     * 
     * @param string|int the Mouser Web Order number
     */
    public function getOrFail($orderNumber)
    {
        $orderNumberSafe = intval($orderNumber);
        if (!$orderNumberSafe) throw new MouserException("Order Number is Not Valid.");
        $url = $this->url . "/{$orderNumberSafe}";
        $this->mouserRequest->setUrl($url);
        try{
            $success = $this->mouserRequest->make();
            if ($success)
            {
                $results = $this->mouserRequest->getResponseData();
                $this->verifyResponse($results);
                return $results;
            }
        }
        catch(\Throwable $e)
        {
            $code = $e->getCode();
            if ($code == 404) throw new MouserException("Order Number {$orderNumber} is not a valid order number");
            if ($code == 401) throw new MouserException("Order Number {$orderNumber} is not one of ours.");
            else throw new MouserException("Error Fetching Order Details: {$e->getMessage()}", $code, $e);
        }
        return [];
    }

}