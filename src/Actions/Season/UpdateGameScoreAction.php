<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update game score form
class UpdateGameScoreAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {            
        extract($this->args);

        $apiResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
        $data = $apiResponse->getData();
        $season = $data['data'];
        $game = collect($season['games'])->firstWhere('gameId', $gameId);

        // convert game date and game time into something useable by the form
        $gameDateTime = \DateTimeImmutable::createFromFormat('M j, Y (D) g:i A', $game['startDate'] . " " . $game['startTime']);
        if ($gameDateTime instanceof \DateTimeImmutable) {
            $game['startDate'] = $gameDateTime->format('Y-m-d');
            $game['startTime'] = $gameDateTime->format('H:i');
        } 
        // if creating the date time object fails then the game time is probably 'TBA' or something like that so just use the game date
        $gameDateTime = $gameDateTime = \DateTimeImmutable::createFromFormat('M j, Y (D)', $game['startDate']);
        if ($gameDateTime instanceof \DateTimeImmutable) {
            $game['startDate'] = $gameDateTime->format('Y-m-d');
        }

        $homeTeam = "Home Team - {$game['homeDesignation']} {$game['homeMascot']}";
        $awayTeam = "Away Team - {$game['awayDesignation']} {$game['awayMascot']}";

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/season/update-game-score.twig', compact(
                'seasonId',
                'gameId',
                'game',
                'homeTeam',
                'awayTeam',
            ));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->patch("/v1/admin/seasons/{$seasonId}/game/{$gameId}/score", [
            'seasonId' => $seasonId,
            'gameId' => $gameId,
            'homeTeamScore' => $parsedBody['home_team_score'],
            'awayTeamScore' => $parsedBody['away_team_score'],
            'startDate' => $parsedBody['start_date'],
            'startTime' => $parsedBody['start_time']
        ]);

        if ($apiResponse->hasErrors()) {
            $data = $apiResponse->getData();

            return $this->view->render($this->response, 'admin/season/update-game-score.twig', [
                'errors' => $data['errors'],
                'seasonId' => $seasonId,
                'gameId' => $gameId,
                'game' => $game,
                'homeTeam' => $homeTeam,
                'awayTeam' => $awayTeam,
            ]);
        }

        return $this->response->withHeader('Location', "/admin/season/{$seasonId}")->withStatus(302);
    }
}