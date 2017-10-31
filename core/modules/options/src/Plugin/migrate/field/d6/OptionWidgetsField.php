<?php

namespace Drupal\options\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "optionwidgets",
 *   core = {6},
 *   source_module = "optionwidgets",
 *   destination_module = "options"
 * )
 */
class OptionWidgetsField extends FieldPluginBase {}
