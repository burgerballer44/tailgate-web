<?php

namespace TailgateWeb\Client;

use TailgateWeb\Client\ApiResponseInterface;

interface TailgateApiClientInterface
{
    public function get(string $path, array $queryStringArray = []) : ApiResponseInterface;
    public function post(string $path, array $data) : ApiResponseInterface;
    public function put(string $path, array $data) : ApiResponseInterface;
    public function patch(string $path, array $data) : ApiResponseInterface;
    public function delete(string $path) : ApiResponseInterface;
}