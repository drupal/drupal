<?php

namespace Drupal\options\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Migrate field plugin for Drupal 7 options fields.
 */
#[MigrateField(
  id: 'options',
  core: [7],
  source_module: 'options',
  destination_module: 'options',
)]
class OptionsField extends FieldPluginBase {}
