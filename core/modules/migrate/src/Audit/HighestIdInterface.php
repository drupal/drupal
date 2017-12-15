<?php

namespace Drupal\migrate\Audit;

/**
 * Defines an interface for destination and ID maps which track a highest ID.
 *
 * When implemented by destination plugins, getHighestId() should return the
 * highest ID of the destination entity type that exists in the system. So, for
 * example, the entity:node plugin should return the highest node ID that
 * exists, regardless of whether it was created by a migration.
 *
 * When implemented by an ID map, getHighestId() should return the highest
 * migrated ID of the destination entity type.
 */
interface HighestIdInterface {

  /**
   * Returns the highest ID tracked by the implementing plugin.
   *
   * @return int
   *   The highest ID.
   */
  public function getHighestId();

}
