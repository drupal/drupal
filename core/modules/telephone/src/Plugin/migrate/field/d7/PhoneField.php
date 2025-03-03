<?php

namespace Drupal\telephone\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Migrate field plugin for Drupal 7 phone fields.
 */
#[MigrateField(
  id: 'phone',
  core: [7],
  type_map: [
    'phone' => 'telephone',
  ],
  source_module: 'phone',
  destination_module: 'telephone',
)]
class PhoneField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'phone' => 'basic_string',
    ];
  }

}
