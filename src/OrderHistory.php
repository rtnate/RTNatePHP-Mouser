<?php

namespace RTNatePHP\Mouser;

use DateTime;

/**
 * Class for managing and retreiving a Mouser Order History
 */
class OrderHistory
{
    use Traits\ChecksResponseForErrors;
    /**
     * The Mouser API Version to use (v1)
     */
    static protected $apiVersion = 'v1';

    /**
     * The MouserRequest object
     */
    protected $mouserRequest;

    /**
     * The Url for an OrderHistory request
     */
    protected $url;

    /**
     * DateTime object representing the beginning of the search range.
     * This defaults to 30 days in the past
     */
    protected $startDate;

    /**
     * DateTime object representing the end of the search range.
     * This defaults to now.
     */
    protected $endDate;

    /**
     * Constructs a new OrderHistory object
     */
    public function __construct()
    {
        $this->url = MouserRoutes::get('order_history', static::$apiVersion);
        $this->mouserRequest = new MouserRequest($this->url, MouserKeys::orders());
        $this->endDate = new DateTime();
        $start = clone $this->endDate;
        $this->startDate = $start->modify('-30 days');
    }

    public function setStartDate($startDate, $format = 'n/d/Y')
    {
        if ($startDate instanceof DateTime)
        {
            $this->startDate = $startDate;
        }
        else if (is_string($startDate))
        {
            $this->startDate = DateTime::createFromFormat($format, $startDate);
        }
    }

    public function setEndDate($endDate, $format = 'n/d/Y')
    {
        if ($endDate instanceof DateTime)
        {
            $this->endDate = $endDate;
        }
        else if (is_string($endDate))
        {
            $this->endDate = DateTime::createFromFormat($format, $endDate);
        }
    }

    public function setDateRange($startDate, $endDate, $format)
    {
        $this->setStartDate($startDate, $format);
        $this->setEndDate($endDate, $format);
    }

    /**
     * Sets the amount of days before the endDate to search
     * Defaults to 30 if $noDays is ill formed.
     * 
     * @param $noDays - Number of days to search
     */
    public function setNumberOfDays($noDays)
    {
        $noDays = intval($noDays);
        if (!$noDays) $noDays = 30;
        $start = clone $this->endDate;
        $this->startDate = $start->modify("-{$noDays} days");

    }

    public function get()
    {
        $startDateFormatted = $this->startDate->format('n/d/Y');
        $endDateFormatted = $this->endDate->format('n/d/Y');
        $this->mouserRequest->setQueryParam('startDate', $startDateFormatted);
        $this->mouserRequest->setQueryParam('endDate', $endDateFormatted);
        $success = $this->mouserRequest->make();
        if ($success)
        {
            $results = $this->mouserRequest->getResponseData();
            $this->verifyResponse($results);
            return $results['OrderHistoryItems'];
        }
    }
}