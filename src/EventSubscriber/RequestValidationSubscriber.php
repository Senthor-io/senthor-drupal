<?php

namespace Drupal\senthor_io\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Routing\AdminContext;
use Drupal\senthor_io\Service\SenthorApiClient;

class RequestValidationSubscriber implements EventSubscriberInterface
{
    protected $apiClient;
    protected $adminContext;

    public function __construct(SenthorApiClient $api_client, AdminContext $admin_context) {
        $this->apiClient = $api_client;
        $this->adminContext = $admin_context;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
        ];
    }

    public function onRequest(RequestEvent $event) {
        $request = $event->getRequest();

        // Filters requests to analyse
        if (!$event->isMainRequest()) { return; }
        if ($request->getMethod() !== 'GET') { return; }
        if ($this->adminContext->isAdminRoute() || str_starts_with($request->getPathInfo(), '/admin')) { return; }
        if ($request->isXmlHttpRequest()) { return; }

        // Collect data
        $headers = $request->headers->all();
        $uri = $request->getUri();
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

        if ($api_response['status'] === 402) {
            $response = new Response(
                $api_response['body'],
                402
            );
            if (!empty($api_response['headers']) && is_array($api_response['headers'])) {
                foreach ($api_response['headers'] as $name => $value) {
                    $response->headers->set($name, $value);
                }
            }
            $event->setResponse($response);
        }
    }
}
