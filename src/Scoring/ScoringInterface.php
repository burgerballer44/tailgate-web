<?php

namespace TailgateWeb\Scoring;

interface ScoringInterface
{
    public function generate($group, $season);
    public function getLeaderboardHtml() : string;
    public function getChartHtml() : string;
}