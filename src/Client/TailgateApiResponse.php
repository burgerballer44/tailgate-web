<?php

namespace TailgateWeb\Client;

use TailgateWeb\Client\ApiResponseInterface;

class TailgateApiResponse implements ApiResponseInterface
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function hasErrors()
    {
        return !empty($this->data['errors']);
    }
}