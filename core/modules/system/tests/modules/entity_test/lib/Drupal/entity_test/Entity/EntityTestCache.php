<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestCache.
 */

namespace Drupal\entity_test\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Language\Language;

/**
 * Defines the test entity class.
 *
 * @EntityType(
 *   id = "entity_test_cache",
 *   label = @Translation("Test entity with field cache"),
 *   module = "entity_test",
 *   controllers = {
 *     "storage" = "Drupal\entity_test\EntityTestStorageController",
 *     "access" = "Drupal\entity_test\EntityTestAccessController",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestFormController"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationControllerNG"
 *   },
 *   base_table = "entity_test",
 *   fieldable = TRUE,
 *   field_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   },
 *   menu_base_path = "entity-test/manage/%entity_test"
 * )
 */
class EntityTestCache extends EntityTest {

  /**
   * The entity ID.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $id;

  /**
   * The entity UUID.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $uuid;

  /**
   * The bundle of the test entity.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $type;

  /**
   * The name of the test entity.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
   */
  public $name;

  /**
   * The associated user.
   *
   * @var \Drupal\Core\Entity\Field\FieldItemListInterface
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
    unset($this->type);
  }

  /**
   * Overrides Drupal\entity\Entity::label().
   */
  public function label($langcode = Language::LANGCODE_DEFAULT) {
    $info = $this->entityInfo();
    if (isset($info['entity_keys']['label']) && $info['entity_keys']['label'] == 'name') {
      return $this->getTranslation($langcode)->name->value;
    }
    else {
      return parent::label($langcode);
    }
  }
}
