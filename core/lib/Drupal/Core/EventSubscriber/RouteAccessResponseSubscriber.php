<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to bubble the route's access result's cacheability.
 *
 * During routing, access checking is performed. The corresponding access result
 * is stored in the Request object's attributes, just like the matching route
 * object is. In case of a cacheable response, the route's access result also
 * determined the content of the response, and therefore the cacheability of the
 * route's access result should also be applied to the resulting response.
 *
 * @see \Drupal\Core\Routing\AccessAwareRouterInterface::ACCESS_RESULT
 * @see \Drupal\Core\Routing\AccessAwareRouter::matchRequest()
 * @see \Drupal\Core\Routing\AccessAwareRouter::checkAccess()
 */
class RouteAccessResponseSubscriber implements EventSubscriberInterface {

  /**
   * Bubbles the route's access result' cacheability metadata.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    $request = $event->getRequest();
    $access_result = $request->attributes->get(AccessAwareRouterInterface::ACCESS_RESULT);
    $response->addCacheableDependency($access_result);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Priority 10, so that it runs before FinishResponseSubscriber, which will
    // expose the cacheability metadata in the form of headers.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 10];
    return $events;
  }

}
