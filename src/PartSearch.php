<?php

namespace RTNatePHP\Mouser;

class PartSearch
{
    use Traits\ChecksResponseForErrors;
    const MAX_PARTS_PER_SEARCH = 10;
    const MAX_PARTS_PER_REQUEST_TIME = 300;

    static protected $apiVersion = 'v1';

    protected $mouserRequest;
    protected $url;
    protected $parts = [];
    protected $chunks = [];
    protected $chunksLeft = 0;

    public function __construct()
    {
        $this->url = MouserRoutes::get('part_search', static::$apiVersion);
        $this->mouserRequest = new MouserRequest($this->url);
    }

    /**
     * Get the array of part numbers to be searched
     * 
     * @return array
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Returns the number of parts to be searched
     * 
     * @return int
     */
    public function noParts()
    {
        return count($this->parts);
    }

    /**
     * Returns the maximum number of parts that a PartSearch can process
     * 
     * @return int
     */
    public function maxNoParts()
    {
        return self::MAX_PARTS_PER_REQUEST_TIME;
    }

    /**
     * Returns the number of parts that can still be added to the search
     * 
     * @return int
     */
    public function partsLeft()
    {
        return self::MAX_PARTS_PER_REQUEST_TIME - $this->noParts();
    }


    private function getArrayOfPartsToAdd($parts)
    {
        $incoming = count($parts);
        $canAdd = $this->partsLeft();
        if ($incoming > $canAdd) return array_slice($parts, 0, $canAdd);
        else return $parts;
    }

    /**
     * Add part(s) to the search.  Takes either an array of part numbers as strings,
     * or a string list of part numbers separated by a pipe ("|")
     * 
     * @param array|string $parts - The parts to add
     * @return int - The number of parts added
     */
    public function addParts($parts)
    {
        $count = $this->noParts();
        if (is_array($parts))
        {
            $partsToAdd = $this->getArrayOfPartsToAdd($parts);
        }
        else if (is_string($parts))
        {
            $partsArray = explode("|", $parts);
            $partsToAdd = $this->getArrayOfPartsToAdd($partsArray);
        }
        $this->parts = array_merge($this->parts, $partsToAdd);
        return count($partsToAdd);
    }

    protected function chunkParts()
    {
        return array_chunk($this->parts, self::MAX_PARTS_PER_SEARCH);
    }

    protected function prepareBody(string $partsToSearch, string $searchOptions = '')
    {
        $search = [
            "SearchByPartRequest" => [
                "mouserPartNumber" => $partsToSearch,
                "partSearchOptions"=> $searchOptions
            ]
        ];
        return json_encode($search);
    }

    protected function searchChunk(array $chunk)
    {
        $partSearch = implode("|", $chunk); 
        $body = $this->prepareBody($partSearch);
        $success = $this->mouserRequest->make($body);
        if ($success)
        {
            $results = $this->mouserRequest->getResponseData();
            $this->verifyResponse($results);
            return $results['Parts'];
        }
        else return [];
    }

    public function execute()
    {
        $this->chunks = $this->chunkParts();
        $this->chunksLeft = count($this->chunks);
        //Prepare the Search Body
        $results = [];
        $i = 0;
        while($this->chunksLeft > 0)
        {
            $searchChunk = $this->chunk[$i];
            $res = $this->searchChunk($searchChunk);
            array_push($res);
        }
        $body = json_encode($search);
        //Execute The Search
        $response = $this->makeRequest('POST', $url, $body);
        //Decode the resonse body from JSON
        $resBody = $response->getBody()->getContents();
        $data = json_decode($resBody, true);
        return $data;
    }
}