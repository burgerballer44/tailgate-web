<?php

namespace TailgateWeb\Scoring;

interface ScoringInterface
{
    public function generate($group, $games);
    public function getLeaderboardHtml() : string;
    public function getChartHtml() : string;
}