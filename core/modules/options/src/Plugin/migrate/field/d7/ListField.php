<?php

namespace Drupal\options\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "list",
 *   type_map = {
 *     "list_boolean" = "boolean",
 *     "list_integer" = "list_integer",
 *     "list_text" = "list_string",
 *   },
 *   core = {7},
 *   source_module = "list",
 *   destination_module = "options"
 * )
 */
class ListField extends FieldPluginBase {}
