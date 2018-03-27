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
<<<<<<< HEAD
 *     "list_float" = "list_float",
 *   },
 *   core = {7},
 *   source_module = "list",
 *   destination_module = "options"
=======
 *   },
 *   core = {7}
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
 * )
 */
class ListField extends FieldPluginBase {}
