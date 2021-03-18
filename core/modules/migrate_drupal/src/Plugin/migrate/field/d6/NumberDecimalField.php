<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d6;

/**
 * MigrateField plugin for Drupal 6 CCK number_decimal fields.
 *
 * @MigrateField(
 *   id = "d6_number_decimal",
 *   type_map = {
 *     "number_decimal" = "decimal",
 *   },
 *   core = {6},
 *   source_module = "number",
 *   destination_module = "core"
 * )
 */
class NumberDecimalField extends NumberFloatField {

  /**
   * @{@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'number_decimal',
      'us_0' => 'number_decimal',
      'us_1' => 'number_decimal',
      'us_2' => 'number_decimal',
      'be_0' => 'number_decimal',
      'be_1' => 'number_decimal',
      'be_2' => 'number_decimal',
      'fr_0' => 'number_decimal',
      'fr_1' => 'number_decimal',
      'fr_2' => 'number_decimal',
      'unformatted' => 'number_unformatted',
    ];
  }

}
