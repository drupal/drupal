<?php

namespace Drupal\Core\Field\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * @MigrateField(
 *   id = "entityreference",
 *   type_map = {
 *     "entityreference" = "entity_reference",
 *   },
 *   core = {7},
 *   source_module = "entityreference",
 *   destination_module = "core"
 * )
 */
class EntityReference extends FieldPluginBase {}
