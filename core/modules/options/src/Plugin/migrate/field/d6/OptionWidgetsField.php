<?php

namespace Drupal\options\Plugin\migrate\field\d6;

use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspell:ignore optionwidgets
/**
 * MigrateField Plugin for Drupal 6 options fields.
 */
#[MigrateField(
  id: 'optionwidgets',
  core: [6],
  source_module: 'optionwidgets',
  destination_module: 'options',
)]
class OptionWidgetsField extends FieldPluginBase {}
