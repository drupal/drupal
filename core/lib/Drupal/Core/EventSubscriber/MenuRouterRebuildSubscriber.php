<?php

/**
 * @file
 * Contains \Drupal\Core\EventSubscriber\MenuRouterRebuildSubscriber.
 */

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Rebuilds the default menu links and runs menu-specific code if necessary.
 */
class MenuRouterRebuildSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   */
  protected $menuLinkManager;

  /**
   * Constructs the MenuRouterRebuildSubscriber object.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   */
  public function __construct(LockBackendInterface $lock, MenuLinkManagerInterface $menu_link_manager) {
    $this->lock = $lock;
    $this->menuLinkManager = $menu_link_manager;
  }

  /**
   * Rebuilds the menu links and deletes the local_task cache tag.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The event object.
   */
  public function onRouterRebuild(Event $event) {
    $this->menuLinksRebuild();
    Cache::deleteTags(array('local_task'));
  }

  /**
   * Perform menu-specific rebuilding.
   */
  protected function menuLinksRebuild() {
    if ($this->lock->acquire(__FUNCTION__)) {
      $transaction = db_transaction();
      try {
        // Ensure the menu links are up to date.
        $this->menuLinkManager->rebuild();
        // Ignore any database replicas temporarily.
        db_ignore_replica();
      }
      catch (\Exception $e) {
        $transaction->rollback();
        watchdog_exception('menu', $e);
      }

      $this->lock->release(__FUNCTION__);
    }
    else {
      // Wait for another request that is already doing this work.
      // We choose to block here since otherwise the router item may not
      // be available during routing resulting in a 404.
      $this->lock->wait(__FUNCTION__);
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[RoutingEvents::FINISHED][] = array('onRouterRebuild', 200);
    return $events;
  }

}
