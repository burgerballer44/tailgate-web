<?php

namespace TailgateWeb\Scoring;

interface ScoringInterface
{
    public function generate($group, $season);
    public function getHtml() : string;
}