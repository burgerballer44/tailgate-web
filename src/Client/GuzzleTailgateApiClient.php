<?php

namespace TailgateWeb\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Flash\Messages;
use TailgateWeb\Client\ApiResponseInterface;
use TailgateWeb\Client\TailgateApiClientInterface;
use TailgateWeb\Session\SessionHelperInterface;

class GuzzleTailgateApiClient implements TailgateApiClientInterface
{
    protected $client;
    protected $session;
    protected $responseFactory;
    protected $flash;
    protected $logger;

    public function __construct(
        Client $client,
        SessionHelperInterface $session,
        ResponseFactoryInterface $responseFactory,
        Messages $flash,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->session = $session;
        $this->responseFactory = $responseFactory;
        $this->flash = $flash;
        $this->logger = $logger;
    }

    public function get(string $path, array $queryStringArray = []) : ApiResponseInterface
    {
        return $this->send('GET', $path, [
            'headers' => $this->getHeaders(),
            'query' => $queryStringArray
        ]);
    }

    public function post(string $path, array $data) : ApiResponseInterface
    {
        return $this->send('POST', $path, [
            'headers' => $this->getHeaders(),
            'json' => $data,
        ]);
    }

    public function put(string $path, array $data) : ApiResponseInterface
    {
        return $this->send('PUT', $path, [
            'headers' => $this->getHeaders(),
            'json' => $data,
        ]);
    }

    public function patch(string $path, array $data) : ApiResponseInterface
    {
        return $this->send('PATCH', $path, [
            'headers' => $this->getHeaders(),
            'json' => $data,
        ]);
    }

    public function delete(string $path) : ApiResponseInterface
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
        $apiData = [];
        $apiData['errors'] = [];

        try {

            $response = $this->client->request($verb, $path, $data);
            $contents = $response->getBody()->getContents();
            if ("" != $contents) {
                $apiData = json_decode($contents, true);
            }

        // 400 level exceptions
        } catch (ClientException $e) {

            // get the response and decode the body
            $response = $e->getResponse();
            $jsonBody = json_decode($response->getBody()->getContents(), true);

            // /token on api will return ['error' => foo, 'error_description' => bar]
            // or bad client credentials
            // let's make it consistent
            if (
                ($response->getStatusCode() == 401 && isset($jsonBody['error']))
                || ($response->getStatusCode() == 400 && isset($jsonBody['error']))
            ) {
                $apiData['errors'] = $jsonBody['error_description'];
                $this->flash->addMessageNow('error', $apiData['errors']);
            } else {
                if (isset($jsonBody['errors'])) {
                    $apiData['errors'] = $jsonBody['errors'];
                } else {
                    $apiData['errors'] = $jsonBody['exception'][0]['message'] ?? $jsonBody['message'] ?? 'Unspecified 400 API error occurred';
                    $this->flash->addMessageNow('error', $e->getMessage());
                }
            }

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
                $message = $jsonBody['exception'][0]['message'] ?? $jsonBody['message'] ?? 'Unspecified 500 API error occurred';

                // if this is a server error then we need to translate
                // the uglier response into the consistent one
                if ($response->getStatusCode() >= 404) {
                    $apiData['errors'] = $message;
                    $this->flash->addMessageNow('error', $message);
                }

                $this->logger->error($message);
            } else {
                $apiData['errors'] = ['server'=> ['Unspecified error occurred']];   
                $this->flash->addMessageNow('error', $e->getMessage());
            }
        }

        return new TailgateApiResponse($apiData);
    }
}