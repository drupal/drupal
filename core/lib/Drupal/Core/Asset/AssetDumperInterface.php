<?php

namespace Drupal\Core\Asset;

/**
 * Interface defining a service that dumps an (optimized) asset.
 */
interface AssetDumperInterface {

  /**
   * Dumps an (optimized) asset to persistent storage.
   *
   * @param string $data
   *   An (optimized) asset's contents.
   * @param string $file_extension
   *   The file extension of this asset.
   *
   * @return string
   *   A URI to access the dumped asset.
   */
  public function dump($data, $file_extension);

}
