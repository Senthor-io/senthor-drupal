<?php

namespace Drupal\senthor_io\Service;

use GuzzleHttp\ClientInterface;

/**
 * Client service for interacting with the Senthor API.
 *
 * This service sends request details to the Senthor WAF API for validation
 * and returns structured responses including status, body, and any special
 * crawler-related headers.
 */
class SenthorApiClient {
  /**
   * The HTTP client used to send requests to the Senthor API.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  protected const API_URL = 'https://waf-api.senthor.io/api/verify-request';

  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Validates a request against the Senthor API.
   *
   * Sends the provided headers, request URI, and client IP to the Senthor API.
   * Returns the API response status, body, and any crawler-related headers.
   * On exception, returns status 500 and the error message.
   *
   * @param array $headers
   *   The request headers to send to the API.
   * @param string $uri
   *   The request URI to validate.
   * @param string $ip
   *   The client IP address.
   *
   * @return array
   *   An associative array with keys:
   *   - 'status': (int) HTTP status code returned by the API.
   *   - 'body': (string) Response body from the API.
   *   - 'headers': (array) Any crawler-related headers returned.
   *   - 'error': (string, optional) Error message if an exception occurred.
   */
  public function validateRequest(array $headers, string $uri, string $ip) {
    try {
      $response = $this->httpClient->post(self::API_URL, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => json_encode([
          'headers' => $headers,
          'request_url' => $uri,
          'client_ip' => $ip,
        ]),
        'timeout' => 10,
        'http_errors' => FALSE,
      ]);

      $restricted_headers = [
        'content-length',
        'transfer-encoding',
        'host',
        'connection'
      ];
      $crawlerHeaders = [];
      foreach ($response->getHeaders() as $name => $values) {
        if (in_array(strtolower($name), $restricted_headers)) {
            continue;
        }
        if (!is_array($values)) {
            $values = [$values];
        }
        $crawlerHeaders[$name] = implode(', ', $values);
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
