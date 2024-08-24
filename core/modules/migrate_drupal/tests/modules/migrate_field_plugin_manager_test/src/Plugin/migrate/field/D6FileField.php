<?php

declare(strict_types=1);

namespace Drupal\migrate_field_plugin_manager_test\Plugin\migrate\field;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * MigrateField Plugin for Drupal 6 file fields.
 */
#[MigrateField(
  id: 'd6_file',
  core: [6],
  type_map: [
    'file' => 'file',
  ],
  source_module: 'foo',
  destination_module: 'bar',
)]
class D6FileField extends FieldPluginBase {}
