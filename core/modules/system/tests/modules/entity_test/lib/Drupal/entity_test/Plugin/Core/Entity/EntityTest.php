<?php

/**
 * @file
 * Definition of Drupal\entity_test\Plugin\Core\Entity\EntityTest.
 */

namespace Drupal\entity_test\Plugin\Core\Entity;

use Drupal\Core\Entity\EntityNG;
use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test entity class.
 *
 * @Plugin(
 *   id = "entity_test",
 *   label = @Translation("Test entity"),
 *   module = "entity_test",
 *   controller_class = "Drupal\entity_test\EntityTestStorageController",
 *   access_controller_class = "Drupal\entity_test\EntityTestAccessController",
 *   form_controller_class = {
 *     "default" = "Drupal\entity_test\EntityTestFormController"
 *   },
 *   translation_controller_class = "Drupal\translation_entity\EntityTranslationControllerNG",
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   menu_base_path = "entity-test/manage/%entity_test"
 * )
 */
class EntityTest extends EntityNG {

  /**
   * The entity ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $id;

  /**
   * The entity UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $uuid;

  /**
   * The name of the test entity.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $name;

  /**
   * The associated user.
   *
   * @var \Drupal\Core\Entity\Field\FieldInterface
   */
  public $user_id;

  /**
   * Initialize the object. Invoked upon construction and wake up.
   */
  protected function init() {
    parent::init();
    // We unset all defined properties, so magic getters apply.
    unset($this->id);
    unset($this->uuid);
    unset($this->name);
    unset($this->user_id);
  }

  /**
   * Overrides Drupal\entity\Entity::label().
   */
  public function label($langcode = LANGUAGE_DEFAULT) {
    return $this->getTranslation($langcode)->name->value;
  }
}
