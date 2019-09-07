<?php

namespace TailgateWeb\Controllers;

use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Container\ContainerInterface;
use Slim\Psr7\Response;

abstract class AbstractController
{
    protected $settings;
    protected $logger;
    protected $flash;
    protected $session;
    protected $client;
    protected $view;
    protected $mailer;

    public function __construct(ContainerInterface $container)
    {
        $this->settings = $container->get('settings');
        $this->logger = $container->get('logger');
        $this->flash = $container->get('flash');
        $this->session = $container->get('session');
        $this->client = $container->get('guzzleClient');
        $this->view = $container->get('view');
        $this->mailer = $container->get('mailer');
    }

    /**
     * [apiGet description]
     * @param  string $path             [description]
     * @param  array  $queryStringArray [description]
     * @return [type]                   [description]
     */
    public function apiGet(string $path, array $queryStringArray = [])
    {
        return $this->send('GET', $path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->tokens['access_token']
            ],
            'query' => $queryStringArray
        ]);
    }

    /**
     * [apiPost description]
     * @param  string $path [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function apiPost(string $path, array $data)
    {        
        return $this->send('POST', $path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->tokens['access_token']
            ],
            'json' => $data,
        ]);
    }

    /**
     * [apiPut description]
     * @param  string $path [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function apiPut(string $path, array $data)
    {        
        return $this->send('PUT', $path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->tokens['access_token']
            ],
            'json' => $data,
        ]);
    }

    /**
     * [apiPatch description]
     * @param  string $path [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function apiPatch(string $path, array $data)
    {        
        return $this->send('PATCH', $path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->tokens['access_token']
            ],
            'json' => $data,
        ]);
    }

    /**
     * [apiDelete description]
     * @param  string $path [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function apiDelete(string $path)
    {        
        return $this->send('DELETE', $path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->tokens['access_token']
            ]
        ]);
    }

    /**
     * [send description]
     * @param  string $verb [description]
     * @param  string $path [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    private function send(string $verb, string $path, array $data)
    {
        try {

            return $this->client->request($verb, $path, $data);

        } catch (TransferException $e) {

            // var_dump($e->getResponse()->getStatusCode());
            // die();

            if ($e->hasResponse()) {
                $response = $e->getResponse();

                $jsonBody = json_decode($response->getBody()->getContents(), true);
                $newBody = (new \Slim\Psr7\Factory\StreamFactory())->createStream();

                // if this is a server error then we need to translate
                // the uglier response into the consistent one
                if ($response->getStatusCode() >= 500) {

                    $newBody->write(json_encode([
                        'code' => $response->getStatusCode(),
                        'type' => $jsonBody['exception'][0]['type'],
                        'errors' => $jsonBody['message'],
                    ]));
                    $newBody->rewind();
                    $this->flash->addMessageNow('error', $jsonBody['message']);
                    $response = $response->withBody($newBody);
                }

                // /token on api will return ['error' => foo, 'error_description' => bar]
                // or bad client credentials
                // let's make it consistent
                if (
                    ($response->getStatusCode() == 401 && isset($jsonBody['error']))
                    || ($response->getStatusCode() == 400 && isset($jsonBody['error']))
                ) {
                    
                    $newBody->write(json_encode([
                        'code' => $response->getStatusCode(),
                        'type' => $jsonBody['error'],
                        'errors' => $jsonBody['error_description'],
                    ]));
                    $newBody->rewind();
                    $this->flash->addMessageNow('error', $jsonBody['error_description']);
                    $response = $response->withBody($newBody);
                }

                return $response;
            }

            $response = new Response;

            $newBody = (new \Slim\Psr7\Factory\StreamFactory())->createStream();

            $newBody->write(json_encode([
                'code' => 500,
                'type' => 'connection_failed',
                'errors' => ['server'=> [$e->getMessage()]],
            ]));
            $newBody->rewind();

            $response = $response->withBody($newBody);

            $this->flash->addMessageNow('error', $e->getMessage());

            return $response->withStatus(500);
        }

    }
}