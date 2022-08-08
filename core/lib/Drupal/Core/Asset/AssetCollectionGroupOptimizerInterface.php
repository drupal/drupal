<?php

namespace Drupal\Core\Asset;

/**
 * Interface defining a service that optimizes a collection of assets.
 *
 * Contains an additional method to allow for optimizing an asset group.
 */
interface AssetCollectionGroupOptimizerInterface extends AssetCollectionOptimizerInterface {

  /**
   * Optimizes a specific group of assets.
   *
   * @param array $group
   *   An asset group.
   *
   * @return string
   *   The optimized string for the group.
   */
  public function optimizeGroup(array $group): string;

}
