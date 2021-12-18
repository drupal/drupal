<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to set the '4xx-response' cache tag on 4xx responses.
 */
class ClientErrorResponseSubscriber implements EventSubscriberInterface {

  /**
   * Sets the '4xx-response' cache tag on 4xx responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    if ($response->isClientError()) {
      $http_4xx_response_cacheability = new CacheableMetadata();
      $http_4xx_response_cacheability->setCacheTags(['4xx-response']);
      $response->addCacheableDependency($http_4xx_response_cacheability);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Priority 10, so that it runs before FinishResponseSubscriber, which will
    // expose the cacheability metadata in the form of headers.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 10];
    return $events;
  }

}
