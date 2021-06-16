<?php

namespace Drupal\migrate\Plugin;

use Drupal\migrate\Event\ImportAwareInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\RollbackAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to forward Migrate events to source and destination plugins.
 */
class PluginEventSubscriber implements EventSubscriberInterface {

  /**
   * Tries to invoke event handling methods on source and destination plugins.
   *
   * @param string $method
   *   The method to invoke.
   * @param \Drupal\migrate\Event\MigrateImportEvent|\Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The event that has triggered the invocation.
   * @param string $plugin_interface
   *   The interface which plugins must implement in order to be invoked.
   */
  protected function invoke($method, $event, $plugin_interface) {
    $migration = $event->getMigration();

    $source = $migration->getSourcePlugin();
    if ($source instanceof $plugin_interface) {
      call_user_func([$source, $method], $event);
    }

    $destination = $migration->getDestinationPlugin();
    if ($destination instanceof $plugin_interface) {
      call_user_func([$destination, $method], $event);
    }
  }

  /**
   * Forwards pre-import events to the source and destination plugins.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event.
   */
  public function preImport(MigrateImportEvent $event) {
    $this->invoke('preImport', $event, ImportAwareInterface::class);
  }

  /**
   * Forwards post-import events to the source and destination plugins.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event.
   */
  public function postImport(MigrateImportEvent $event) {
    $this->invoke('postImport', $event, ImportAwareInterface::class);
  }

  /**
   * Forwards pre-rollback events to the source and destination plugins.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The rollback event.
   */
  public function preRollback(MigrateRollbackEvent $event) {
    $this->invoke('preRollback', $event, RollbackAwareInterface::class);
  }

  /**
   * Forwards post-rollback events to the source and destination plugins.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The rollback event.
   */
  public function postRollback(MigrateRollbackEvent $event) {
    $this->invoke('postRollback', $event, RollbackAwareInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::PRE_IMPORT][] = ['preImport'];
    $events[MigrateEvents::POST_IMPORT][] = ['postImport'];
    $events[MigrateEvents::PRE_ROLLBACK][] = ['preRollback'];
    $events[MigrateEvents::POST_ROLLBACK][] = ['postRollback'];

    return $events;
  }

}
