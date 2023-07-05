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
   * @param array $libraries
   *   An array of library names.
   *
   * @return array
   *   An optimized asset collection.
   */
  public function optimize(array $assets, array $libraries);

  /**
   * Returns all optimized asset collections assets.
   *
   * @return string[]
   *   URIs for all optimized asset collection assets.
   *
   * @deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is
   *   no replacement.
   *
   * @see https://www.drupal.org/node/3301744
   */
  public function getAll();

  /**
   * Deletes all optimized asset collections assets.
   */
  public function deleteAll();

}
