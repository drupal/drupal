<?php

namespace Drupal\options\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "options",
 *   core = {7},
 *   source_module = "options",
 *   destination_module = "options"
 * )
 */
class OptionsField extends FieldPluginBase {}
