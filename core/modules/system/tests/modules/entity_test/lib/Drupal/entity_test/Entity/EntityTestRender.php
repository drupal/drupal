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
 *     "render" = "Drupal\entity_test\EntityTestRenderController",
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestFormController"
 *     },
 *   },
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   route_base_path = "admin/structure/entity-test-render/manage/{bundle}",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/entity-test-render/{entity_test_render}",
 *     "edit-form" = "/entity-\_test_render/manage/{entity_test_render}/edit"
 *   }
 * )
 */
class EntityTestRender extends EntityTest {

}
