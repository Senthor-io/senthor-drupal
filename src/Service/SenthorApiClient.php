<?php

namespace Drupal\senthor_io\Service;

use GuzzleHttp\ClientInterface;

class SenthorApiClient {
    protected $httpClient;

    protected const API_URL = 'https://waf-api.senthor.io/api/check-request';

    public function __construct(ClientInterface $http_client) {
        $this->httpClient = $http_client;
    }

    public function validateRequest(array $headers, string $uri, string $ip) {
        try {
            $response = $this->httpClient->post(self::API_URL, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode([
                    'headers' => $headers,
                    'request_url' => $uri,
                    'client_ip' => $ip,
                ]),
                'timeout' => 2,
                'http_errors' => false,
            ]);

            $crawlerHeaders = [];
            foreach ($response->getHeaders() as $name => $values) {
                if (stripos($name, 'crawler-') === 0) {
                    $crawlerHeaders[$name] = implode(', ', $values);
                }
            }

            return [
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getBody(),
                'headers' => $crawlerHeaders,
            ];
        }
        catch (\Exception $e) {
            return ['status' => 500, 'body' => '', 'error' => $e->getMessage()];
        }
    }
}
