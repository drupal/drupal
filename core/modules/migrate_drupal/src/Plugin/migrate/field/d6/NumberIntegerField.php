<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspell:ignore optionwidgets

/**
 * MigrateField plugin for Drupal 6 CCK number_integer fields.
 *
 * @MigrateField(
 *   id = "d6_number_integer",
 *   type_map = {
 *     "number_integer" = "integer",
 *   },
 *   core = {6},
 *   source_module = "number",
 *   destination_module = "core"
 * )
 */
class NumberIntegerField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    switch ($row->getSourceProperty('widget_type')) {
      case 'optionwidgets_buttons':
      case 'optionwidgets_select':
        return 'list_integer';

      case 'optionwidgets_onoff':
        return 'boolean';

      default:
        return parent::getFieldType($row);
    }
  }

  /**
   * @{@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'number' => 'number',
      'optionwidgets_buttons' => 'options_buttons',
      'optionwidgets_select' => 'options_select',
      'optionwidgets_onoff' => 'boolean_checkbox',
    ];
  }

  /**
   * @{@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'number_integer',
      'us_0' => 'number_integer',
      'be_0' => 'number_integer',
      'fr_0' => 'number_integer',
      'unformatted' => 'number_unformatted',
    ];
  }

}
