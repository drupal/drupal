<?php

namespace Drupal\Core\Asset;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

/**
 * Provides a method to generate a normalized hash of a given asset group set.
 */
trait AssetGroupSetHashTrait {

  /**
   * Generates a hash for an array of asset groups.
   *
   * @param array $group
   *   An asset group.
   *
   * @return string
   *   A hash to uniquely identify the groups.
   */
  protected function generateHash(array $group): string {
    $normalized = [];
    $group_keys = [
      'type' => NULL,
      'group' => NULL,
      'media' => NULL,
      'browsers' => NULL,
    ];

    $normalized['asset_group'] = array_intersect_key($group, $group_keys);
    $normalized['asset_group']['items'] = [];
    // Remove some keys to make the hash more stable.
    $omit_keys = [
      'weight' => NULL,
    ];
    foreach ($group['items'] as $key => $asset) {
      $normalized['asset_group']['items'][$key] = array_diff_key($asset, $group_keys, $omit_keys);
    }
    // The asset array ensures that a valid hash can only be generated via the
    // same code base. Additionally use the hash salt to ensure that hashes are
    // not re-usable between different installations.
    return Crypt::hmacBase64(serialize($normalized), Settings::getHashSalt());
  }

}
