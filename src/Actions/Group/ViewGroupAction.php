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

// view group
class ViewGroupAction extends AbstractAction
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
        $member = [];
        $players = [];
        $season = [];
        $leaderboard = '';
        $scoreChart = '';

        extract($this->args);

        // get the group and determine if the user is a member
        $apiResponse = $this->apiClient->get("/v1/groups/{$groupId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            $this->flash->addMessage('error', $data['errors']);
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }
        $group = $data['data'];
        $member = collect($group['members'])->firstWhere('userId', $this->session->get('user')['userId']);
        if (!$member) {
            $this->flash->addMessage('error', 'Unable to determine if you are a member of the group.');
            return $this->response->withHeader('Location', "/dashboard")->withStatus(302);
        }

        // get players or all players if admin
        if ('Group-Admin' == $member['role']) {
            $players = collect($group['players'])->toArray();
        } else {
            $players = collect($group['players'])->where('memberId', $member['memberId'])->toArray();
        }

        // if the group is following a team then get all the games for the season they are following
        if (!empty($players) && isset($group['follow']['followId'])) {
            $followId = $group['follow']['followId'];
            $apiResponse = $this->apiClient->get("/v1/seasons/follow/{$followId}");
            $data = $apiResponse->getData();
            if ($apiResponse->hasErrors()) {
                return $this->view->render($this->response, 'group/view.twig', ['errors' => $data['errors']]);
            }
            $games = $data['data'];

            $this->scoring->generate($group, $games);
            $leaderboard = $this->scoring->getLeaderboardHtml();
            $scoreChart = $this->scoring->getChartHtml();
        }

        return $this->view->render($this->response, 'group/view.twig', compact(
            'group',
            'groupId',
            'member',
            'players',
            'season',
            'leaderboard',
            'scoreChart'
        ));
    }
}