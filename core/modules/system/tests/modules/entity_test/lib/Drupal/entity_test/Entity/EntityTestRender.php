<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestRender.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a test entity class with a render controller.
 *
 * @EntityType(
 *   id = "entity_test_render",
 *   label = @Translation("Test render entity"),
 *   module = "entity_test",
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController",
 *     "render" = "Drupal\entity_test\EntityTestRenderController"
 *   },
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestRender extends EntityTest {

}
