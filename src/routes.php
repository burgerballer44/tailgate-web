<?php

use Slim\Routing\RouteCollectorProxy as Group;
use Slim\App;
use TailgateWeb\Middleware\{MustBeSignedOutMiddleware, MustBeSignedInMiddleware};

return function (App $app) use ($request) {

    $container = $app->getContainer();

    // homepage, signing in and out
    $app->get('/', \TailgateWeb\Controllers\HomeController::class . ':home')->setName('home')->add(MustBeSignedOutMiddleware::class);
    $app->get('/sign-in', \TailgateWeb\Controllers\HomeController::class . ':signIn')->setName('sign-in')->add(MustBeSignedOutMiddleware::class);
    $app->get('/sign-out', \TailgateWeb\Controllers\HomeController::class . ':signOut')->setName('sign-out')->add(MustBeSignedInMiddleware::class);
    $app->post('/sign-in', \TailgateWeb\Controllers\HomeController::class . ':signInPost')->add(MustBeSignedOutMiddleware::class);

    // reset password
    $app->get('/request-reset', \TailgateWeb\Controllers\HomeController::class . ':requestReset')->setName('request-reset')->add(MustBeSignedOutMiddleware::class);
    $app->post('/request-reset', \TailgateWeb\Controllers\HomeController::class . ':requestResetPost')->add(MustBeSignedOutMiddleware::class);
    $app->get('/reset-password/{token}', \TailgateWeb\Controllers\HomeController::class . ':password')->setName('reset-password');
    $app->post('/reset-password/{token}', \TailgateWeb\Controllers\HomeController::class . ':passwordPost');

    // register
    $app->get('/register', \TailgateWeb\Controllers\UserController::class . ':register')->setName('register')->add(MustBeSignedOutMiddleware::class);
    $app->post('/register', \TailgateWeb\Controllers\UserController::class . ':registerPost')->add(MustBeSignedOutMiddleware::class);
    $app->get('/confirm', \TailgateWeb\Controllers\UserController::class . ':confirm')->setName('confirm')->add(MustBeSignedOutMiddleware::class);

    // dashboard
    $app->get('/dashboard', \TailgateWeb\Controllers\DashboardController::class . ':dashboard')->setName('dashboard')->add(MustBeSignedInMiddleware::class);

    // user
    $app->group('/profile', function (Group $group) {
        $group->get('/', \TailgateWeb\Controllers\UserController::class . ':profile')->setName('profile');
        $group->get('/email', \TailgateWeb\Controllers\UserController::class . ':email')->setName('update-email');
        $group->post('/email', \TailgateWeb\Controllers\UserController::class . ':emailPost');
    })->add(MustBeSignedInMiddleware::class);

    // group
    $app->group('/group', function (Group $group) {
        $group->get('/invite-code', \TailgateWeb\Controllers\GroupController::class . ':inviteCode')->setName('invite-code');
        $group->post('/invite-code', \TailgateWeb\Controllers\GroupController::class . ':inviteCodePost');
        $group->get('/create', \TailgateWeb\Controllers\GroupController::class . ':create')->setName('create-group');
        $group->post('/create', \TailgateWeb\Controllers\GroupController::class . ':createPost');
        $group->get('/{groupId}', \TailgateWeb\Controllers\GroupController::class . ':view')->setName('group');
        $group->get('/{groupId}/delete', \TailgateWeb\Controllers\GroupController::class . ':delete')->setName('delete-group');
        $group->get('/{groupId}/send-invite', \TailgateWeb\Controllers\GroupController::class . ':sendInvite')->setName('send-invite');
        $group->post('/{groupId}/send-invite', \TailgateWeb\Controllers\GroupController::class . ':sendInvitePost');
        $group->get('/{groupId}/add-player/{memberId}', \TailgateWeb\Controllers\GroupController::class . ':addPlayer')->setName('add-player');
        $group->post('/{groupId}/add-player/{memberId}', \TailgateWeb\Controllers\GroupController::class . ':addPlayerPost');
        $group->get('/{groupId}/follow', \TailgateWeb\Controllers\GroupController::class . ':follow')->setName('follow-team');
        $group->post('/{groupId}/follow', \TailgateWeb\Controllers\GroupController::class . ':followPost');
        $group->get('/{groupId}/follow/{followId}/delete', \TailgateWeb\Controllers\GroupController::class . ':deleteFollow')->setName('delete-follow');
        $group->get('/{groupId}/member/{memberId}/update', \TailgateWeb\Controllers\GroupController::class . ':updateMember')->setName('update-member');
        $group->post('/{groupId}/member/{memberId}/update', \TailgateWeb\Controllers\GroupController::class . ':updateMemberPost');
        $group->get('/{groupId}/member/{memberId}/delete', \TailgateWeb\Controllers\GroupController::class . ':deleteMember')->setName('delete-member');
        $group->get('/{groupId}/player/{playerId}/delete', \TailgateWeb\Controllers\GroupController::class . ':deletePlayer')->setName('delete-player');
        $group->get('/{groupId}/submit-score/{playerId}', \TailgateWeb\Controllers\GroupController::class . ':submitScore')->setName('submit-score');
        $group->post('/{groupId}/submit-score/{playerId}', \TailgateWeb\Controllers\GroupController::class . ':submitScorePost');
        $group->get('/{groupId}/update-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':updateScore')->setName('update-score');
        $group->post('/{groupId}/update-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':updateScorePost');
        $group->get('/{groupId}/delete-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':deleteScore')->setName('delete-score');
    })->add(MustBeSignedInMiddleware::class);

    // season
    $app->group('/season', function (Group $group) {
        $group->get('/teamlist/{seasonId}', \TailgateWeb\Controllers\SeasonController::class . ':teamlist')->setName('teamlist');
    })->add(MustBeSignedInMiddleware::class);

    // admin access
    $app->group('/admin', function (Group $group) {

        $group->group('/users', function (Group $group) {
            $group->get('', \TailgateWeb\Controllers\UserController::class . ':all')->setName('users');
            $group->get('/{userId}', \TailgateWeb\Controllers\UserController::class . ':view')->setName('user');
            $group->get('/{userId}/resend', \TailgateWeb\Controllers\UserController::class . ':resendConfirmation')->setName('resend-confirmation');
            $group->get('/{userId}/update', \TailgateWeb\Controllers\UserController::class . ':update')->setName('update-user');
            $group->post('/{userId}/update', \TailgateWeb\Controllers\UserController::class . ':updatePost');
            $group->get('/{userId}/delete', \TailgateWeb\Controllers\UserController::class . ':delete')->setName('delete-user');
        });

        $group->group('/groups', function (Group $group) {
            $group->get('', \TailgateWeb\Controllers\GroupController::class . ':adminAll')->setName('groups');
            $group->get('/create', \TailgateWeb\Controllers\GroupController::class . ':adminCreate')->setName('admin-create-group');
            $group->post('/create', \TailgateWeb\Controllers\GroupController::class . ':adminCreatePost');
            $group->get('/{groupId}', \TailgateWeb\Controllers\GroupController::class . ':adminView')->setName('admin-group');
            $group->get('/{groupId}/update', \TailgateWeb\Controllers\GroupController::class . ':adminUpdate')->setName('admin-update-group');
            $group->post('/{groupId}/update', \TailgateWeb\Controllers\GroupController::class . ':adminUpdatePost');
            
            $group->get('/{groupId}/delete', \TailgateWeb\Controllers\GroupController::class . ':adminDelete')->setName('admin-delete-group');
            $group->get('/{groupId}/add-member', \TailgateWeb\Controllers\GroupController::class . ':adminAddMember')->setName('admin-add-member');
            $group->post('/{groupId}/add-member', \TailgateWeb\Controllers\GroupController::class . ':adminAddMemberPost');
            $group->get('/{groupId}/follow', \TailgateWeb\Controllers\GroupController::class . ':adminFollow')->setName('admin-follow-team');
            $group->post('/{groupId}/follow', \TailgateWeb\Controllers\GroupController::class . ':adminFollowPost');
            $group->get('/{groupId}/follow/{followId}/delete', \TailgateWeb\Controllers\GroupController::class . ':adminDeleteFollow')->setName('admin-delete-follow');
            $group->get('/{groupId}/member/{memberId}/update', \TailgateWeb\Controllers\GroupController::class . ':adminUpdateMember')->setName('admin-update-member');
            $group->post('/{groupId}/member/{memberId}/update', \TailgateWeb\Controllers\GroupController::class . ':adminUpdateMemberPost');
            $group->get('/{groupId}/member/{memberId}/delete', \TailgateWeb\Controllers\GroupController::class . ':adminDeleteMember')->setName('admin-delete-member');
            $group->get('/{groupId}/add-player/{memberId}', \TailgateWeb\Controllers\GroupController::class . ':adminAddPlayer')->setName('admin-add-player');
            $group->post('/{groupId}/add-player/{memberId}', \TailgateWeb\Controllers\GroupController::class . ':adminAddPlayerPost');
            $group->get('/{groupId}/player/{playerId}/delete', \TailgateWeb\Controllers\GroupController::class . ':adminDeletePlayer')->setName('admin-delete-player');
            $group->get('/{groupId}/submit-score/{playerId}', \TailgateWeb\Controllers\GroupController::class . ':adminSubmitScore')->setName('admin-submit-score');
            $group->post('/{groupId}/submit-score/{playerId}', \TailgateWeb\Controllers\GroupController::class . ':adminSubmitScorePost');
            $group->get('/{groupId}/update-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':adminUpdateScore')->setName('admin-update-score');
            $group->post('/{groupId}/update-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':adminUpdateScorePost');
            $group->get('/{groupId}/delete-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':adminDeleteScore')->setName('admin-delete-score');
        });

        // team
        $group->group('/team', function (Group $group) {
            $group->get('', \TailgateWeb\Controllers\TeamController::class . ':all')->setName('teams');
            $group->get('/add', \TailgateWeb\Controllers\TeamController::class . ':add')->setName('add-team');
            $group->post('/add', \TailgateWeb\Controllers\TeamController::class . ':addPost');
            $group->get('/{teamId}', \TailgateWeb\Controllers\TeamController::class . ':view')->setName('team');
            $group->get('/{teamId}/update', \TailgateWeb\Controllers\TeamController::class . ':update')->setName('update-team');
            $group->post('/{teamId}/update', \TailgateWeb\Controllers\TeamController::class . ':updatePost');
            $group->get('/{teamId}/delete', \TailgateWeb\Controllers\TeamController::class . ':delete')->setName('delete-team');
            $group->get('/{teamId}/follow', \TailgateWeb\Controllers\TeamController::class . ':adminFollow')->setName('admin-follow-team');
            $group->post('/{teamId}/follow', \TailgateWeb\Controllers\TeamController::class . ':adminFollowPost');
            $group->get('/{teamId}/{groupId}/follow/{followId}/delete', \TailgateWeb\Controllers\TeamController::class . ':adminDeleteFollow')->setName('admin-delete-follow');
        });

        // season
        $group->group('/season', function (Group $group) {
            $group->get('', \TailgateWeb\Controllers\SeasonController::class . ':all')->setName('seasons');
            $group->get('/create', \TailgateWeb\Controllers\SeasonController::class . ':create')->setName('create-season');
            $group->post('/create', \TailgateWeb\Controllers\SeasonController::class . ':createPost');
            $group->get('/{seasonId}', \TailgateWeb\Controllers\SeasonController::class . ':view')->setName('season');
            $group->get('/{seasonId}/update', \TailgateWeb\Controllers\SeasonController::class . ':update')->setName('update-season');
            $group->post('/{seasonId}/update', \TailgateWeb\Controllers\SeasonController::class . ':updatePost');
            $group->get('/{seasonId}/delete', \TailgateWeb\Controllers\SeasonController::class . ':delete')->setName('delete-season');
            $group->get('/{seasonId}/add-game', \TailgateWeb\Controllers\SeasonController::class . ':addGame')->setName('add-game');
            $group->post('/{seasonId}/add-game', \TailgateWeb\Controllers\SeasonController::class . ':addGamePost');
            $group->get('/{seasonId}/game/{gameId}/score', \TailgateWeb\Controllers\SeasonController::class . ':updateGameScore')->setName('update-game-score');
            $group->post('/{seasonId}/game/{gameId}/score', \TailgateWeb\Controllers\SeasonController::class . ':updateGameScorePost');
            $group->get('/{seasonId}/game/{gameId}/delete', \TailgateWeb\Controllers\SeasonController::class . ':deleteGame')->setName('delete-game');
        });

    })->add(MustBeSignedInMiddleware::class)->add(\TailgateWeb\Middleware\AdminMiddleware::class);
};