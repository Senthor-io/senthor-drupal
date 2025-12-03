<?php

namespace Drupal\senthor_io\Middleware;

use Drupal\senthor_io\Service\SenthorApiClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware that validates incoming requests using the Senthor API service.
 *
 * This middleware inspects GET requests to non-admin paths, filters sensitive
 * headers, sends the request details to Senthor for validation, and either
 * returns a response from Senthor or continues normal request handling.
 */
class SenthorMiddleware implements HttpKernelInterface {

  protected HttpKernelInterface $httpKernel;
  protected SenthorApiClient $apiClient;

  public function __construct(HttpKernelInterface $kernel, SenthorApiClient $api_client) {
    $this->httpKernel = $kernel;
    $this->apiClient = $api_client;
  }

  /**
   * Constructs a new SenthorMiddleware object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The decorated kernel.
   * @param \Drupal\senthor\Api\SenthorApiClient $api_client
   *   The Senthor API client.
   */
  public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = TRUE): Response {
    // Filter requests.
    if ($type !== self::MAIN_REQUEST || $request->getMethod() !== 'GET') {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Admin context isn't loaded yet.
    if (str_starts_with($request->getPathInfo(), '/admin')) {
      return $this->httpKernel->handle($request, $type, $catch);
    }

    // Build body.
    $headers = $request->headers->all();
    $uri = $request->getRequestUri();
    $ip = $request->getClientIp();

    $sensitive_headers = [
      'authorization',
      'cookie',
      'set-cookie',
      'x-csrf-token',
    ];
    $filtered_headers = array_filter($headers, function ($key) use ($sensitive_headers) {
        return !in_array(strtolower($key), $sensitive_headers, TRUE);
    }, ARRAY_FILTER_USE_KEY);

    $api_response = $this->apiClient->validateRequest($filtered_headers, $uri, $ip);

    if (!empty($api_response['status']) && $api_response['status'] === 402) {
      $response = new Response(
            $api_response['body'] ?? '',
            402
        );

      if (!empty($api_response['headers']) && is_array($api_response['headers'])) {
        foreach ($api_response['headers'] as $name => $value) {
          $response->headers->set($name, $value);
        }
      }

      return $response;
    }

    // Otherwise, continue normally.
    return $this->httpKernel->handle($request, $type, $catch);
  }

}
