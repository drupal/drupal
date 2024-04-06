<?php

namespace Drupal\field\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * MigrateField plugin for Drupal 7 number fields.
 */
#[MigrateField(
  id: 'number_default',
  core: [7],
  type_map: [
    'number_integer' => 'integer',
    'number_decimal' => 'decimal',
    'number_float' => 'float',
  ],
  source_module: 'number',
  destination_module: 'core',
)]
class NumberField extends FieldPluginBase {}
