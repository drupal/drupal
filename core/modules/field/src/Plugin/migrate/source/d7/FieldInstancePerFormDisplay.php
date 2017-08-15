<?php

namespace Drupal\field\Plugin\migrate\source\d7;

/**
 * The field instance per form display source class.
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
