<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a revisionable entity bundle.
 */
interface RevisionableEntityBundleInterface extends ConfigEntityInterface {

  /**
   * Gets whether a new revision should be created by default.
   *
   * @return bool
   *   TRUE if a new revision should be created by default.
   */
  public function shouldCreateNewRevision();

}
