<?php

namespace Drupal\senthor_io\Middleware;

use Drupal\Core\Routing\AdminContext;
use Drupal\senthor_io\Service\SenthorApiClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;

class SenthorMiddleware implements HttpKernelInterface {

    protected HttpKernelInterface $httpKernel;
    protected AdminContext $adminContext;
    protected SenthorApiClient $apiClient;

    public function __construct(HttpKernelInterface $kernel, AdminContext $admin_context, SenthorApiClient $api_client) {
        $this->httpKernel = $kernel;
        $this->adminContext = $admin_context;
        $this->apiClient = $api_client;
    }

    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response {
        // Filter requests
        if ($type !== self::MAIN_REQUEST || $request->getMethod() !== 'GET') {
            return $this->httpKernel->handle($request, $type, $catch);
        }

        if ($this->adminContext->isAdminRoute() || str_starts_with($request->getPathInfo(), '/admin')) {
            return $this->httpKernel->handle($request, $type, $catch);
        }

        // Build body
        $headers = $request->headers->all();
        $uri = $request->getRequestUri();
        $ip = $request->getClientIp();

        $sensitive_headers = [
            'authorization',
            'cookie',
            'set-cookie',
            'x-csrf-token',
        ];
        $filtered_headers = array_filter($headers, function($key) use ($sensitive_headers) {
            return !in_array(strtolower($key), $sensitive_headers, true);
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

        // Otherwise, continue normally
        return $this->httpKernel->handle($request, $type, $catch);
    }
}
