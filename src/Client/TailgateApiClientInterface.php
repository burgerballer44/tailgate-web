<?php

namespace TailgateWeb\Client;

interface TailgateApiClientInterface
{
    public function get(string $path, array $queryStringArray = []);
    public function post(string $path, array $data);
    public function put(string $path, array $data);
    public function patch(string $path, array $data);
    public function delete(string $path);
}