<?php

namespace Drupal\migrate_drupal\Plugin\migrate\cckfield;

@trigger_error('CckFieldPluginBase is deprecated in Drupal 8.3.x and will be be removed before Drupal 9.0.x. Use \Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase instead.', E_USER_DEPRECATED);

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;
use Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface;

/**
 * The base class for all field plugins.
 *
 * @deprecated in Drupal 8.4.x, to be removed before Drupal 9.0.x. Use
 * \Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase instead.
 *
 * @see https://www.drupal.org/node/2751897
 *
 * @ingroup migration
 */
abstract class CckFieldPluginBase extends FieldPluginBase implements MigrateCckFieldInterface {

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    // Provide a bridge to the old method declared on the interface and now an
    // abstract method in this class.
    return $this->processCckFieldValues($migration, $field_name, $data);
  }

  /**
   * Apply any custom processing to the field bundle migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   * @param string $field_name
   *   The field name we're processing the value for.
   * @param array $data
   *   The array of field data from FieldValues::fieldData().
   */
  abstract public function processCckFieldValues(MigrationInterface $migration, $field_name, $data);

}
