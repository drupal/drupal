<?php

namespace Drupal\migrate_cckfield_plugin_manager_test\Plugin\migrate\cckfield;

use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateCckField(
 *   id = "d6_no_core_version_specified"
 * )
 */
class D6NoCoreVersionSpecified extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {}

}
