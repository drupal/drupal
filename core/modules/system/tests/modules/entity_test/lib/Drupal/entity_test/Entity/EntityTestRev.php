<?php

/**
 * @file
 * Definition of Drupal\entity_test\Entity\EntityTestRev.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Field\FieldDefinition;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test entity class.
 *
 * @EntityType(
 *   id = "entity_test_rev",
 *   label = @Translation("Test entity - revisions"),
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController",
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestFormController"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationController"
 *   },
 *   base_table = "entity_test_rev",
 *   revision_table = "entity_test_rev_revision",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "entity_test.edit_entity_test_rev",
 *     "edit-form" = "entity_test.edit_entity_test_rev"
 *   }
 * )
 */
class EntityTestRev extends EntityTest {

  /**
   * The entity revision id.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  public $revision_id;

  /**
   * {@inheritdoc}
   */
  public function init() {
    parent::init();
    unset($this->revision_id);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::getRevisionId().
   */
  public function getRevisionId() {
    return $this->get('revision_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions($entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['revision_id'] = FieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The version id of the test entity.'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
