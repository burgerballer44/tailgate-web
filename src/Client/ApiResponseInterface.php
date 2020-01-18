<?php

namespace TailgateWeb\Client;

interface ApiResponseInterface
{
    public function getData();
    public function hasErrors();
}