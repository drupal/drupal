<?php

namespace Drupal\migrate_field_plugin_manager_test\Plugin\migrate\field;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "d6_file",
 *   core = {6},
 *   type_map = {
 *     "file" = "file"
 *   },
 *   source_module = "foo",
 *   destination_module = "bar"
 * )
 */
class D6FileField extends FieldPluginBase {}
