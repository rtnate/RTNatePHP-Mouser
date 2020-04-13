<?php

namespace RT\Services;

use RT\Models\Production\Vendors\Vendor;
use RT\Models\Production\Vendors\VendorPart;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use RT\Exceptions\MouserException;
use Throwable;
use TypeError;

class MouserService{

    const MOUSER_URL = "https://api.mouser.com/api/v1";
    
    /**
     * The GuzzleHttp\Client instance 
     */
    protected  $client;

    protected $mouserVendorId;

    public function __construct()
    {
        $this->client = new GuzzleClient();
        $mouserVendor = Vendor::where('vendor', 'Mouser')->first();
        if ($mouserVendor) $this->mouserVendorId = $mouserVendor->id;
        else{
            $mouserVendor = Vendor::where('vendor', 'mouser')->first();
            if ($mouserVendor) $this->mouserVendorId = $mouserVendor->id;
            else $this->mouserVendorId = 0;
        }
    }

    /**
     * Populate a collection of VendorPart models with their mouser part data
     * This function will only populate 300 vendor parts at one time
     * 
     * @param Collection $vendorParts - A Collection of VendorPart models to populate
     */
    public function populateVendorParts(Collection $vendorParts)
    {
        //Only search Mouser parts
        $vendorParts = $vendorParts->where('vendor_id', $this->mouserVendorId);
        $searchParts = $vendorParts;
        //Limit Search to 300 Parts 
        if ($vendorParts->count() >= 300)
        {
            $searchParts = $vendorParts->slice(0, 300);
        }
        //Chunk the parts into groups of 10 to make the search
        $partsChunked = $searchParts->chunk(10);

        $success = true;
        foreach ($partsChunked as $chunk) {
            $wasSuccesful = $this->populateVendorPartsChunk($chunk);
            if (!$wasSuccesful) $success = false;
        }
        return $success;
    }

    private function populateVendorPartsChunk(Collection $vendorParts)
    {
        $part_numbers = $vendorParts->pluck('vendor_part_no');
        $searchResult = $this->getParts($part_numbers->toArray());
        if ($searchResult['errors']){
            //TODO: Handle and Error Here
            throw new Exception("Mouser Returned Error: ".var_dump($searchResult['errors']));
        }
        else{
            $mouserParts = $searchResult['parts'];
            $this->matchMouserPartsToVendorParts($vendorParts, $mouserParts);
        }
    }

    private function matchMouserPartsToVendorParts(Collection $vendorParts, Collection $mouserParts)
    {
        $success = true;
        $mouserParts->map( function ($part) use ($vendorParts)
        {
            $mouserPartNo = $part['MouserPartNumber'];
            $vendorPart = $vendorParts->where('vendor_part_no', $mouserPartNo)->first();
            if ($vendorPart) $vendorPart->mouser_info = $part;
            else $success = false;
        });
        return $success;
    }

    public function getParts(Array $part_numbers)
    {
        $output = [];
        $output['input_parts'] = $part_numbers;
        //Trim any whitespace in part numbers to prepare for imploding
        $part_numbers = array_map('trim', $part_numbers);
        //Maximum Number Of Parts per Search is 10
        if (count($part_numbers) > 10)
        {
            $parts_to_search = array_slice($part_numbers, 0, 10, true);
        } else $parts_to_search = $part_numbers;
        $output['parts_searched'] = $parts_to_search;
        
        $search_list = implode("|", $parts_to_search);

        $results = $this->executeSearch($search_list);
        $errors = $results['Errors'];
        $mouserParts = [];
        if (!$errors) $mouserParts = $results['SearchResults']['Parts'];
        $output['errors'] = $errors;
        $output['parts'] = collect($mouserParts);
        return $output;
    }

    /**
     * Executes the Mouser Parts search, returning the parsed result
     * 
     *  @param string $partSearch  The part number search string
     *      - Multiple part numbers should be separated by a pipe ("|")
     *      - Limit 10 different part numbers 
     * 
     *  @param string $partSearchOptions (optional) Additional part search options
     *      - If not provided, the default is None. Refers to options supported by the search engine. 
     *      - Only one value at a time is supported.
     *      - The following values are valid: None | Exact | BeginsWith
     *      - Can use string representations or integer IDs: 1[None] | 2[Exact] | 3[BeginsWith]
     * 
     *  @return array
     */
    public function executeSearch(string $partSearch, string $partSearchOptions = "")
    {
        //Prepare the Search URL
        $URI = self::MOUSER_URL."/search/";
        $API_KEY = env('MOUSER_API_KEY', '');
        $url = "{$URI}partnumber?apiKey={$API_KEY}";
        //Prepare the Search Body
        $search = [
            "SearchByPartRequest" => [
                "mouserPartNumber" => $partSearch,
                "partSearchOptions"=> $partSearchOptions
            ]
            ];
        $body = json_encode($search);
        //Execute The Search
        $response = $this->makeRequest('POST', $url, $body);
        //Decode the resonse body from JSON
        $resBody = $response->getBody()->getContents();
        $data = json_decode($resBody, true);
        return $data;
    }

