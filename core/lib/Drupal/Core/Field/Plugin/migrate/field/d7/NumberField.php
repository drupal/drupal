<?php

namespace Drupal\Core\Field\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "number_default",
 *   type_map = {
 *     "number_integer" = "integer",
 *     "number_decimal" = "decimal",
 *     "number_float" = "float",
 *   },
<<<<<<< HEAD
 *   core = {7},
 *   source_module = "number",
 *   destination_module = "core"
=======
 *   core = {7}
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
 * )
 */
class NumberField extends FieldPluginBase {}
