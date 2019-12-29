<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view all seasons
class AllSeasonsAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $clientResponse = $this->apiClient->get("/v1/seasons");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            return $this->view->render($this->response, 'admin/season/index.twig', ['errors' => $data['errors']]);
        }

        $seasons = $data['data'];
        return $this->view->render($this->response, 'admin/season/index.twig', compact('seasons'));
    }
}