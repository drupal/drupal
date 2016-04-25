<?php

namespace Drupal\module_installer_config_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a configuration-based entity type used for testing.
 *
 * @ConfigEntityType(
 *   id = "test_config_type",
 *   label = @Translation("Test entity type"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder"
 *   },
 *   admin_permission = "administer modules",
 *   config_prefix = "type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   }
 * )
 */
class TestConfigType extends ConfigEntityBase {
}
