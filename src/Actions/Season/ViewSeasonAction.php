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

        $apiResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/season/view.twig', ['errors' => $data['errors']]);
        }

        $season = $data['data'];
        $eventLog = $season['eventLog'];
        $games = $season['games'];
        return $this->view->render($this->response, 'admin/season/view.twig', compact('season', 'games', 'eventLog'));
    }
}