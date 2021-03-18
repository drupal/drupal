<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * MigrateField plugin for Drupal 7 boolean fields.
 *
 * @MigrateField(
 *   id = "boolean",
 *   type_map = {
 *     "list_boolean" = "boolean",
 *   },
 *   core = {7},
 *   source_module = "list",
 *   destination_module = "core"
 * )
 */
class Boolean extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'options_onoff' => 'boolean_checkbox',
    ];
  }

}
