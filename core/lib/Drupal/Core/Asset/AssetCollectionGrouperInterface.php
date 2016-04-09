<?php

namespace Drupal\Core\Asset;

/**
 * Interface defining a service that logically groups a collection of assets.
 */
interface AssetCollectionGrouperInterface {

  /**
   * Groups a collection of assets into logical groups of asset collections.
   *
   * @param array $assets
   *   An asset collection.
   *
   * @return array
   *   A sorted array of asset groups.
   */
  public function group(array $assets);

}
