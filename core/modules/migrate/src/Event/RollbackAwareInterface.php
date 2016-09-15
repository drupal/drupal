<?php

namespace Drupal\migrate\Event;

/**
 * Interface for plugins that react to pre- or post-rollback events.
 */
interface RollbackAwareInterface {

  /**
   * Performs pre-rollback tasks.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The pre-rollback event object.
   */
  public function preRollback(MigrateRollbackEvent $event);

  /**
   * Performs post-rollback tasks.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The post-rollback event object.
   */
  public function postRollback(MigrateRollbackEvent $event);

}
