<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

/**
 * Handles options requests.
 *
 * Listens to KernelEvents::REQUEST and responds to OPTIONS requests by
 * providing an Allow header listing all the HTTP methods allowed for the
 * requested routes.
 */
class OptionsRequestSubscriber implements EventSubscriberInterface {

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Creates a new OptionsRequestSubscriber instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct(RouteProviderInterface $route_provider) {
    $this->routeProvider = $route_provider;
  }

  /**
   * Tries to handle the options request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    if ($event->getRequest()->isMethod('OPTIONS')) {
      $routes = $this->routeProvider->getRouteCollectionForRequest($event->getRequest());
      // In case we don't have any routes, a 403 should be thrown by the normal
      // request handling.
      if (count($routes) > 0) {
        // Flatten and unique the available methods.
        $methods = array_reduce($routes->all(), function ($methods, Route $route) {
          return array_merge($methods, $route->getMethods());
        }, []);
        $methods = array_unique($methods);
        $response = new Response('', 200, ['Allow' => implode(', ', $methods)]);
        $event->setResponse($response);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Set a high priority so it is executed before routing.
    $events[KernelEvents::REQUEST][] = ['onRequest', 1000];
    return $events;
  }

}
