<?php

namespace TailgateWeb\Actions\User;

use Psr\Http\Message\ResponseInterface;
use TailgateWeb\Actions\AbstractAction;

// update user form
class UpdateUserAction extends AbstractAction
{   
    public function action() : ResponseInterface
    {
        $statuses = ['Active' => 'Active', 'Pending' => 'Pending', 'Deleted' => 'Deleted'];
        $roles = ['Normal' => 'Normal', 'Admin' => 'Admin'];

        extract($this->args);

        // get user
        $apiResponse = $this->apiClient->get("/v1/admin/users/{$userId}");
        $data = $apiResponse->getData();
        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/user/update.twig');
        }
        $user = $data['data'];

        if ('GET' == $this->request->getMethod()) {
            return $this->view->render($this->response, 'admin/user/update.twig', compact('userId', 'user', 'statuses', 'roles'));
        }

        $parsedBody = $this->request->getParsedBody();

        $apiResponse = $this->apiClient->patch("/v1/admin/users/{$userId}", [
            'email' => $parsedBody['email'],
            'status' => $parsedBody['status'],
            'role' => $parsedBody['role'],
        ]);
        $data = $apiResponse->getData();

        if ($apiResponse->hasErrors()) {
            return $this->view->render($this->response, 'admin/user/update.twig', [
                'errors' => $data['errors'],
                'userId' => $userId,
                'user' => $user,
                'statuses' => $statuses,
                'roles' => $roles,
            ]);
        }

        $sessionUser = $this->session->get('user');

        // if it's the logged in user, update the session info
        if ($sessionUser['userId'] == $user['userId']) {
            $sessionUser['email'] = $parsedBody['email'];
            $sessionUser['status'] = $parsedBody['status'];
            $sessionUser['role'] = $parsedBody['role'];
            $this->session->set('user', $sessionUser);
        }

        return $this->response->withHeader('Location', "/admin/users/{$userId}")->withStatus(302);
    }
}