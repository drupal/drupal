<?php

/**
 * @file
 * Definition of Drupal\entity_test\Plugin\Core\Entity\EntityTestMulRev.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\entity_test\Plugin\Core\Entity\EntityTestRev;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test entity class.
 *
 * @Plugin(
 *   id = "entity_test_mulrev",
 *   label = @Translation("Test entity - revisions and data table"),
 *   module = "entity_test",
 *   controller_class = "Drupal\entity_test\EntityTestMulRevStorageController",
 *   access_controller_class = "Drupal\entity_test\EntityTestAccessController",
 *   form_controller_class = {
 *     "default" = "Drupal\entity_test\EntityTestFormController"
 *   },
 *   translation_controller_class = "Drupal\translation_entity\EntityTranslationControllerNG",
 *   base_table = "entity_test_mulrev",
 *   data_table = "entity_test_mulrev_property_data",
 *   revision_table = "entity_test_mulrev_property_revision",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *   },
 *   menu_base_path = "entity_test_mulrev/manage/%entity_test_mulrev"
 * )
 */
class EntityTestMulRev extends EntityTestRev {

}
