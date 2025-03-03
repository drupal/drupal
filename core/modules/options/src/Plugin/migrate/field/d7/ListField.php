<?php

namespace Drupal\options\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Migrate field plugin for Drupal 7 list fields.
 */
#[MigrateField(
  id: 'list',
  core: [7],
  type_map: [
    'list_boolean' => 'boolean',
    'list_integer' => 'list_integer',
    'list_text' => 'list_string',
    'list_float' => 'list_float',
  ],
  source_module: 'list',
  destination_module: 'options',
)]
class ListField extends FieldPluginBase {}
