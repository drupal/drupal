<?php

namespace Drupal\Core\Cache\EventSubscriber;

use Drupal\Core\Cache\CacheTagsChecksumInterface;
use Drupal\Core\Cache\CacheTagsChecksumPreloadInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Preloads frequently used cache tags.
 */
class CacheTagPreloadSubscriber implements EventSubscriberInterface {

  public function __construct(protected CacheTagsChecksumInterface $cacheTagsChecksum) {
  }

  /**
   * Preloads frequently used cache tags.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event): void {
    if ($event->isMainRequest() && $this->cacheTagsChecksum instanceof CacheTagsChecksumPreloadInterface) {
      $default_preload_cache_tags = array_merge([
        'route_match',
        'access_policies',
        'routes',
        'router',
        'entity_types',
        'entity_field_info',
        'entity_bundles',
        'local_task',
        'library_info',
      ], Settings::get('cache_preload_tags', []));
      $this->cacheTagsChecksum->registerCacheTagsForPreload($default_preload_cache_tags);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest', 500];
    return $events;
  }

}
