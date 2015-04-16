<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestMulRev.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTestRev;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_mulrev",
 *   label = @Translation("Test entity - revisions and data table"),
 *   handlers = {
 *     "view_builder" = "Drupal\entity_test\EntityTestViewBuilder",
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm",
 *       "delete" = "Drupal\entity_test\EntityTestDeleteForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   base_table = "entity_test_mulrev",
 *   data_table = "entity_test_mulrev_property_data",
 *   revision_table = "entity_test_mulrev_revision",
 *   revision_data_table = "entity_test_mulrev_property_revision",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "revision" = "revision_id",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/entity_test_mulrev/manage/{entity_test_mulrev}",
 *     "delete-form" = "/entity_test/delete/entity_test_mulrev/{entity_test_mulrev}",
 *     "edit-form" = "/entity_test_mulrev/manage/{entity_test_mulrev}",
 *     "revision" = "/entity_test_mulrev/{entity_test_mulrev}/revision/{entity_test_mulrev_revision}/view",
 *   }
 * )
 */
class EntityTestMulRev extends EntityTestRev {

}
