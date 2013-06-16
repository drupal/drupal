<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\TestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\EntityStorageControllerInterface;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "test_entity",
 *   label = @Translation("Test Entity"),
 *   module = "field_test",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController",
 *     "render" = "Drupal\Core\Entity\EntityRenderController",
 *     "form" = {
 *       "default" = "Drupal\field_test\TestEntityFormController"
 *     }
 *   },
 *   field_cache = FALSE,
 *   base_table = "test_entity",
 *   revision_table = "test_entity_revision",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "ftid",
 *     "revision" = "ftvid",
 *     "bundle" = "fttype"
 *   }
 * )
 */
class TestEntity extends Entity {

  /**
   * Primary key.
   *
   * @var integer
   */
  public $ftid;

  /**
   * Revision key.
   *
   * @var integer
   */
  public $ftvid;

  /**
   * Bundle
   *
   * @var string
   */
  public $fttype;

  /**
   * Label property
   *
   * @var string
   */
  public $ftlabel;

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->ftid;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::getRevisionId().
   */
  public function getRevisionId() {
    return $this->ftvid;
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::bundle().
   */
  public function bundle() {
    return !empty($this->fttype) ? $this->fttype : $this->entityType();
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageControllerInterface $storage_controller, \stdClass $record) {
    // Allow for predefined revision ids.
    if (!empty($record->use_provided_revision_id)) {
      $record->ftvid = $record->use_provided_revision_id;
    }
  }

}