    /**
     *  Outputs Our Orders from the Specified Date Range
     * 
     *  @param  string|DateTime $startDate (optional) - The beginning of the date range
     *      If not provided, will default to 30 days before ow
     *  @param string|DateTime $endDate (optional) - The end of the date range
     *      If not provided will default to now
     *  @return An Array of the past orders
     */
    public function listOrders($startDate = null, $endDate = null)
    {
        //Prepare End Date
        if($endDate == null)
        {
            $endDate = Date::now();
        }
        else if (is_string($startDate))
        {
            $endDate = Date::createFromFormat('n/d/Y', $endDate);
        }
        //Prepare Start Date
        if($startDate == null)
        {
            $startDate = clone $endDate;
            $startDate->modify('-30 days');
        }
        else if (is_string($startDate))
        {
            $startDate = Date::createFromFormat('n/d/Y', $startDate);
        }

        $startDateFormatted = $startDate->format('n/d/Y');
        $endDateFormatted = $endDate->format('n/d/Y');
        //Prepare the Search URL
        $URI = self::MOUSER_URL."/orderhistory/ByDateRange";
        $API_KEY = $this->getOrderApiKey();
        $url = "{$URI}?apiKey={$API_KEY}";
        $url .= "&startDate={$startDateFormatted}";
        $url .= "&endDate={$endDateFormatted}";
        $body = '';
        //Execute The Search
        $response = $this->makeRequest('GET', $url, $body);
        //Decode the resonse body from JSON
        $resBody = $response->getBody()->getContents();
        $data = json_decode($resBody, true);
        return $data;
    }


    public function getOrderDetails($orderNumber)
    {
        if (!is_long($orderNumber)) throw new TypeError("Order Number should by a Long Integer");
        //Prepare the Search URL
        $URI = self::MOUSER_URL."/order/";
        $API_KEY = $this->getOrderApiKey();
        $url = "{$URI}/{$orderNumber}?apiKey={$API_KEY}";
        $body = '';
        //Execute The Search
        $response = $this->makeRequest('GET', $url, $body);
        //Decode the resonse body from JSON
        $resBody = $response->getBody()->getContents();
        $data = json_decode($resBody, true);
        return $data;
    }

    /**
     *  Makes the HTTP request using the GuzzleClient returning the response
     * 
     *  @param string $method - The request method ('GET', 'POST', etc)
     *  @param string $url - The request url
     *  @param string $body - The request body, stringified
     *  @param array|null $headers (optional) - Set any additional headers or override defaults
     *  @return ResponseInterface 
     *   
     *  Default headers are accept 'application/json' and Content-Type 'application/json'
     */
    private function makeRequest($method, $url, $body, $headers = null)
    {
        
        $defaultHeaders = ['accept' => 'application/json',
                    'Content-type' => 'application/json'];
        //If headers is set, and an array, merge the new headers with the default headers            
        if ($headers != null)
        {
            if (is_array($headers))
            {
                $sendHeaders = array_merge($defaultHeaders, $headers);
            }
            else throw new TypeError('Input paramater $headers must be an array or null');
        } else $sendHeaders = $defaultHeaders;

        $response = '';
        try{
            $response = $this->client->request($method, $url, ['body' => $body, 'headers' => $sendHeaders]);
        }
        catch(Throwable $e)
        {
            $message = "Mouser Unreachable: ".$e->getMessage();
            throw new MouserException($message, $e->getCode(), $e);
        }
        //Send the request and return the response
        return $response;
    }

    protected function verifyResponse($response)
    {
        $errors = [];
        $data = [];
        if (!$response)
        {
            throw new MouserException('Error: Mouser Response Was Empty');
        }
        try{
            $resBody = $response->getBody()->getContents();
            $data = json_decode($resBody, true);
            $errors = $data['Errors'];
        }
        catch(Throwable $e)
        {
            throw new MouserException("Error: Mouser Response Was Empty or Ill Formed.", $e->getCode(), $e);
        }
        if ($errors)
        {
            $errorList = implode(", ", $errors);
            throw new MouserException("Mouser Returned the follow Errors: {$errorList}"); 
        }
        return $data;
    }

    protected function getSearchApiKey()
    {
        return env('MOUSER_API_KEY', '');
    }

    protected function getOrderApiKey()
    {
        return env('MOUSER_ORDER_API_KEY', '');
    }

    public function quack()
    {
        echo 'QAUCK!';
    }
}