<?php

/**
 * @file
 * Definition of Drupal\entity_test\Plugin\Core\Entity\EntityTestMul.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\entity_test\Plugin\Core\Entity\EntityTest;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test entity class.
 *
 * @EntityType(
 *   id = "entity_test_mul",
 *   label = @Translation("Test entity - data table"),
 *   module = "entity_test",
 *   controller_class = "Drupal\entity_test\EntityTestMulStorageController",
 *   access_controller_class = "Drupal\entity_test\EntityTestAccessController",
 *   form_controller_class = {
 *     "default" = "Drupal\entity_test\EntityTestFormController"
 *   },
 *   translation_controller_class = "Drupal\translation_entity\EntityTranslationControllerNG",
 *   base_table = "entity_test_mul",
 *   data_table = "entity_test_mul_property_data",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   menu_base_path = "entity_test_mul/manage/%entity_test_mul"
 * )
 */
class EntityTestMul extends EntityTest {

}
