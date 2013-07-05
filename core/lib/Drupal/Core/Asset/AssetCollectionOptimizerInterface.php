<?php
/**
 * @file
 * Contains \Drupal\Core\Asset\AssetCollectionOptimizerInterface.
 */

namespace Drupal\Core\Asset;

/**
 * Interface defining a service that optimizes a collection of assets.
 */
interface AssetCollectionOptimizerInterface {

  /**
   * Optimizes a collection of assets.
   *
   * @param array $assets
   *   An asset collection.
   *
   * @return array
   *   An optimized asset collection.
   */
  public function optimize(array $assets);

}
