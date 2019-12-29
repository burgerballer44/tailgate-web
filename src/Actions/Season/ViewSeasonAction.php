<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view a season
class ViewSeasonAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        extract($this->args);

        $clientResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($this->response, 'admin/season/view.twig', ['errors' => $data['errors']]);
        }

        $season = $data['data'];
        $eventLog = $season['eventLog'];
        $games = $season['games'];
        return $this->view->render($this->response, 'admin/season/view.twig', compact('season', 'games', 'eventLog'));
    }
}