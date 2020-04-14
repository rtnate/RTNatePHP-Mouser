<?php

namespace RTNatePHP\Mouser;

use GuzzleHttp\Client as Client;
use Psr\Http\Message\ResponseInterface;
use RTNatePHP\HTTP\RequestManager;

/**
 *  Extension of RequestManager for making HTTP Requests to Mouser
 */
class MouserRequest extends RequestManager{
    
    protected $headers =    ['accept' => 'application/json',
                            'Content-type' => 'application/json'];

    protected $reqMethod = 'GET';

    /**
     * Constructs a new MouserRequest Object
     * 
     * @param string $url - The request url
     * @param string $apiKey - The Mouser API key to use with the request
     * @param Client|null $client - The Client object to use for the request.
     *                              If not supplied one will be created automatically.
     */
    public function __construct($url = '', $apiKey = '', $client = null)
    {
        $this->apiKey = $apiKey;
        parent::__construct($url, $client);
    }

    /**
     * Sets the API Key for this request
     * 
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    protected function getQueryString()
    {
        //Add the apiKey to the query params before stringifying
        $this->query['apiKey'] = $this->apiKey;
        return parent::getQueryString();
    }

    /**
     * @throws MouserException on Missing API Key or request failure
     */
    public function make($body = '')
    {
        if (!$this->apiKey)
        {
            throw new MouserException('Error: API Key Not supplied');
        }
        try{
            return parent::make();
        }
        catch(\Throwable $e)
        {
            $message = "Mouser Unreachable: ".$e->getMessage();
            throw new MouserException($message, $e->getCode(), $e);
        }
        return false;
    }

    /**
     * Retrieves the Mouser response data
     * 
     * @return array An associative array with the response
     * @throws MouserException on invalid data
     */
    public function getResponseData()
    {
        try{
            if ($this->response instanceof ResponseInterface)
            {
                $body = $this->response->getBody()->getContents();
                $data = json_decode($body, true);
                return $data;
            }
            else return [];
        }
        catch(\Throwable $e)
        {
            $message = "Unable To Read Mouser Response: ".$e->getMessage();
            throw new MouserException($message, $e->getCode(), $e);
        }
    }

}