<?php

namespace Drupal\migrate_state_active_test\Plugin\migrate\field\d7;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

// cspell:ignore fieldright

/**
 * Field migration for testing migration states.
 *
 * @MigrateField(
 *   id = "fieldright",
 *   core = {6,7},
 *   source_module = "aggregator",
 *   destination_module = "migrate_state_finished_test"
 * )
 */
class FieldRight extends FieldPluginBase {
}
