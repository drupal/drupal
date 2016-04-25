<?php

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

  /**
   * Returns all optimized asset collections assets.
   *
   * @return string[]
   *   URIs for all optimized asset collection assets.
   */
  public function getAll();

  /**
   * Deletes all optimized asset collections assets.
   */
  public function deleteAll();

}
