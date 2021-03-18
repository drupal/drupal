<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d6;

use Drupal\migrate\Row;

/**
 * MigrateField plugin for Drupal 6 CCK number_decimal and number_float fields.
 *
 * @MigrateField(
 *   id = "d6_number_float",
 *   type_map = {
 *     "number_float" = "float",
 *   },
 *   core = {6},
 *   source_module = "number",
 *   destination_module = "core"
 * )
 */
class NumberFloatField extends NumberIntegerField {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    switch ($row->getSourceProperty('widget_type')) {
      case 'optionwidgets_buttons':
      case 'optionwidgets_select':
        return 'list_float';

      default:
        return parent::getFieldType($row);
    }
  }

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

