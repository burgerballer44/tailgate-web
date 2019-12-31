<?php

namespace TailgateWeb\Scoring;

class DefaultScoring implements ScoringInterface
{
    private $playerNames;
    private $formattedData;

    public function generate($group, $season)
    {   
        // initialize as collections
        $players = collect($group['players'])->sortBy('username');
        $scores  = collect($group['scores']);
        $games   = collect($season['games']);
        
        // only get games that are being followed by group
        $followedTeamId = $group['follow']['teamId'];
        $games = $games->filter(function($game) use ($followedTeamId) {
            return $game['homeTeamId'] == $followedTeamId || $game['awayTeamId'] == $followedTeamId;
        });

        // get all player names for use in header
        $this->playerNames = $players->pluck('username');

        // gather all data and group it by games
        $this->formattedData = $games->reduce(function($carry, $game) use ($players, $scores) {

            // get all home predictions
            $homePredictions = $players->reduce(function($temp, $player) use ($game, $scores) {
                $homePrediction  = $scores->where('playerId', $player['playerId'])->where('gameId', $game['gameId'])->first()['homeTeamPrediction'];
                $temp[$player['playerId']] = $homePrediction;
                return $temp;
            }, collect([]));

            // get all away predictions
            $awayPredictions = $players->reduce(function($temp, $player) use ($game, $scores) {
                $awayPrediction  = $scores->where('playerId', $player['playerId'])->where('gameId', $game['gameId'])->first()['awayTeamPrediction'];
                $temp[$player['playerId']] = $awayPrediction;
                return $temp;
            }, collect([]));

            // calculate the absolute value difference from scores predicted and 
            $pointDifferences = $homePredictions->map(function($homePrediction, $playerId) use ($game, $awayPredictions) {
                if (null == $homePrediction || null == $awayPredictions[$playerId] || null == $game['homeTeamScore'] || null == $game['awayTeamScore']) {
                    return null;
                }
                return abs(($game['homeTeamScore'] + $game['awayTeamScore']) - ($homePrediction + $awayPredictions[$playerId]));
            });

            // keep track of the highest point difference since it is used in penalty points
            $highestPointDifference = $pointDifferences->max();

            // calculate penalty points
            $penaltyPoints = $homePredictions->map(function($homePrediction, $playerId) use ($game, $awayPredictions, $highestPointDifference) {
                
                // no final score means we should not calculate
                if (null == $game['homeTeamScore'] || null == $game['awayTeamScore']) {
                    return null;
                }
                // if a user fails to submit a score then they get the highest point difference plus 7
                if (null == $homePrediction || null == $awayPredictions[$playerId] ) {
                    return $highestPointDifference + 7;
                }

                return 0;
            });

            // final points is penalty plus point difference
            $finalPoints = $penaltyPoints->map(function($penaltyPoint, $playerId) use ($pointDifferences) {
                return $penaltyPoint + $pointDifferences[$playerId];
            });

            // dd([$game['homeTeamScore'], $game['awayTeamScore']], $homePredictions, $awayPredictions, $pointDifferences, $penaltyPoints, $finalPoints);

            $carry[$game['gameId']] = [
                'homeTeam'         => $game['homeDesignation'] . ' ' . $game['homeMascot'],
                'homeTeamScore'    => $game['homeTeamScore'],
                'awayTeam'         => $game['awayDesignation'] . ' ' . $game['awayMascot'],
                'awayTeamScore'    => $game['awayTeamScore'],
                'homePredictions'  => $homePredictions,
                'awayPredictions'  => $awayPredictions,
                'pointDifferences' => $pointDifferences,
                'penaltyPoints'    => $penaltyPoints,
                'finalPoints'      => $finalPoints,
            ];

            return $carry;

        }, collect([]));

        // dd($this->formattedData);

        return $this;
    }

    public function getHtml() : string
    {   
        // table and header start
        $gridHtml = "<table cellpadding='5'><tr class='border-t-2 border-black'><th>Game</th><th>Final Score</th>";

        // add player names to header
        $gridHtml .= $this->playerNames->reduce(function($headerHtml, $player) {
            $headerHtml .= "<th>{$player}</th>";
            return $headerHtml;
        }, '');

        // end header
        $gridHtml .= '</tr>';

        // table data
        $gridHtml .= $this->formattedData->reduce(function($tableHtml, $data){

            // home
            $tableHtml .= "<tr class='border border-black border-t-2'>";
            $tableHtml .= "<td class='border'>{$data['homeTeam']}</td>";
            $tableHtml .= "<td class='border' align='center'>{$data['homeTeamScore']}</td>";
            $tableHtml .= $data['homePredictions']->reduce(function($html, $score) {
                $html .= "<td class='border'>{$score}</td>";
                return $html;
            }, '');
            $tableHtml .= "</tr>";

            // away
            $tableHtml .= "<tr class='border'>";
            $tableHtml .= "<td class='border'>{$data['awayTeam']}</td>";
            $tableHtml .= "<td class='border' align='center'>{$data['awayTeamScore']}</td>";
            $tableHtml .= $data['awayPredictions']->reduce(function($html, $score) {
                $html .= "<td class='border'>{$score}</td>";
                return $html;
            }, '');
            $tableHtml .= "</tr>";

            // point differences
            $tableHtml .= "<tr class='border'><td colspan='2' align='right' class='border'>Point Difference</td>";
            $tableHtml .= $data['pointDifferences']->reduce(function($html, $score) {
                $html .= "<td class='border'>{$score}</td>";
                return $html;
            }, '');
            $tableHtml .= "</tr>";

            // penalty points
            $tableHtml .= "<tr class='border'><td colspan='2' align='right' class='border'>Penalty Points</td>";
            $tableHtml .= $data['penaltyPoints']->reduce(function($html, $score) {
                $html .= "<td class='border'>{$score}</td>";
                return $html;
            }, '');
            $tableHtml .= "</tr>";

            // final points
            $tableHtml .= "<tr class='border'><td colspan='2' align='right' class='border'>Final Points</td>";
            $tableHtml .= $data['finalPoints']->reduce(function($html, $score) {
                $html .= "<td class='border'>{$score}</td>";
                return $html;
            }, '');
            $tableHtml .= "</tr>";

            return $tableHtml;
        }, '');

        $gridHtml .= '</table>';

        return $gridHtml;
    }
}