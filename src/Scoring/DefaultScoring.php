<?php

namespace TailgateWeb\Scoring;

class DefaultScoring implements ScoringInterface
{
    private $playerNames = [];
    private $formattedData = [];
    private $finalValuesPerGame = [];
    private $leaderboard = [];

    public function generate($group, $games)
    {   
        $this->finalValuesPerGame = collect([]);

        // initialize as collections
        $players = collect($group['players'])->sortBy('username');
        $scores  = collect($group['scores']);
        $games   = collect($games);
        
        // only get games that are being followed by group
        $followedTeamId = $group['follow']['teamId'];
        $games = $games->filter(function($game) use ($followedTeamId) {
            return $game['homeTeamId'] == $followedTeamId || $game['awayTeamId'] == $followedTeamId;
        });

        // get all player names for use in header
        $this->playerNames = $players->flatMap(function($player) {
            return [$player['playerId'] => $player['username']];
        });

        // gather all data and group it by games
        $this->formattedData = $games->reduce(function($carry, $game) use ($players, $scores) {

            // get all score predictions by player
            $playerPredictionValues = $players->reduce(function($carry, $player) use ($game, $scores) {
                $scorePrediction = $scores->where('playerId', $player['playerId'])->where('gameId', $game['gameId'])->first();

                // if we should show the score in the grid or not
                $shouldDisplayScore = false;

                $gameDateTime = \DateTimeImmutable::createFromFormat('M j, Y (D) g:i A', $game['startDate'] . " " . $game['startTime']);
                if ($gameDateTime instanceof \DateTimeImmutable) {
                    $today = (new \DateTime('now'))->format('Y-m-d H:i:s');
                    $gameStart = $gameDateTime->format('Y-m-d H:i:s');
                    if ($today >= $gameStart) {
                        $shouldDisplayScore = true;
                    }
                } else {
                    // if creating the date time object fails then the game time is probably 'TBA' or something like that so just use the game date
                    $gameDateTime = $gameDateTime = \DateTimeImmutable::createFromFormat('M j, Y (D)', $game['startDate']);

                    if (!$shouldDisplayScore && $gameDateTime instanceof \DateTimeImmutable) {
                        $today = (new \DateTime('now'))->format('Y-m-d');
                        $gameStart = $gameDateTime->format('Y-m-d');
                        if ($today >= $gameStart) {
                            $shouldDisplayScore = true;
                        }
                    }
                }

                // if the game time has not passed then do not do any calculations and act as if no score was submitted
                $homeTeamPrediction = null;
                $awayTeamPrediction = null;
                if ($shouldDisplayScore) {
                    $homeTeamPrediction = $scorePrediction['homeTeamPrediction'];
                    $awayTeamPrediction = $scorePrediction['awayTeamPrediction'];
                }

                // absolute value difference from scores predicted and actual
                // return null if the game has no score or their is no prediction
                $difference = null;
                $penaltyPoints = 0;
                if ((null != $homeTeamPrediction) && (null != $awayTeamPrediction) && (null != $game['homeTeamScore']) && (null != $game['awayTeamScore'])) {
                    $difference = abs($game['homeTeamScore'] - $homeTeamPrediction) + abs($game['awayTeamScore'] - $awayTeamPrediction);

                    $didHomeTeamWin = $game['homeTeamScore'] > $game['awayTeamScore'];
                    $wasHomeTeamSelected = $homeTeamPrediction > $awayTeamPrediction;
                    $choseCorrectTeam = $didHomeTeamWin == $wasHomeTeamSelected;

                    // if a user selects the wrong team then 7 points
                    if (!$choseCorrectTeam) {
                        $penaltyPoints += 7;
                    }
                }

                $carry[] = [
                    'playerId' => $player['playerId'],
                    'home' => $homeTeamPrediction,
                    'away' => $awayTeamPrediction,
                    'difference' => $difference,
                    'penaltyPoints' => $penaltyPoints,
                    'final' =>  $difference + $penaltyPoints,
                ];
                return $carry;
            }, collect([]));

            // keep track of the highest point difference since it is used in extra penalty points
            $highestPointDifference = (int)collect($playerPredictionValues)->pluck('final')->max();

            // calculate penalty for failing to submit
            $playerPredictionValues = $playerPredictionValues->zip($playerPredictionValues->map(function($playerPrediction, $playerId) use ($game, $highestPointDifference) {
                
                $points = 0;

                // if a user fails to submit a score then they get the highest point difference plus 7
                if (null == $playerPrediction['home'] || null == $playerPrediction['away']) {
                    $points += $highestPointDifference;
                    $points += 7;
                }

                return $points;

            }))->map(function ($predictionValuesAndExtraPenalty) {
                list($values, $extraPenalty) = $predictionValuesAndExtraPenalty;
                $values['penaltyPoints'] = $values['penaltyPoints'] + $extraPenalty;
                // final points is updated penalty plus point difference
                $values['final'] = $values['difference'] + $values['penaltyPoints'];
                return $values;
            });

            // dd([$game['homeTeamScore'], $game['awayTeamScore']], $playerPredictionValues);

            // rank players
            $this->finalValuesPerGame[$game['gameId']] = $playerPredictionValues
                ->sortBy('final')
                ->zip(range(1, $playerPredictionValues->count()))
                ->flatMap(function ($predictionValuesAndRank) {
                    list($values, $rank) = $predictionValuesAndRank;
                    return [
                        $values['playerId'] => array_merge($values, ['rank' => $rank])
                    ];
                });

            $carry[$game['gameId']] = [
                'homeTeam'         => $game['homeDesignation'] . ' ' . $game['homeMascot'],
                'homeTeamScore'    => $game['homeTeamScore'],
                'awayTeam'         => $game['awayDesignation'] . ' ' . $game['awayMascot'],
                'awayTeamScore'    => $game['awayTeamScore'],
                'homePredictions'  => collect($playerPredictionValues)->pluck('home'),
                'awayPredictions'  => collect($playerPredictionValues)->pluck('away'),
                'pointDifferences' => collect($playerPredictionValues)->pluck('difference'),
                'penaltyPoints'    => collect($playerPredictionValues)->pluck('penaltyPoints'),
                'finalPoints'      => collect($playerPredictionValues)->pluck('final'),
            ];

            return $carry;

        }, collect([]));

        // dd($this->formattedData);
        // dd($this->finalValuesPerGame);
        
        $playerNames = $this->playerNames;
        $this->leaderboard = $this->finalValuesPerGame
            ->flatten(1)
            ->groupBy('playerId')
            ->map(function($allPlayerPredicationValues, $playerId) use ($playerNames) {
                return [
                    'player' => $playerNames[$playerId],
                    'total' => collect($allPlayerPredicationValues)->pluck('final')->sum()
                ];
            })
            ->sortBy('total')
            ->zip(range(1, $playerNames->count()))
            ->map(function ($totalAndRank) {
                list($values, $rank) = $totalAndRank;
                return array_merge($values, ['rank' => $rank]);
            })
            ->groupBy('total')
            ->map(function ($tiedTotals) {
                $lowestRank = $tiedTotals->pluck('rank')->min();
                return $tiedTotals->map(function ($rankedTotal) use ($lowestRank) {
                    return array_merge($rankedTotal, [
                        'rank' => $lowestRank
                    ]);
                });
            })
            ->collapse()
            ->sortBy('rank');
        // dd($this->leaderboard);

        return $this;
    }

    public function getLeaderboardHtml() : string
    {
        $gridHtml = "";

        // table and header start
        $gridHtml .= "<table cellpadding='5'><tr class='border-t-2 border-b-2 border-black'><th>Name</th><th>Total Points</th><th>Rank</th></tr>";
        $gridHtml .= $this->leaderboard->reduce(function($html, $points) {
            $html .= "<tr>
                        <td class='border'>{$points['player']}</td>
                        <td class='border'>{$points['total']}</td>
                        <td class='border'>{$points['rank']}</td>
                    </tr>";
            return $html;
        }, '');
        $gridHtml .= '</table>';

        return $gridHtml;
    }

    public function getChartHtml() : string
    {   
        $gridHtml = "";

        // table and header start
        $gridHtml .= "<table cellpadding='5'><tr class='border-t-2 border-black'><th>Game</th><th>Final Score</th>";

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