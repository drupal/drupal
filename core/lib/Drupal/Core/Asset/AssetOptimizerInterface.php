<?php
/**
 * @file
 * Contains \Drupal\Core\Asset\AssetCollectionOptimizerInterface.
 */

namespace Drupal\Core\Asset;

/**
 * Interface defining a service that optimizes an asset.
 */
interface AssetOptimizerInterface {

  /**
   * Optimizes an asset.
   *
   * @param array $asset
   *   An asset.
   *
   * @return string
   *   The optimized asset's contents.
   */
  public function optimize(array $asset);

}
