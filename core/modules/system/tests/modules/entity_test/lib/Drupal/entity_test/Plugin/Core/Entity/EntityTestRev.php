<?php

/**
 * @file
 * Definition of Drupal\entity_test\Plugin\Core\Entity\EntityTestRev.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\entity_test\Plugin\Core\Entity\EntityTest;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test entity class.
 *
 * @Plugin(
 *   id = "entity_test_rev",
 *   label = @Translation("Test entity - revisions"),
 *   module = "entity_test",
 *   controller_class = "Drupal\entity_test\EntityTestRevStorageController",
 *   access_controller_class = "Drupal\entity_test\EntityTestAccessController",
 *   form_controller_class = {
 *     "default" = "Drupal\entity_test\EntityTestFormController"
 *   },
 *   translation_controller_class = "Drupal\translation_entity\EntityTranslationControllerNG",
 *   base_table = "entity_test_rev",
 *   revision_table = "entity_test_rev_revision",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *   },
 *   menu_base_path = "entity_test_rev/manage/%entity_test_rev"
 * )
 */
class EntityTestRev extends EntityTest {

  /**
   * The entity revision id.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $revision_id;

  /**
   * Overrides EntityNG::init().
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
}
