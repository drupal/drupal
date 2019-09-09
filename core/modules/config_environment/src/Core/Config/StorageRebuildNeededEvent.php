<?php

namespace Drupal\config_environment\Core\Config;

use Symfony\Component\EventDispatcher\Event;

/**
 * The dispatched by a storage manager to check if a rebuild is needed.
 */
class StorageRebuildNeededEvent extends Event {

  /**
   * The flag which keeps track of whether the storage needs to be rebuilt.
   *
   * @var bool
   */
  private $rebuildNeeded = FALSE;

  /**
   * Flags to the config storage manager that a rebuild is needed.
   */
  public function setRebuildNeeded() {
    $this->rebuildNeeded = TRUE;
    $this->stopPropagation();
  }

  /**
   * Returns whether the storage needs to be rebuilt or not.
   *
   * @return bool
   *   Whether the rebuild is needed or not.
   */
  public function isRebuildNeeded() {
    return $this->rebuildNeeded;
  }

}
