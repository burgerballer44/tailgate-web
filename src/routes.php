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

    // register
    $app->get('/register', \TailgateWeb\Controllers\UserController::class . ':register')->setName('register')->add(MustBeSignedOutMiddleware::class);
    $app->post('/register', \TailgateWeb\Controllers\UserController::class . ':registerPost')->add(MustBeSignedOutMiddleware::class);
    $app->get('/confirm', \TailgateWeb\Controllers\UserController::class . ':confirm')->setName('confirm')->add(MustBeSignedOutMiddleware::class);

    // dashboard
    $app->get('/dashboard', \TailgateWeb\Controllers\DashboardController::class . ':dashboard')->setName('dashboard')->add(MustBeSignedInMiddleware::class);

    // user
    $app->group('/user', function (Group $group) {
        $group->get('', \TailgateWeb\Controllers\UserController::class . ':all')->setName('users');
        $group->get('/{userId}', \TailgateWeb\Controllers\UserController::class . ':view')->setName('user');
        $group->get('/{userId}/update', \TailgateWeb\Controllers\UserController::class . ':update')->setName('update-user');
        $group->post('/{userId}/update', \TailgateWeb\Controllers\UserController::class . ':updatePost');
        $group->get('/{userId}/delete', \TailgateWeb\Controllers\UserController::class . ':delete')->setName('delete-user');
        $group->get('/{userId}/email', \TailgateWeb\Controllers\UserController::class . ':email')->setName('update-email');
        $group->post('/{userId}/email', \TailgateWeb\Controllers\UserController::class . ':emailPost');
        $group->get('/{userId}/password', \TailgateWeb\Controllers\UserController::class . ':password')->setName('update-password');
        $group->post('/{userId}/password', \TailgateWeb\Controllers\UserController::class . ':passwordPost');
    })->add(MustBeSignedInMiddleware::class);

    // group
    $app->group('/group', function (Group $group) {
        $group->get('', \TailgateWeb\Controllers\GroupController::class . ':all')->setName('groups');
        $group->get('/create', \TailgateWeb\Controllers\GroupController::class . ':create')->setName('create-group');
        $group->post('/create', \TailgateWeb\Controllers\GroupController::class . ':createPost');
        $group->get('/{groupId}', \TailgateWeb\Controllers\GroupController::class . ':view')->setName('group');
        $group->get('/{groupId}/update', \TailgateWeb\Controllers\GroupController::class . ':update')->setName('update-group');
        $group->post('/{groupId}/update', \TailgateWeb\Controllers\GroupController::class . ':updatePost');
        $group->get('/{groupId}/delete', \TailgateWeb\Controllers\GroupController::class . ':delete')->setName('delete-group');
        $group->get('/{groupId}/add-member', \TailgateWeb\Controllers\GroupController::class . ':addMember')->setName('add-member');
        $group->post('/{groupId}/add-member', \TailgateWeb\Controllers\GroupController::class . ':addMemberPost');
        $group->get('/{groupId}/member/{memberId}/update', \TailgateWeb\Controllers\GroupController::class . ':updateMember')->setName('update-member');
        $group->post('/{groupId}/member/{memberId}/update', \TailgateWeb\Controllers\GroupController::class . ':updateMemberPost');
        $group->get('/{groupId}/member/{memberId}/delete', \TailgateWeb\Controllers\GroupController::class . ':deleteMember')->setName('delete-member');
        $group->get('/{groupId}/add-player/{memberId}', \TailgateWeb\Controllers\GroupController::class . ':addPlayer')->setName('add-player');
        $group->post('/{groupId}/add-player/{memberId}', \TailgateWeb\Controllers\GroupController::class . ':addPlayerPost');
        $group->get('/{groupId}/player/{playerId}/delete', \TailgateWeb\Controllers\GroupController::class . ':deletePlayer')->setName('delete-player');
        $group->get('/{groupId}/submit-score/{playerId}', \TailgateWeb\Controllers\GroupController::class . ':submitScore')->setName('submit-score');
        $group->post('/{groupId}/submit-score/{playerId}', \TailgateWeb\Controllers\GroupController::class . ':submitScorePost');
        $group->get('/{groupId}/update-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':updateScore')->setName('update-score');
        $group->post('/{groupId}/update-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':updateScorePost');
        $group->get('/{groupId}/delete-score/{scoreId}', \TailgateWeb\Controllers\GroupController::class . ':deleteScore')->setName('delete-score');
    })->add(MustBeSignedInMiddleware::class);

    // team
    $app->group('/team', function (Group $group) {
        $group->get('', \TailgateWeb\Controllers\TeamController::class . ':all')->setName('teams');
        $group->get('/add', \TailgateWeb\Controllers\TeamController::class . ':add')->setName('add-team');
        $group->post('/add', \TailgateWeb\Controllers\TeamController::class . ':addPost');
        $group->get('/{teamId}', \TailgateWeb\Controllers\TeamController::class . ':view')->setName('team');
        $group->get('/{teamId}/update', \TailgateWeb\Controllers\TeamController::class . ':update')->setName('update-team');
        $group->post('/{teamId}/update', \TailgateWeb\Controllers\TeamController::class . ':updatePost');
        $group->get('/{teamId}/delete', \TailgateWeb\Controllers\TeamController::class . ':delete')->setName('delete-team');
        $group->get('/{teamId}/follow', \TailgateWeb\Controllers\TeamController::class . ':follow')->setName('follow-team');
        $group->post('/{teamId}/follow', \TailgateWeb\Controllers\TeamController::class . ':followPost');
        $group->get('/{teamId}/follow/{followId}/delete', \TailgateWeb\Controllers\TeamController::class . ':deleteFollow')->setName('delete-follow');
    })->add(MustBeSignedInMiddleware::class);

    // season
    $app->group('/season', function (Group $group) {
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
    })->add(MustBeSignedInMiddleware::class);

};