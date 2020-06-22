<?php

namespace Drupal\views_config_entity_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines a configuration-based entity type used for testing Views data.
 *
 * @ConfigEntityType(
 *   id = "views_config_entity_test",
 *   label = @Translation("Test config entity type with Views data"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views_config_entity_test\ViewsConfigEntityTestViewsData"
 *   },
 *   admin_permission = "administer modules",
 *   config_prefix = "type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   }
 * )
 */
class ViewsConfigEntityTest extends ConfigEntityBase {
}
