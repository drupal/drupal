<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Clear cache tags when the router is rebuilt.
 */
class CacheRouterRebuildSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onRouterFinished() {
    // Requested URLs that formerly gave a 403/404 may now be valid.
    // Also invalidate all cached routing as well as every HTTP response.
    Cache::invalidateTags(['4xx-response', 'route_match', 'http_response']);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    // Act only when the router rebuild is finished.
    $events[RoutingEvents::FINISHED][] = ['onRouterFinished', 200];
    return $events;
  }

}
