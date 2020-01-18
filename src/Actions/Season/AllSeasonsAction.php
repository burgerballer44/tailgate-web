<?php

namespace TailgateWeb\Actions\Season;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// view all seasons
class AllSeasonsAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $apiResponse = $this->apiClient->get("/v1/seasons");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/season/index.twig', ['errors' => $data['errors']]);
        }

        $seasons = $data['data'];
        return $this->view->render($this->response, 'admin/season/index.twig', compact('seasons'));
    }
}