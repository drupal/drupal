<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\CckFieldMigrateSourceInterface.
 */

namespace Drupal\migrate_drupal\Plugin;

use Drupal\migrate\Plugin\MigrateSourceInterface;

/**
 * Defines an interface for cck field sources that need per type processing.
 */
interface CckFieldMigrateSourceInterface extends MigrateSourceInterface {

  /**
   * Field data used for determining the field type in the LoadEntity
   *
   * @return mixed
   *   An array of cck field data.
   */
  public function fieldData();
}
