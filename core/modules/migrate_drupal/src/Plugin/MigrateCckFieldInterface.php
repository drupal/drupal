<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface.
 */

namespace Drupal\migrate_drupal\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Provides an interface for all CCK field type plugins.
 */
interface MigrateCckFieldInterface extends PluginInspectionInterface {

  /**
   * Apply any custom processing to the field migration.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   */
  public function processField(MigrationInterface $migration);

  /**
   * Apply any custom processing to the field instance migration.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   */
  public function processFieldInstance(MigrationInterface $migration);

  /**
   * Apply any custom processing to the field widget migration.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   */
  public function processFieldWidget(MigrationInterface $migration);

  /**
   * Apply any custom processing to the field formatter migration.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   */
  public function processFieldFormatter(MigrationInterface $migration);

  /**
   * Get a map between D6 formatters and D8 formatters for this field type.
   *
   * This is used by static::processFieldFormatter() in the base class.
   *
   * @return array
   *   The keys are D6 formatters and the values are D8 formatters.
   */
  public function getFieldFormatterMap();

  /**
   * Get a map between D6 and D8 widgets for this field type.
   *
   * @return array
   *   The keys are D6 field widget types and the values D8 widgets.
   */
  public function getFieldWidgetMap();

  /**
   * Apply any custom processing to the cck bundle migrations.
   *
   * @param \Drupal\migrate\Entity\MigrationInterface $migration
   *   The migration entity.
   * @param string $field_name
   *   The field name we're processing the value for.
   * @param array $data
   *   The array of field data from CckFieldValues::fieldData().
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data);

  /**
   * Computes the destination type of a migrated field.
   *
   * @param \Drupal\migrate\Row $row
   *   The field being migrated.
   *
   * @return string
   *   The destination field type.
   */
  public function getFieldType(Row $row);

}
