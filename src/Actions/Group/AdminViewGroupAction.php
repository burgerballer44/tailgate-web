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
        $leaderboard = '';
        $scoreChart = '';
        extract($this->args);

        $apiResponse = $this->apiClient->get("/v1/admin/groups/{$groupId}");
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/admin/groups")->withStatus(302);
        }

        $group = $data['data'];
        $eventLog = $group['eventLog'];

        // if the group is following a team then get all the games for the season they are following
        if (isset($group['follow']['followId'])) {
            $followId = $group['follow']['followId'];
            $apiResponse = $this->apiClient->get("/v1/seasons/follow/{$followId}");
            $data = $apiResponse->getData();
            if ($apiResponse->hasErrors()) {
                return $this->view->render($this->response, 'admin/group/view.twig', ['errors' => $data['errors']]);
            }
            $games = $data['data'];

            $this->scoring->generate($group, $games);
            $leaderboard = $this->scoring->getLeaderboardHtml();
            $scoreChart = $this->scoring->getChartHtml();
        }

        return $this->view->render($this->response, 'admin/group/view.twig', compact('group', 'groupId', 'leaderboard', 'scoreChart', 'eventLog'));
    }
}