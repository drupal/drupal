<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\TestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @Plugin(
 *   id = "test_entity",
 *   label = @Translation("Test Entity"),
 *   module = "field_test",
 *   controller_class = "Drupal\field_test\TestEntityController",
 *   render_controller_class = "Drupal\Core\Entity\EntityRenderController",
 *   form_controller_class = {
 *     "default" = "Drupal\field_test\TestEntityFormController"
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
}

