<?php

namespace Drupal\field\Plugin\migrate\source\d7;

/**
 * Drupal 7 field instance per form display source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\field\Plugin\migrate\source\d7\FieldInstance
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_field_instance_per_form_display",
 *   source_module = "field"
 * )
 */
class FieldInstancePerFormDisplay extends FieldInstance {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'bundle' => [
        'type' => 'string',
      ],
      'field_name' => [
        'type' => 'string',
        'alias' => 'fci',
      ],
    ];
  }

}
