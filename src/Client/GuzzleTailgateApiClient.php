<?php

namespace TailgateWeb\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Session\SessionHelperInterface;

class GuzzleTailgateApiClient implements TailgateApiClientInterface
{
    protected $client;
    protected $session;
    public $config;

    public function __construct(Client $client, SessionHelperInterface $session, array $config)
    {
        $this->client = $client;
        $this->session = $session;
        $this->config = $config;
    }

    public function get(string $path, array $queryStringArray = [])
    {
        return $this->send('GET', $path, [
            'headers' => $this->getHeaders(),
            'query' => $queryStringArray
        ]);
    }

    public function post(string $path, array $data)
    {
        return $this->send('POST', $path, [
            'headers' => $this->getHeaders(),
            'json' => $data,
        ]);
    }

    public function put(string $path, array $data)
    {
        return $this->send('PUT', $path, [
            'headers' => $this->getHeaders(),
            'json' => $data,
        ]);
    }

    public function patch(string $path, array $data)
    {
        return $this->send('PATCH', $path, [
            'headers' => $this->getHeaders(),
            'json' => $data,
        ]);
    }

    public function delete(string $path)
    {
        return $this->send('DELETE', $path, [
            'headers' => $this->getHeaders()
        ]);
    }

    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->session->get('tokens')['access_token']
        ];
    }

    private function send(string $verb, string $path, array $data)
    {
        try {

            return $this->client->request($verb, $path, $data);

        // } catch (TransferException $e) {
        } catch (ClientException $e) {
        // } catch (\Exception $e) {

            // dd($e->getResponse()->getStatusCode());

            if ($e->hasResponse()) {
                $response = $e->getResponse();

                $jsonBody = json_decode($response->getBody()->getContents(), true);
                $newBody = (new \Slim\Psr7\Factory\StreamFactory())->createStream();

                // if this is a server error then we need to translate
                // the uglier response into the consistent one
                if ($response->getStatusCode() >= 404) {

                    $statusCode = $jsonBody['exception'][0]['code'] ?? $response->getStatusCode();
                    $statusCode = $statusCode >= 100 ? $statusCode : $response->getStatusCode();

                    $newBody->write(json_encode([
                        'code' => $statusCode,
                        'type' => $jsonBody['exception'][0]['type'],
                        'errors' => $jsonBody['message'],
                    ]));
                    $newBody->rewind();
                    $this->flash->addMessageNow('error', $jsonBody['message']);
                    $response = $response->withBody($newBody)->withStatus($statusCode);
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

            // if the api server is down or soemthing else fails we need to create a response ourself
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