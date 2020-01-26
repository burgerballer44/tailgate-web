<?php

use Slim\Routing\RouteCollectorProxy as Group;
use Slim\App;
use TailgateWeb\Middleware\{MustBeSignedOutMiddleware, MustBeSignedInMiddleware};

return function (App $app) {

    // homepage, signing in and out
    $app->get('/', \TailgateWeb\Actions\Home\HomeAction::class)->setName('home')->add(MustBeSignedOutMiddleware::class);
    $app->get('/sign-in', \TailgateWeb\Actions\Home\SignInAction::class)->setName('sign-in')->add(MustBeSignedOutMiddleware::class);
    $app->get('/sign-out', \TailgateWeb\Actions\Home\SignOutAction::class)->setName('sign-out')->add(MustBeSignedInMiddleware::class);
    $app->post('/sign-in', \TailgateWeb\Actions\Home\SignInAction::class)->add(MustBeSignedOutMiddleware::class);

    // reset password
    $app->get('/request-reset', \TailgateWeb\Actions\Home\RequestResetAction::class)->setName('request-reset')->add(MustBeSignedOutMiddleware::class);
    $app->post('/request-reset', \TailgateWeb\Actions\Home\RequestResetAction::class)->add(MustBeSignedOutMiddleware::class);
    $app->get('/reset-password/{token}', \TailgateWeb\Actions\Home\ResetPasswordAction::class)->setName('reset-password');
    $app->post('/reset-password/{token}', \TailgateWeb\Actions\Home\ResetPasswordAction::class);

    // register
    $app->get('/register', \TailgateWeb\Actions\User\RegisterAction::class)->setName('register')->add(MustBeSignedOutMiddleware::class);
    $app->post('/register', \TailgateWeb\Actions\User\RegisterAction::class)->add(MustBeSignedOutMiddleware::class);
    $app->get('/confirm', \TailgateWeb\Actions\User\ConfirmEmailAction::class)->setName('confirm')->add(MustBeSignedOutMiddleware::class);

    // dashboard
    $app->get('/dashboard', \TailgateWeb\Actions\Dashboard\DashboardAction::class)->setName('dashboard')->add(MustBeSignedInMiddleware::class);
    // $app->post('/upload', \TailgateWeb\Actions\Dashboard\UploadAction::class)->setName('upload')->add(MustBeSignedInMiddleware::class);

    // user
    $app->group('/profile', function (Group $group) {
        $group->get('/', \TailgateWeb\Actions\User\ProfileAction::class)->setName('profile');
        $group->get('/email', \TailgateWeb\Actions\User\UpdateEmailAction::class)->setName('update-email');
        $group->post('/email', \TailgateWeb\Actions\User\UpdateEmailAction::class);
    })->add(MustBeSignedInMiddleware::class);

    // group
    $app->group('/group', function (Group $group) {
        $group->get('/invite-code', \TailgateWeb\Actions\Group\InviteCodeAction::class)->setName('invite-code');
        $group->post('/invite-code', \TailgateWeb\Actions\Group\InviteCodeAction::class);
        $group->get('/create', \TailgateWeb\Actions\Group\CreateGroupAction::class)->setName('create-group');
        $group->post('/create', \TailgateWeb\Actions\Group\CreateGroupAction::class);
        $group->get('/{groupId}', \TailgateWeb\Actions\Group\ViewGroupAction::class)->setName('group');
        $group->get('/{groupId}/delete', \TailgateWeb\Actions\Group\DeleteGroupAction::class)->setName('delete-group');
        $group->get('/{groupId}/send-invite', \TailgateWeb\Actions\Group\SendInviteAction::class)->setName('send-invite');
        $group->post('/{groupId}/send-invite', \TailgateWeb\Actions\Group\SendInviteAction::class);
        $group->get('/{groupId}/add-player/{memberId}', \TailgateWeb\Actions\Group\AddPlayerAction::class)->setName('add-player');
        $group->post('/{groupId}/add-player/{memberId}', \TailgateWeb\Actions\Group\AddPlayerAction::class);
        $group->get('/{groupId}/follow', \TailgateWeb\Actions\Group\FollowAction::class)->setName('follow-team');
        $group->post('/{groupId}/follow', \TailgateWeb\Actions\Group\FollowAction::class);
        $group->get('/{groupId}/follow/{followId}/delete', \TailgateWeb\Actions\Group\DeleteFollowAction::class)->setName('delete-follow');
        $group->get('/{groupId}/member/{memberId}/update', \TailgateWeb\Actions\Group\UpdateMemberAction::class)->setName('update-member');
        $group->post('/{groupId}/member/{memberId}/update', \TailgateWeb\Actions\Group\UpdateMemberAction::class);
        $group->get('/{groupId}/member/{memberId}/delete', \TailgateWeb\Actions\Group\DeleteMemberAction::class)->setName('delete-member');
        $group->get('/{groupId}/player/{playerId}/delete', \TailgateWeb\Actions\Group\DeletePlayerAction::class)->setName('delete-player');
        $group->get('/{groupId}/player/{playerId}', \TailgateWeb\Actions\Group\UpdatePlayerAction::class)->setName('update-player');
        $group->post('/{groupId}/player/{playerId}', \TailgateWeb\Actions\Group\UpdatePlayerAction::class);
        $group->get('/{groupId}/submit-score/{memberId}', \TailgateWeb\Actions\Group\SubmitScoreForGroupAction::class)->setName('submit-score');
        $group->post('/{groupId}/submit-score/{memberId}', \TailgateWeb\Actions\Group\SubmitScoreForGroupAction::class);
        $group->get('/{groupId}/update-score/{scoreId}', \TailgateWeb\Actions\Group\UpdateScoreForGroupAction::class)->setName('update-score');
        $group->post('/{groupId}/update-score/{scoreId}', \TailgateWeb\Actions\Group\UpdateScoreForGroupAction::class);
        $group->get('/{groupId}/delete-score/{scoreId}',\TailgateWeb\Actions\Group\DeleteScoreForGroupAction::class)->setName('delete-score');
    })->add(MustBeSignedInMiddleware::class);

    // season
    $app->group('/season', function (Group $group) {
        $group->get('/teamlist/{seasonId}', \TailgateWeb\Actions\Season\TeamListAction::class)->setName('teamlist');
    })->add(MustBeSignedInMiddleware::class);

    // admin access
    $app->group('/admin', function (Group $group) {

        $group->group('/users', function (Group $group) {
            $group->get('', \TailgateWeb\Actions\User\AllUsersAction::class)->setName('users');
            $group->get('/{userId}', \TailgateWeb\Actions\User\ViewUserAction::class)->setName('user');
            $group->get('/{userId}/resend', \TailgateWeb\Actions\User\ResendConfirmationAction::class)->setName('resend-confirmation');
            $group->get('/{userId}/update', \TailgateWeb\Actions\User\UpdateUserAction::class)->setName('update-user');
            $group->post('/{userId}/update', \TailgateWeb\Actions\User\UpdateUserAction::class);
            $group->get('/{userId}/delete', \TailgateWeb\Actions\User\DeleteUserAction::class)->setName('delete-user');
        });

        $group->group('/groups', function (Group $group) {
            $group->get('', \TailgateWeb\Actions\Group\AllGroupsAction::class)->setName('groups');
            $group->get('/create', \TailgateWeb\Actions\Group\AdminCreateGroupAction::class)->setName('admin-create-group');
            $group->post('/create', \TailgateWeb\Actions\Group\AdminCreateGroupAction::class);
            $group->get('/{groupId}', \TailgateWeb\Actions\Group\AdminViewGroupAction::class)->setName('admin-group');
            $group->get('/{groupId}/update', \TailgateWeb\Actions\Group\AdminUpdateGroupAction::class)->setName('admin-update-group');
            $group->post('/{groupId}/update', \TailgateWeb\Actions\Group\AdminUpdateGroupAction::class);
            $group->get('/{groupId}/delete', \TailgateWeb\Actions\Group\AdminDeleteGroupAction::class)->setName('admin-delete-group');
            $group->get('/{groupId}/add-member', \TailgateWeb\Actions\Group\AdminAddMemberAction::class)->setName('admin-add-member');
            $group->post('/{groupId}/add-member', \TailgateWeb\Actions\Group\AdminAddMemberAction::class);
            $group->get('/{groupId}/follow', \TailgateWeb\Actions\Group\AdminFollowAction::class)->setName('admin-follow-team');
            $group->post('/{groupId}/follow', \TailgateWeb\Actions\Group\AdminFollowAction::class);
            $group->get('/{groupId}/follow/{followId}/delete', \TailgateWeb\Actions\Group\AdminDeleteFollowAction::class)->setName('admin-delete-follow');
            $group->get('/{groupId}/member/{memberId}/update', \TailgateWeb\Actions\Group\AdminUpdateMemberAction::class)->setName('admin-update-member');
            $group->post('/{groupId}/member/{memberId}/update', \TailgateWeb\Actions\Group\AdminUpdateMemberAction::class);
            $group->get('/{groupId}/member/{memberId}/delete', \TailgateWeb\Actions\Group\AdminDeleteMemberAction::class)->setName('admin-delete-member');
            $group->get('/{groupId}/add-player/{memberId}', \TailgateWeb\Actions\Group\AdminAddPlayerAction::class)->setName('admin-add-player');
            $group->post('/{groupId}/add-player/{memberId}', \TailgateWeb\Actions\Group\AdminAddPlayerAction::class);
            $group->get('/{groupId}/player/{playerId}', \TailgateWeb\Actions\Group\AdminUpdatePlayerAction::class)->setName('admin-update-player');
            $group->post('/{groupId}/player/{playerId}', \TailgateWeb\Actions\Group\AdminUpdatePlayerAction::class);
            $group->get('/{groupId}/player/{playerId}/delete', \TailgateWeb\Actions\Group\AdminDeletePlayerAction::class)->setName('admin-delete-player');
            $group->get('/{groupId}/submit-score/{memberId}', \TailgateWeb\Actions\Group\AdminSubmitScoreForGroupAction::class)->setName('admin-submit-score');
            $group->post('/{groupId}/submit-score/{memberId}', \TailgateWeb\Actions\Group\AdminSubmitScoreForGroupAction::class);
            $group->get('/{groupId}/update-score/{scoreId}', \TailgateWeb\Actions\Group\AdminUpdateScoreForGroupAction::class)->setName('admin-update-score');
            $group->post('/{groupId}/update-score/{scoreId}', \TailgateWeb\Actions\Group\AdminUpdateScoreForGroupAction::class);
            $group->get('/{groupId}/delete-score/{scoreId}', \TailgateWeb\Actions\Group\AdminDeleteScoreForGroupAction::class)->setName('admin-delete-score');
        });

        // team
        $group->group('/team', function (Group $group) {
            $group->get('', \TailgateWeb\Actions\Team\AllTeamsAction::class)->setName('teams');
            $group->get('/add', \TailgateWeb\Actions\Team\AddTeamAction::class)->setName('add-team');
            $group->post('/add', \TailgateWeb\Actions\Team\AddTeamAction::class);
            $group->get('/{teamId}', \TailgateWeb\Actions\Team\ViewTeamAction::class)->setName('team');
            $group->get('/{teamId}/update', \TailgateWeb\Actions\Team\UpdateTeamAction::class)->setName('update-team');
            $group->post('/{teamId}/update', \TailgateWeb\Actions\Team\UpdateTeamAction::class);
            $group->get('/{teamId}/delete', \TailgateWeb\Actions\Team\DeleteTeamAction::class)->setName('delete-team');
            $group->get('/{teamId}/follow', \TailgateWeb\Actions\Team\AdminFollowTeamAction::class)->setName('admin-follow-team');
            $group->post('/{teamId}/follow', \TailgateWeb\Actions\Team\AdminFollowTeamAction::class);
            $group->get('/{teamId}/{groupId}/follow/{followId}/delete', \TailgateWeb\Actions\Team\AdminDeleteFollowAction::class)->setName('admin-delete-follow');
        });

        // season
        $group->group('/season', function (Group $group) {
            $group->get('', \TailgateWeb\Actions\Season\AllSeasonsAction::class)->setName('seasons');
            $group->get('/create', \TailgateWeb\Actions\Season\CreateSeasonAction::class)->setName('create-season');
            $group->post('/create', \TailgateWeb\Actions\Season\CreateSeasonAction::class);
            $group->get('/{seasonId}', \TailgateWeb\Actions\Season\ViewSeasonAction::class)->setName('season');
            $group->get('/{seasonId}/update', \TailgateWeb\Actions\Season\UpdateSeasonAction::class)->setName('update-season');
            $group->post('/{seasonId}/update', \TailgateWeb\Actions\Season\UpdateSeasonAction::class);
            $group->get('/{seasonId}/delete', \TailgateWeb\Actions\Season\DeleteSeasonAction::class)->setName('delete-season');
            $group->get('/{seasonId}/add-game', \TailgateWeb\Actions\Season\AddGameAction::class)->setName('add-game');
            $group->post('/{seasonId}/add-game', \TailgateWeb\Actions\Season\AddGameAction::class);
            $group->get('/{seasonId}/game/{gameId}/score', \TailgateWeb\Actions\Season\UpdateGameScoreAction::class)->setName('update-game-score');
            $group->post('/{seasonId}/game/{gameId}/score',\TailgateWeb\Actions\Season\UpdateGameScoreAction::class);
            $group->get('/{seasonId}/game/{gameId}/delete', \TailgateWeb\Actions\Season\DeleteGameAction::class)->setName('delete-game');
        });

    })->add(MustBeSignedInMiddleware::class)->add(\TailgateWeb\Middleware\AdminMiddleware::class);
};