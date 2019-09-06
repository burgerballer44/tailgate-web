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
    })->add(MustBeSignedInMiddleware::class);

    // group
    $app->group('/group', function (Group $group) {
        $group->get('', \TailgateWeb\Controllers\GroupController::class . ':all')->setName('groups');
        $group->get('/create', \TailgateWeb\Controllers\GroupController::class . ':create')->setName('create-group');
        $group->post('/create', \TailgateWeb\Controllers\GroupController::class . ':createPost');
        $group->get('/add-member', \TailgateWeb\Controllers\GroupController::class . ':addMember')->setName('add-member');
        $group->post('/add-member', \TailgateWeb\Controllers\GroupController::class . ':addMemberPost');
        $group->get('/submit-score', \TailgateWeb\Controllers\GroupController::class . ':submitScore')->setName('submit-score');
        $group->post('/submit-score', \TailgateWeb\Controllers\GroupController::class . ':submitScorePost');
        $group->get('/{id}', \TailgateWeb\Controllers\GroupController::class . ':view')->setName('group');
    })->add(MustBeSignedInMiddleware::class);

    // team
    $app->group('/team', function (Group $group) {
        $group->get('', \TailgateWeb\Controllers\TeamController::class . ':all')->setName('teams');
        $group->get('/add', \TailgateWeb\Controllers\TeamController::class . ':add')->setName('add-team');
        $group->post('/add', \TailgateWeb\Controllers\TeamController::class . ':addPost');
        $group->get('/follow', \TailgateWeb\Controllers\TeamController::class . ':follow')->setName('follow-team');
        $group->post('/follow', \TailgateWeb\Controllers\TeamController::class . ':followPost');
        $group->get('/{id}', \TailgateWeb\Controllers\TeamController::class . ':view')->setName('team');
    })->add(MustBeSignedInMiddleware::class);

    // season
    $app->group('/season', function (Group $group) {
        $group->get('', \TailgateWeb\Controllers\SeasonController::class . ':all')->setName('seasons');
        $group->get('/create', \TailgateWeb\Controllers\SeasonController::class . ':create')->setName('create-season');
        $group->post('/create', \TailgateWeb\Controllers\SeasonController::class . ':createPost');
        $group->get('/add-game', \TailgateWeb\Controllers\SeasonController::class . ':addGame')->setName('add-game');
        $group->post('/add-game', \TailgateWeb\Controllers\SeasonController::class . ':addGamePost');
        $group->get('/add-game-score', \TailgateWeb\Controllers\SeasonController::class . ':addGameScore')->setName('add-game-score');
        $group->post('/add-game-score', \TailgateWeb\Controllers\SeasonController::class . ':addGameScorePost');
        $group->get('/{id}', \TailgateWeb\Controllers\SeasonController::class . ':view')->setName('season');
    })->add(MustBeSignedInMiddleware::class);

};