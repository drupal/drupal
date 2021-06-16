<?php

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

  /**
   * Removes unwanted content from an asset.
   *
   * @param string $content
   *   The content of an asset.
   *
   * @return string
   *   The cleaned asset's contents.
   */
  public function clean($content);

}
