<?php

namespace Drupal\migrate_drupal\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Provides an interface for all field type plugins.
 */
interface MigrateFieldInterface extends PluginInspectionInterface {

  /**
   * Apply any custom processing to the field migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   */
  public function alterFieldMigration(MigrationInterface $migration);

  /**
   * Apply any custom processing to the field instance migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration);

  /**
   * Apply any custom processing to the field widget migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   */
  public function alterFieldWidgetMigration(MigrationInterface $migration);

  /**
   * Apply any custom processing to the field formatter migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration entity.
   */
  public function alterFieldFormatterMigration(MigrationInterface $migration);

  /**
   * Get the field formatter type from the source.
   *
   * @param \Drupal\migrate\Row $row
   *   The field being migrated.
   *
   * @return string
   *   The field formatter type.
   */
  public function getFieldFormatterType(Row $row);

  /**
   * Get a map between D6 formatters and D8 formatters for this field type.
   *
   * This is used by static::alterFieldFormatterMigration() in the base class.
   *
   * @return array
   *   The keys are D6 formatters and the values are D8 formatters.
   */
  public function getFieldFormatterMap();

  /**
   * Get the field widget type from the source.
   *
   * @param \Drupal\migrate\Row $row
   *   The field being migrated.
   *
   * @return string
   *   The field widget type.
   */
  public function getFieldWidgetType(Row $row);

  /**
   * Get a map between D6 and D8 widgets for this field type.
   *
   * @return array
   *   The keys are D6 field widget types and the values D8 widgets.
   */
  public function getFieldWidgetMap();

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
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data);

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
