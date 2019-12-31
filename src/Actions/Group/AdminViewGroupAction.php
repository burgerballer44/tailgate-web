<?php

namespace TailgateWeb\Actions\Group;

use Psr\Http\Message\ResponseInterface;
use Slim\Flash\Messages;
use Slim\Views\Twig;
use TailgateWeb\Actions\AbstractAction;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Mailer\MailerInterface;
use TailgateWeb\Scoring\ScoringInterface;
use TailgateWeb\Session\SessionHelperInterface;

// view group for admin
class AdminViewGroupAction extends AbstractAction
{   
    private $scoring;

    public function __construct(
        TailgateApiClientInterface $apiClient,
        SessionHelperInterface $session,
        MailerInterface $mailer,
        Twig $view,
        Messages $flash,
        ScoringInterface $scoring
    ) {
        parent::__construct($apiClient, $session, $mailer, $view, $flash);
        $this->scoring = $scoring;
    }

    public function action() : ResponseInterface
    {    
        $group = [];
        $season = [];
        $eventLog = [];
        $gridHtml = '';
        extract($this->args);

        $clientResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = json_decode($clientResponse->getBody(), true);

        if ($clientResponse->getStatusCode() >= 400) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/admin/groups")->withStatus(302);
        }

        $group = $data['data'];
        $eventLog = $group['eventLog'];

        // if the group is following a team then get all the games for the season they are following
        if (isset($group['follow']['seasonId'])) {
            $seasonId = $group['follow']['seasonId'];
            $clientResponse = $this->apiClient->get("/v1/seasons/{$seasonId}");
            $data = json_decode($clientResponse->getBody(), true);
            if ($clientResponse->getStatusCode() >= 400) {
                return $this->view->render($this->response, 'admin/season/view.twig', ['errors' => $data['errors']]);
            }
            $season = $data['data'];
        
            $gridHtml = $this->scoring->generate($group, $season)->getHtml();
        }

        return $this->view->render($this->response, 'admin/group/view.twig', compact('group', 'groupId', 'gridHtml', 'eventLog'));
    }
}