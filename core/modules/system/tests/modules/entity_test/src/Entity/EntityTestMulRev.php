<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestMulRev.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldDefinition;
use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mulrev",
 *   label = @Translation("Test entity - revisions and data table"),
 *   controllers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test_mulrev",
 *   data_table = "entity_test_mulrev_property_data",
 *   revision_table = "entity_test_mulrev_revision",
 *   revision_data_table = "entity_test_mulrev_property_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "entity_test.edit_entity_test_mulrev",
 *     "delete-form" = "entity_test.delete_entity_test_mulrev",
 *     "edit-form" = "entity_test.edit_entity_test_mulrev"
 *   }
 * )
 */
class EntityTestMulRev extends EntityTestRev {

}
