<?php

namespace Drupal\Core\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\ReplicaKillSwitch;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Rebuilds the default menu links and runs menu-specific code if necessary.
 */
class MenuRouterRebuildSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The menu link plugin manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The replica kill switch.
   *
   * @var \Drupal\Core\Database\ReplicaKillSwitch
   */
  protected $replicaKillSwitch;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs the MenuRouterRebuildSubscriber object.
   *
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   The lock backend.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link plugin manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Database\ReplicaKillSwitch $replica_kill_switch
   *   The replica kill switch.
   * @param \Psr\Log\LoggerInterface|null $logger
   *   The logger.
   */
  public function __construct(LockBackendInterface $lock, MenuLinkManagerInterface $menu_link_manager, Connection $connection, ReplicaKillSwitch $replica_kill_switch, protected ?LoggerInterface $logger = NULL) {
    $this->lock = $lock;
    $this->menuLinkManager = $menu_link_manager;
    $this->connection = $connection;
    $this->replicaKillSwitch = $replica_kill_switch;
    if ($this->logger === NULL) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in drupal:10.1.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/2932520', E_USER_DEPRECATED);
      $this->logger = \Drupal::service('logger.channel.menu');
    }
  }

  /**
   * Rebuilds the menu links and deletes the local_task cache tag.
   *
   * @param \Drupal\Component\EventDispatcher\Event $event
   *   The event object.
   */
  public function onRouterRebuild($event) {
    $this->menuLinksRebuild();
    Cache::invalidateTags(['local_task']);
  }

  /**
   * Perform menu-specific rebuilding.
   */
  protected function menuLinksRebuild() {
    if ($this->lock->acquire(__FUNCTION__)) {
      try {
        $transaction = $this->connection->startTransaction();
        // Ensure the menu links are up to date.
        $this->menuLinkManager->rebuild();
        // Ignore any database replicas temporarily.
        $this->replicaKillSwitch->trigger();
      }
      catch (\Exception $e) {
        if (isset($transaction)) {
          $transaction->rollBack();
        }
        Error::logException($this->logger, $e);
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
  public static function getSubscribedEvents(): array {
    // Run after CachedRouteRebuildSubscriber.
    $events[RoutingEvents::FINISHED][] = ['onRouterRebuild', 100];
    return $events;
  }

}
