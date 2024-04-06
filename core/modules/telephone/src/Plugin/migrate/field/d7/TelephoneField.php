<?php

namespace Drupal\telephone\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

#[MigrateField(
  id: 'telephone',
  core: [7],
  source_module: 'telephone',
  destination_module: 'telephone',
)]
class TelephoneField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    // The widget IDs are identical in Drupal 7 and 8, so we do not need any
    // mapping.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'text_plain' => 'string',
      'telephone_link' => 'telephone_link',
    ];
  }

}
