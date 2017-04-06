<?php

namespace Drupal\migrate_drupal\Plugin;

@trigger_error('MigrateCckFieldInterface is deprecated in Drupal 8.3.x and will
be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Annotation\MigrateField
instead.', E_USER_DEPRECATED);

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Provides an interface for all CCK field type plugins.
 *
 * @deprecated in Drupal 8.3.x, to be removed before Drupal 9.0.x. Use
 *   \Drupal\migrate_drupal\Annotation\MigrateField instead.
 */
interface MigrateCckFieldInterface extends MigrateFieldInterface {

  /**
   * Apply any custom processing to the cck bundle migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param string $field_name
   *   The field name we're processing the value for.
   * @param array $data
   *   The array of field data from CckFieldValues::fieldData().
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data);

}
