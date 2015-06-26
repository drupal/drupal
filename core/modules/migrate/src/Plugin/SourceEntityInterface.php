<?php
/**
 * @file
 * Contains \Drupal\migrate\Plugin\SourceEntityInterface.
 */

namespace Drupal\migrate\Plugin;

/**
 * Interface for sources providing an entity.
 */
interface SourceEntityInterface {

  /**
   * Whether this migration has a bundle migration.
   *
   * @return bool
   *   TRUE when the bundle_migration key is required.
   */
  public function bundleMigrationRequired();

  /**
   * The entity type id (user, node etc).
   *
   * This function is used when bundleMigrationRequired() is FALSE.
   *
   * @return string
   *   The entity type id.
   */
  public function entityTypeId();

}
