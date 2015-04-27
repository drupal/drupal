<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\AnonymousUserResponseSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Response subscriber to handle finished responses for the anonymous user.
 */
class AnonymousUserResponseSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs an AnonymousUserResponseSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * Adds a cache tag if the 'user.permissions' cache context is present.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespond(FilterResponseEvent $event) {
    if (!$event->isMasterRequest()) {
      return;
    }

    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // The 'user.permissions' cache context ensures that if the permissions for
    // a role are modified, users are not served stale render cache content.
    // But, when entire responses are cached in reverse proxies, the value for
    // the cache context is never calculated, causing the stale response to not
    // be invalidated. Therefore, when varying by permissions and the current
    // user is the anonymous user, also add the cache tag for the 'anonymous'
    // role.
    if (in_array('user.permissions', $response->getCacheableMetadata()->getCacheContexts())) {
      $per_permissions_response_for_anon = new CacheableMetadata();
      $per_permissions_response_for_anon->setCacheTags(['config:user.role.anonymous']);
      $response->addCacheableDependency($per_permissions_response_for_anon);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    // Priority 5, so that it runs before FinishResponseSubscriber, but after
    // event subscribers that add the associated cacheability metadata (which
    // have priority 10). This one is conditional, so must run after those.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 5];
    return $events;
  }

}
