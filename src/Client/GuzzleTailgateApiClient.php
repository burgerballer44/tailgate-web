<?php

namespace TailgateWeb\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Flash\Messages;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Session\SessionHelperInterface;

class GuzzleTailgateApiClient implements TailgateApiClientInterface
{
    protected $client;
    protected $session;
    protected $responseFactory;
    protected $flash;
    protected $logger;
    public $config;

    public function __construct(
        Client $client,
        SessionHelperInterface $session,
        ResponseFactoryInterface $responseFactory,
        Messages $flash,
        LoggerInterface $logger,
        array $config
    ) {
        $this->client = $client;
        $this->session = $session;
        $this->responseFactory = $responseFactory;
        $this->flash = $flash;
        $this->logger = $logger;
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

        // 400 level exceptions
        } catch (ClientException $e) {

            // get the response and decode the body
            $response = $e->getResponse();
            $jsonBody = json_decode($response->getBody()->getContents(), true);

            // create a new body for the response
            $newBody = (new \Slim\Psr7\Factory\StreamFactory())->createStream();

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

        // all other exceptions
        } catch (TransferException $e) {

            $this->logger->error($e->getMessage());

            if ($e->hasResponse()) {
                // get the response and decode the body
                $response = $e->getResponse();
                $jsonBody = json_decode($response->getBody()->getContents(), true);

                $statusCode = $jsonBody['exception'][0]['code'] ?? $response->getStatusCode();
                $statusCode = $statusCode >= 100 ? $statusCode : $response->getStatusCode();
                $statusCode = $statusCode <= 600 ? $statusCode : $response->getStatusCode();

                $type = $jsonBody['exception'][0]['type'] ?? 'Unknown API Error';
                $message = $jsonBody['exception'][0]['message'] ?? $jsonBody['message'] ?? 'Unspecified API error occurred';

                // create a new body for the response
                $newBody = (new \Slim\Psr7\Factory\StreamFactory())->createStream();

                // if this is a server error then we need to translate
                // the uglier response into the consistent one
                if ($response->getStatusCode() >= 404) {

                    $newBody->write(json_encode([
                        'code' => $statusCode,
                        'type' => $type,
                        'errors' => $message,
                    ]));
                    $newBody->rewind();
                    $this->flash->addMessageNow('error', $message);
                    $response = $response->withBody($newBody)->withStatus($statusCode);
                }

                $this->logger->error($message);

                return $response;
            }

            // if for whatever reason there is no response from the exception then we need to create one
            $response = $this->responseFactory->createResponse();

            // create a new body for the response
            $newBody = (new \Slim\Psr7\Factory\StreamFactory())->createStream();

            $newBody->write(json_encode([
                'code' => 500,
                'type' => 'connection_failed',
                'errors' => ['server'=> ['Unspecified error occurred']],
            ]));
            $newBody->rewind();
            $response = $response->withBody($newBody);

            $this->flash->addMessageNow('error', $e->getMessage());

            return $response->withStatus(500);
        }
    }
}