<?php

namespace Drupal\Core\Entity;

/**
 * Provides a trait for accessing synchronization information.
 *
 * @ingroup entity_api
 */
trait SynchronizableEntityTrait {

  /**
   * Is entity being created updated or deleted through synchronization process.
   *
   * @var bool
   */
  protected $isSyncing = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setSyncing($syncing) {
    $this->isSyncing = $syncing;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isSyncing() {
    return $this->isSyncing;
  }

}
